import numpy as np

from services.image_preprocessing import LetterboxTransform
from services.yolo_decoder import decode_yolo_output

LABELS = [
    "construction_materials",
    "garbage_debris",
    "illegal_parking",
    "road_obstruction",
    "sidewalk_obstruction",
]
TRANSFORM = LetterboxTransform(640, 640, 640, 640, 0, 0, 1.0)


def test_decoder_returns_controlled_empty_detection_list():
    output = np.zeros((1, 9, 8400), dtype=np.float32)

    assert decode_yolo_output(output, LABELS, TRANSFORM) == []


def test_decoder_applies_per_class_nms_and_preserves_other_classes():
    output = np.zeros((1, 9, 8400), dtype=np.float32)
    output[0, :4, 0] = [200 / 640, 200 / 640, 100 / 640, 100 / 640]
    output[0, 4, 0] = 0.90
    output[0, :4, 1] = [205 / 640, 205 / 640, 100 / 640, 100 / 640]
    output[0, 4, 1] = 0.80
    output[0, :4, 2] = [205 / 640, 205 / 640, 100 / 640, 100 / 640]
    output[0, 5, 2] = 0.85

    detections = decode_yolo_output(output, LABELS, TRANSFORM)

    assert len(detections) == 2
    assert [item["class_name"] for item in detections] == [
        "construction_materials",
        "garbage_debris",
    ]
    assert detections[0]["confidence"] == 0.9


def test_decoder_maps_letterboxed_boxes_to_original_coordinates():
    output = np.zeros((1, 9, 8400), dtype=np.float32)
    output[0, :4, 0] = [0.5, 0.5, 0.5, 0.25]
    output[0, 6, 0] = 0.75
    landscape = LetterboxTransform(800, 400, 640, 320, 0, 160, 0.8)

    detection = decode_yolo_output(output, LABELS, landscape)[0]

    assert detection["class_name"] == "illegal_parking"
    assert detection["x_min"] == 200.0
    assert detection["x_max"] == 600.0
    assert detection["y_min"] == 100.0
    assert detection["y_max"] == 300.0


def test_decoder_retains_low_confidence_evidence_at_point_25():
    output = np.zeros((1, 9, 8400), dtype=np.float32)
    output[0, :4, 0] = [0.7485, 0.6194, 0.1412, 0.2509]
    output[0, 6, 0] = 0.296444
    landscape = LetterboxTransform(736, 552, 640, 480, 0, 80, 640 / 736)

    detection = decode_yolo_output(output, LABELS, landscape)[0]

    assert detection["class_name"] == "illegal_parking"
    assert detection["confidence"] == 0.296444
    assert detection["x_min"] == 498.934
    assert detection["y_min"] == 271.547
    assert detection["x_max"] == 602.858
    assert detection["y_max"] == 456.21


def test_decoder_rejects_candidates_below_point_25():
    output = np.zeros((1, 9, 8400), dtype=np.float32)
    output[0, :4, 0] = [0.5, 0.5, 0.25, 0.25]
    output[0, 6, 0] = 0.249999

    assert decode_yolo_output(output, LABELS, TRANSFORM) == []
