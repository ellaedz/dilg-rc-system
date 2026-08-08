import hashlib
import io
import threading
import time
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

import numpy as np
from PIL import Image

from services.image_inference_service import ImageInferenceService

SERVER_DIR = Path(__file__).resolve().parents[1]
MODEL_DIR = SERVER_DIR / "models/image"


def png_bytes() -> bytes:
    buffer = io.BytesIO()
    Image.new("RGB", (64, 64), (0, 0, 0)).save(buffer, format="PNG")
    return buffer.getvalue()


def sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def test_server_owned_artifacts_match_the_approved_hash_manifest():
    approved_hashes = {
        "best_float16.tflite": "deb4e346701a063cfa39494fd9ab86882269ca827795304db27e60f8e42a7c0f",
        "labels.txt": "63c27fd6842efb23a300d5427d066314021c59d2c02fde3aab1c938c0e03cc16",
        "model_metadata.json": "30b2ac8a60c281615cc48cf701bad05543772cc18ef42ded38354c6c7e7869cc",
    }

    for artifact_name, approved_hash in approved_hashes.items():
        assert sha256(MODEL_DIR / artifact_name) == approved_hash


def test_real_litert_model_loads_and_runs_a_controlled_no_detection():
    service = ImageInferenceService(
        MODEL_DIR / "best_float16.tflite",
        MODEL_DIR / "labels.txt",
        MODEL_DIR / "model_metadata.json",
    )
    service.load()

    assert service.loaded is True
    assert tuple(service.input_details["shape"]) == (1, 640, 640, 3)
    assert tuple(service.output_details["shape"]) == (1, 9, 8400)
    result = service.predict(png_bytes(), "image/png")
    assert result["status"] in {"detected", "no_detection"}
    assert isinstance(result["detections"], list)


class ConcurrencyDetectingInterpreter:
    def __init__(self) -> None:
        self.active = 0
        self.maximum_active = 0
        self.guard = threading.Lock()

    def set_tensor(self, _index, _tensor) -> None:
        pass

    def invoke(self) -> None:
        with self.guard:
            self.active += 1
            self.maximum_active = max(self.maximum_active, self.active)
        time.sleep(0.03)
        with self.guard:
            self.active -= 1

    def get_tensor(self, _index):
        return np.zeros((1, 9, 8400), dtype=np.float32)


def test_shared_interpreter_mutation_is_serialized():
    service = ImageInferenceService(Path("unused"), Path("unused"), Path("unused"))
    interpreter = ConcurrencyDetectingInterpreter()
    service.interpreter = interpreter
    service.input_details = {"index": 0}
    service.output_details = {"index": 1}
    service.labels = service.EXPECTED_LABELS

    with ThreadPoolExecutor(max_workers=2) as executor:
        results = list(executor.map(lambda _: service.predict(png_bytes(), "image/png"), range(2)))

    assert interpreter.maximum_active == 1
    assert all(result["status"] == "no_detection" for result in results)
