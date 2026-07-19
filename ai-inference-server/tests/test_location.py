from fastapi.testclient import TestClient

from main import app


def test_location_validates_municipality_without_faking_barangay():
    with TestClient(app) as client:
        response = client.post("/predict/location", json={"latitude": 14.281, "longitude": 121.416})
    assert response.status_code == 200
    payload = response.json()
    assert payload["inside_santa_cruz"] is True
    assert payload["barangay"] is None
    assert payload["barangay_detection_status"] == "barangay_boundary_unavailable"
    assert payload["needs_manual_barangay_review"] is True
