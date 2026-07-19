from __future__ import annotations

import json
from pathlib import Path
from typing import Any


class GISService:
    def __init__(self, municipal_boundary_path: Path, barangay_boundary_path: Path) -> None:
        self.municipal_boundary_path = municipal_boundary_path
        self.barangay_boundary_path = barangay_boundary_path
        self.municipal_boundary = self._load_feature_collection(municipal_boundary_path)
        self.barangay_boundaries = self._load_feature_collection(barangay_boundary_path)

    def validate(self, latitude: float, longitude: float, supplied_barangay: str | None = None) -> dict[str, Any]:
        inside = bool(
            self.municipal_boundary
            and self._point_intersects_collection(latitude, longitude, self.municipal_boundary)
        )

        if not inside:
            return {
                "inside_santa_cruz": False,
                "municipality_name": None,
                "barangay": None,
                "barangay_detection_status": "outside_coverage",
                "needs_manual_barangay_review": True,
                "location_context": "Outside Santa Cruz Coverage",
            }

        if not self.barangay_boundaries or not self.barangay_boundaries.get("features"):
            return {
                "inside_santa_cruz": True,
                "municipality_name": "Santa Cruz",
                "barangay": None,
                "barangay_detection_status": "barangay_boundary_unavailable",
                "needs_manual_barangay_review": True,
                "location_context": "Inside Santa Cruz; Needs Barangay Review",
            }

        detected = self._detect_barangay(latitude, longitude)
        return {
            "inside_santa_cruz": True,
            "municipality_name": "Santa Cruz",
            "barangay": detected,
            "barangay_detection_status": "auto_detected" if detected else "barangay_not_matched",
            "needs_manual_barangay_review": detected is None,
            "location_context": "Inside Barangay Boundary" if detected else "Inside Santa Cruz; Needs Barangay Review",
        }

    @staticmethod
    def _load_feature_collection(path: Path) -> dict[str, Any] | None:
        if not path.is_file():
            return None
        try:
            value = json.loads(path.read_text(encoding="utf-8"))
            return value if value.get("type") == "FeatureCollection" else None
        except (OSError, json.JSONDecodeError, AttributeError):
            return None

    def _detect_barangay(self, latitude: float, longitude: float) -> str | None:
        for feature in self.barangay_boundaries.get("features", []):
            geometry = feature.get("geometry")
            if geometry and self._point_in_geometry(latitude, longitude, geometry):
                properties = feature.get("properties", {})
                for key in ("barangay", "name", "ADM4_EN"):
                    if properties.get(key):
                        return str(properties[key]).strip()
        return None

    def _point_intersects_collection(self, latitude: float, longitude: float, collection: dict[str, Any]) -> bool:
        return any(
            feature.get("geometry")
            and self._point_in_geometry(latitude, longitude, feature["geometry"])
            for feature in collection.get("features", [])
        )

    def _point_in_geometry(self, latitude: float, longitude: float, geometry: dict[str, Any]) -> bool:
        coordinates = geometry.get("coordinates", [])
        if geometry.get("type") == "Polygon":
            return self._point_in_polygon(latitude, longitude, coordinates)
        if geometry.get("type") == "MultiPolygon":
            return any(self._point_in_polygon(latitude, longitude, polygon) for polygon in coordinates)
        return False

    def _point_in_polygon(self, latitude: float, longitude: float, polygon: list[Any]) -> bool:
        if not polygon or not self._point_in_ring(latitude, longitude, polygon[0]):
            return False
        return not any(self._point_in_ring(latitude, longitude, hole) for hole in polygon[1:])

    @staticmethod
    def _point_in_ring(latitude: float, longitude: float, ring: list[list[float]]) -> bool:
        if len(ring) < 3:
            return False
        inside = False
        previous = len(ring) - 1
        for current in range(len(ring)):
            x_current, y_current = ring[current][:2]
            x_previous, y_previous = ring[previous][:2]
            if (y_current > latitude) != (y_previous > latitude):
                intersection = (x_previous - x_current) * (latitude - y_current) / (y_previous - y_current) + x_current
                if longitude < intersection:
                    inside = not inside
            previous = current
        return inside
