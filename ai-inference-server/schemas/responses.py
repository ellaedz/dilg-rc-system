from typing import Any

from pydantic import BaseModel


class TextPredictionResponse(BaseModel):
    prediction: str | None
    confidence: float
    model_version: str
    decision_source: str
    needs_manual_review: bool


class LocationPredictionResponse(BaseModel):
    inside_santa_cruz: bool
    municipality_name: str | None
    barangay: str | None
    barangay_detection_status: str
    needs_manual_barangay_review: bool
    location_context: str


class MultimodalPredictionResponse(BaseModel):
    final_violation_type: str | None
    final_confidence: float
    decision_source: str
    needs_manual_review: bool
    text_result: dict[str, Any]
    image_result: dict[str, Any]
    location_result: dict[str, Any]
