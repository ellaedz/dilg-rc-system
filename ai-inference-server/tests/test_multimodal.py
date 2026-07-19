from fastapi.testclient import TestClient

from main import app


def test_multimodal_prioritizes_strong_image_when_text_is_weak():
    with TestClient(app) as client:
        response = client.post("/predict/multimodal", json={
            "image_result": "illegal_parking",
            "image_confidence": 0.92,
            "text_report": "May sasakyan na nakaharang sa kalsada.",
            "latitude": 14.281,
            "longitude": 121.416,
            "barangay": None,
        })
    assert response.status_code == 200
    payload = response.json()
    assert payload["final_violation_type"] == "illegal_parking"
    assert payload["decision_source"] in {"image_priority", "image_text_agreement"}
    assert payload["location_result"]["barangay"] is None


def test_strong_disagreement_requires_manual_review():
    from services.fusion_service import FusionService

    result = FusionService().fuse(
        "illegal_parking",
        0.92,
        {"prediction": "garbage_debris", "confidence": 0.90},
        {"inside_santa_cruz": True},
    )
    assert result["decision_source"] == "strong_disagreement_manual_review"
    assert result["needs_manual_review"] is True
