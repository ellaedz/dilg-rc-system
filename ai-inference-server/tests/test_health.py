from fastapi.testclient import TestClient

from main import app


def test_health_reports_loaded_model_and_boundary():
    with TestClient(app) as client:
        response = client.get("/health")
    assert response.status_code == 200
    payload = response.json()
    assert payload["status"] == "ok"
    assert payload["nlp_model_loaded"] is True
    assert payload["municipal_boundary_loaded"] is True
