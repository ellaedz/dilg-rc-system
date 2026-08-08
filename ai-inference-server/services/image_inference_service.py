from __future__ import annotations

import hashlib
import json
import logging
import threading
import time
from pathlib import Path
from typing import Any

import numpy as np

from services.image_preprocessing import preprocess_image
from services.yolo_decoder import ModelContractError, decode_yolo_output

LOGGER = logging.getLogger(__name__)


class ImageInferenceService:
    EXPECTED_MODEL_SHA256 = "deb4e346701a063cfa39494fd9ab86882269ca827795304db27e60f8e42a7c0f"
    EXPECTED_LABELS_SHA256 = "63c27fd6842efb23a300d5427d066314021c59d2c02fde3aab1c938c0e03cc16"
    EXPECTED_METADATA_SHA256 = "30b2ac8a60c281615cc48cf701bad05543772cc18ef42ded38354c6c7e7869cc"
    EXPECTED_LABELS = [
        "construction_materials",
        "garbage_debris",
        "illegal_parking",
        "road_obstruction",
        "sidewalk_obstruction",
    ]

    def __init__(self, model_path: Path, labels_path: Path, metadata_path: Path) -> None:
        self.model_path = model_path
        self.labels_path = labels_path
        self.metadata_path = metadata_path
        self.interpreter: Any | None = None
        self.input_details: dict[str, Any] | None = None
        self.output_details: dict[str, Any] | None = None
        self.labels: list[str] = []
        self.metadata: dict[str, Any] = {}
        self.load_error: str | None = None
        self.runtime = "ai-edge-litert"
        self.runtime_version: str | None = None
        self.model_sha256: str | None = None
        self._interpreter_lock = threading.Lock()

    @property
    def loaded(self) -> bool:
        return self.interpreter is not None

    @staticmethod
    def _sha256(path: Path) -> str:
        return hashlib.sha256(path.read_bytes()).hexdigest()

    def load(self) -> None:
        self.interpreter = None
        try:
            from importlib.metadata import version

            from ai_edge_litert.interpreter import Interpreter

            hashes = {
                "model": self._sha256(self.model_path),
                "labels": self._sha256(self.labels_path),
                "metadata": self._sha256(self.metadata_path),
            }
            expected = {
                "model": self.EXPECTED_MODEL_SHA256,
                "labels": self.EXPECTED_LABELS_SHA256,
                "metadata": self.EXPECTED_METADATA_SHA256,
            }
            if hashes != expected:
                raise ModelContractError("One or more image-model artifact hashes do not match.")

            labels = [
                line.strip()
                for line in self.labels_path.read_text(encoding="utf-8").splitlines()
                if line.strip()
            ]
            if labels != self.EXPECTED_LABELS:
                raise ModelContractError("The image-model class order does not match the approved contract.")
            metadata = json.loads(self.metadata_path.read_text(encoding="utf-8"))

            interpreter = Interpreter(model_path=str(self.model_path))
            interpreter.allocate_tensors()
            input_details = interpreter.get_input_details()
            output_details = interpreter.get_output_details()
            if len(input_details) != 1 or len(output_details) != 1:
                raise ModelContractError("The image model must expose one input and one output tensor.")
            input_detail = input_details[0]
            output_detail = output_details[0]
            if tuple(input_detail["shape"]) != (1, 640, 640, 3) or input_detail["dtype"] != np.float32:
                raise ModelContractError("Unexpected image-model input tensor contract.")
            if tuple(output_detail["shape"]) != (1, 9, 8400) or output_detail["dtype"] != np.float32:
                raise ModelContractError("Unexpected image-model output tensor contract.")

            self.interpreter = interpreter
            self.input_details = input_detail
            self.output_details = output_detail
            self.labels = labels
            self.metadata = metadata
            self.model_sha256 = hashes["model"]
            self.runtime_version = version("ai-edge-litert")
            self.load_error = None
            LOGGER.info("Loaded image model with ai-edge-litert %s.", self.runtime_version)
        except Exception as exc:
            self.interpreter = None
            self.input_details = None
            self.output_details = None
            self.labels = []
            self.metadata = {}
            self.load_error = str(exc)
            LOGGER.exception("Image model load failed.")

    def predict(self, image_bytes: bytes, content_type: str | None) -> dict[str, Any]:
        if not self.loaded or self.input_details is None or self.output_details is None:
            raise RuntimeError("The image model is unavailable.")

        started = time.perf_counter()
        tensor, transform = preprocess_image(image_bytes, content_type)
        preprocessed = time.perf_counter()

        # One interpreter is deliberately retained. Its full mutable sequence is
        # serialized; the output is copied before releasing the lock.
        with self._interpreter_lock:
            self.interpreter.set_tensor(self.input_details["index"], tensor)
            self.interpreter.invoke()
            output = self.interpreter.get_tensor(self.output_details["index"]).copy()
        inferred = time.perf_counter()

        detections = decode_yolo_output(output, self.labels, transform)
        finished = time.perf_counter()
        primary = detections[0] if detections else None
        return {
            "prediction": primary["class_name"] if primary else None,
            "confidence": primary["confidence"] if primary else 0.0,
            "detections": detections,
            "detection_count": len(detections),
            "status": "detected" if detections else "no_detection",
            "input": {
                "original_width": transform.original_width,
                "original_height": transform.original_height,
                "resized_width": transform.resized_width,
                "resized_height": transform.resized_height,
                "pad_x": transform.pad_x,
                "pad_y": transform.pad_y,
            },
            "timing_ms": {
                "preprocessing": round((preprocessed - started) * 1000, 3),
                "inference": round((inferred - preprocessed) * 1000, 3),
                "postprocessing": round((finished - inferred) * 1000, 3),
                "total": round((finished - started) * 1000, 3),
            },
        }

    def safe_status(self) -> dict[str, Any]:
        return {
            "loaded": self.loaded,
            "runtime": self.runtime,
            "runtime_version": self.runtime_version,
            "model_version": self.metadata.get("model", {}).get("version") if self.loaded else None,
            "model_sha256": self.model_sha256,
            "contract": {
                "input_shape": [1, 640, 640, 3],
                "output_shape": [1, 9, 8400],
                "class_count": len(self.EXPECTED_LABELS),
            },
            "error_code": None if self.loaded else "image_model_unavailable",
        }
