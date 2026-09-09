import json
from pathlib import Path

from fastapi.testclient import TestClient

from main import app, gis_service


def test_location_automatically_detects_the_mpdo_barangay():
    with TestClient(app) as client:
        response = client.post("/predict/location", json={"latitude": 14.281, "longitude": 121.416})
    assert response.status_code == 200
    payload = response.json()
    assert payload["inside_santa_cruz"] is True
    assert payload["barangay"] == "Poblacion III"
    assert payload["barangay_detection_status"] == "auto_detected"
    assert payload["needs_manual_barangay_review"] is False


def test_location_rejects_points_outside_municipal_coverage():
    with TestClient(app) as client:
        response = client.post("/predict/location", json={"latitude": 14.5995, "longitude": 120.9842})

    assert response.status_code == 200
    payload = response.json()
    assert payload["inside_santa_cruz"] is False
    assert payload["municipality_name"] is None
    assert payload["barangay"] is None
    assert payload["barangay_detection_status"] == "outside_coverage"


def test_all_mpdo_acceptance_points_resolve_to_their_barangays():
    fixture_path = Path(__file__).resolve().parents[2] / "tests/Fixtures/mpdo_barangay_acceptance_points.geojson"
    features = json.loads(fixture_path.read_text(encoding="utf-8"))["features"]

    assert len(features) == 26
    for feature in features:
        longitude, latitude = feature["geometry"]["coordinates"]
        result = gis_service.validate(latitude, longitude)
        assert result["barangay"] == feature["properties"]["name"]
        assert result["barangay_detection_status"] == "auto_detected"
        assert result["needs_manual_barangay_review"] is False


def test_shared_barangay_boundary_requires_manual_review():
    result = gis_service.validate(14.231875, 121.3875028)

    assert result["inside_santa_cruz"] is True
    assert result["barangay"] is None
    assert result["barangay_detection_status"] == "barangay_boundary_ambiguous"
    assert result["needs_manual_barangay_review"] is True
