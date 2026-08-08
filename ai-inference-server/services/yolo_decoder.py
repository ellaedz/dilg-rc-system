from __future__ import annotations

from typing import Any

import numpy as np

from services.image_preprocessing import INPUT_SIZE, LetterboxTransform

# Keep plausible low-confidence evidence so it can be shown to staff and explicitly
# marked for manual review. Automatic acceptance remains governed by the separate
# 0.60 review threshold in main.py.
CANDIDATE_THRESHOLD = 0.25
IOU_THRESHOLD = 0.45
MAX_DETECTIONS = 20


class ModelContractError(RuntimeError):
    pass


def _intersection_over_union(left: dict[str, Any], right: dict[str, Any]) -> float:
    x_min = max(left["x_min"], right["x_min"])
    y_min = max(left["y_min"], right["y_min"])
    x_max = min(left["x_max"], right["x_max"])
    y_max = min(left["y_max"], right["y_max"])
    intersection = max(0.0, x_max - x_min) * max(0.0, y_max - y_min)
    left_area = max(0.0, left["x_max"] - left["x_min"]) * max(0.0, left["y_max"] - left["y_min"])
    right_area = max(0.0, right["x_max"] - right["x_min"]) * max(0.0, right["y_max"] - right["y_min"])
    union = left_area + right_area - intersection
    return intersection / union if union > 0 else 0.0


def _per_class_nms(detections: list[dict[str, Any]]) -> list[dict[str, Any]]:
    kept: list[dict[str, Any]] = []
    for class_id in sorted({int(item["class_id"]) for item in detections}):
        candidates = sorted(
            (item for item in detections if item["class_id"] == class_id),
            key=lambda item: item["confidence"],
            reverse=True,
        )
        while candidates:
            selected = candidates.pop(0)
            kept.append(selected)
            candidates = [
                candidate
                for candidate in candidates
                if _intersection_over_union(selected, candidate) <= IOU_THRESHOLD
            ]
    return sorted(kept, key=lambda item: item["confidence"], reverse=True)[:MAX_DETECTIONS]


def decode_yolo_output(
    output: np.ndarray,
    labels: list[str],
    transform: LetterboxTransform,
) -> list[dict[str, Any]]:
    expected_shape = (1, 4 + len(labels), 8400)
    if output.shape != expected_shape or output.dtype != np.float32:
        raise ModelContractError(
            f"Unexpected image-model output contract: shape={output.shape}, dtype={output.dtype}."
        )

    detections: list[dict[str, Any]] = []
    candidates = output[0]
    for candidate_index in range(candidates.shape[1]):
        class_scores = candidates[4:, candidate_index]
        class_id = int(np.argmax(class_scores))
        confidence = float(class_scores[class_id])
        if not np.isfinite(confidence) or confidence < CANDIDATE_THRESHOLD:
            continue

        normalized_center_x, normalized_center_y, normalized_width, normalized_height = (
            float(value) for value in candidates[:4, candidate_index]
        )
        if not all(
            np.isfinite(value)
            for value in (
                normalized_center_x,
                normalized_center_y,
                normalized_width,
                normalized_height,
            )
        ):
            continue
        if normalized_width <= 0 or normalized_height <= 0:
            continue

        # Ultralytics LiteRT exports raw xywh coordinates normalized to [0, 1].
        # Convert them to the 640x640 letterboxed input coordinate space before
        # removing padding and mapping them back to the original image.
        center_x = normalized_center_x * INPUT_SIZE
        center_y = normalized_center_y * INPUT_SIZE
        width = normalized_width * INPUT_SIZE
        height = normalized_height * INPUT_SIZE

        x_min = (center_x - width / 2 - transform.pad_x) / transform.scale
        y_min = (center_y - height / 2 - transform.pad_y) / transform.scale
        x_max = (center_x + width / 2 - transform.pad_x) / transform.scale
        y_max = (center_y + height / 2 - transform.pad_y) / transform.scale
        x_min = min(max(x_min, 0.0), float(transform.original_width))
        y_min = min(max(y_min, 0.0), float(transform.original_height))
        x_max = min(max(x_max, 0.0), float(transform.original_width))
        y_max = min(max(y_max, 0.0), float(transform.original_height))
        if x_max <= x_min or y_max <= y_min:
            continue

        detections.append({
            "class_id": class_id,
            "class_name": labels[class_id],
            "confidence": round(min(max(confidence, 0.0), 1.0), 6),
            "x_center": round((x_min + x_max) / 2, 3),
            "y_center": round((y_min + y_max) / 2, 3),
            "width": round(x_max - x_min, 3),
            "height": round(y_max - y_min, 3),
            "x_min": round(x_min, 3),
            "y_min": round(y_min, 3),
            "x_max": round(x_max, 3),
            "y_max": round(y_max, 3),
        })

    return _per_class_nms(detections)
