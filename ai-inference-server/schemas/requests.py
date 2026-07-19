from pydantic import BaseModel, Field


class TextPredictionRequest(BaseModel):
    text_report: str = Field(min_length=1, max_length=5000)


class LocationPredictionRequest(BaseModel):
    latitude: float = Field(ge=-90, le=90)
    longitude: float = Field(ge=-180, le=180)
    barangay: str | None = Field(default=None, max_length=255)


class MultimodalPredictionRequest(LocationPredictionRequest, TextPredictionRequest):
    image_result: str | None = Field(default=None, max_length=100)
    image_confidence: float | None = Field(default=None, ge=0, le=1)
