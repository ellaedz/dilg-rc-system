from fastapi.testclient import TestClient

from main import app


def test_text_uses_trained_pipeline():
    with TestClient(app) as client:
        response = client.post("/predict/text", json={"text_report": "May sasakyan na nakaharang sa kalsada."})
    assert response.status_code == 200
    payload = response.json()
    assert payload["prediction"] == "illegal_parking"
    assert payload["decision_source"] == "trained_nlp_model"
    assert 0 <= payload["confidence"] <= 1
