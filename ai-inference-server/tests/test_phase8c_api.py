import io

from fastapi.testclient import TestClient
from PIL import Image

import main


def png_bytes() -> bytes:
    buffer = io.BytesIO()
    Image.new("RGB", (64, 64), (0, 0, 0)).save(buffer, format="PNG")
    return buffer.getvalue()


def fake_no_detection(_content: bytes, _content_type: str | None) -> dict:
    return {
        "prediction": None,
        "confidence": 0.0,
        "detections": [],
        "detection_count": 0,
        "status": "no_detection",
        "input": {
            "original_width": 64,
            "original_height": 64,
            "resized_width": 640,
            "resized_height": 640,
            "pad_x": 0,
            "pad_y": 0,
        },
        "timing_ms": {
            "preprocessing": 1.0,
            "inference": 2.0,
            "postprocessing": 1.0,
            "total": 4.0,
        },
    }


def fake_low_confidence(_content: bytes, _content_type: str | None) -> dict:
    result = fake_no_detection(_content, _content_type)
    result.update({
        "prediction": "road_obstruction",
        "confidence": 0.5,
        "status": "detected",
        "detection_count": 1,
        "detections": [{
            "class_id": 3,
            "class_name": "road_obstruction",
            "confidence": 0.5,
        }],
    })
    return result


def test_health_is_live_and_ready_reports_inference_readiness():
    with TestClient(main.app) as client:
        health = client.get("/health")
        ready = client.get("/ready")

    assert health.status_code == 200
    assert health.json()["liveness"] == "alive"
    assert health.json()["components"]["image"]["loaded"] is True
    assert ready.status_code == 200
    assert ready.json()["ready"] is True


def test_ready_is_503_when_required_model_is_unavailable(monkeypatch):
    with TestClient(main.app) as client:
        monkeypatch.setattr(main.image_service, "interpreter", None)
        response = client.get("/ready")

    assert response.status_code == 503
    assert response.json()["missing_required_components"] == ["image_model"]


def test_missing_barangay_polygons_do_not_make_service_unready(monkeypatch):
    with TestClient(main.app) as client:
        monkeypatch.setattr(main.gis_service, "barangay_boundaries", None)
        response = client.get("/ready")

    assert response.status_code == 200
    assert response.json()["barangay_routing_status"] == "barangay_boundary_unavailable"


def test_invalid_malformed_and_excessive_uploads_are_controlled():
    with TestClient(main.app) as client:
        unsupported = client.post(
            "/v1/predict/image",
            files={"image": ("evidence.txt", b"not an image", "text/plain")},
        )
        malformed = client.post(
            "/v1/predict/image",
            files={"image": ("evidence.png", b"not a png", "image/png")},
        )
        excessive = client.post(
            "/v1/predict/image",
            files={
                "image": (
                    "evidence.png",
                    b"x" * (main.MAX_UPLOAD_BYTES + 1),
                    "image/png",
                )
            },
        )

    assert unsupported.status_code == 415
    assert unsupported.json()["error"]["code"] == "unsupported_image_type"
    assert malformed.status_code == 422
    assert malformed.json()["error"]["code"] == "invalid_image"
    assert excessive.status_code == 413
    assert excessive.json()["error"]["code"] == "image_size_exceeded"


def test_low_confidence_is_ai_review_not_an_operational_error(monkeypatch):
    with TestClient(main.app) as client:
        monkeypatch.setattr(main.image_service, "predict", fake_low_confidence)
        response = client.post(
            "/v1/predict/image",
            files={"image": ("evidence.png", png_bytes(), "image/png")},
        )

    assert response.status_code == 200
    assert response.json()["review"] == {
        "ai_needs_manual_review": True,
        "ai_manual_review_reasons": ["low_image_confidence"],
    }


def test_multimodal_separates_ai_review_from_barangay_review(monkeypatch):
    with TestClient(main.app) as client:
        monkeypatch.setattr(main.image_service, "predict", fake_no_detection)
        response = client.post(
            "/v1/predict/multimodal",
            files={"image": ("evidence.png", png_bytes(), "image/png")},
            data={
                "text_report": "May sasakyan na nakaharang sa kalsada.",
                "latitude": "14.281",
                "longitude": "121.416",
                "barangay_hint": "Unverified Client Barangay",
            },
        )

    assert response.status_code == 200
    payload = response.json()
    assert payload["gis"]["barangay"] is None
    assert payload["gis"]["barangay_assignment_status"] == "barangay_boundary_unavailable"
    assert payload["gis"]["needs_manual_barangay_review"] is True
    assert "no_image_detection" in payload["review"]["ai_manual_review_reasons"]
    assert "barangay_boundary_unavailable" not in payload["review"]["ai_manual_review_reasons"]
