from __future__ import annotations

import os
from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import FastAPI, HTTPException

from schemas.requests import LocationPredictionRequest, MultimodalPredictionRequest, TextPredictionRequest
from schemas.responses import LocationPredictionResponse, MultimodalPredictionResponse, TextPredictionResponse
from services.fusion_service import FusionService
from services.gis_service import GISService
from services.nlp_service import NLPService

BASE_DIR = Path(__file__).resolve().parent
REPOSITORY_DIR = BASE_DIR.parent

nlp_service = NLPService(Path(os.getenv("NLP_MODEL_PATH", BASE_DIR / "models/nlp/civiclear_nlp_model.joblib")))
gis_service = GISService(
    Path(os.getenv("MUNICIPAL_BOUNDARY_PATH", REPOSITORY_DIR / "public/gis/boundary.geojson")),
    Path(os.getenv("BARANGAY_BOUNDARY_PATH", REPOSITORY_DIR / "public/gis/santa_cruz_barangays.geojson")),
)
fusion_service = FusionService()


@asynccontextmanager
async def lifespan(_: FastAPI):
    nlp_service.load()
    yield


app = FastAPI(title="DILG-RC AI Inference Server", version="6.0.0", lifespan=lifespan)


@app.get("/health")
def health() -> dict:
    return {
        "status": "ok" if nlp_service.loaded else "degraded",
        "nlp_model_loaded": nlp_service.loaded,
        "nlp_model_version": nlp_service.model_version,
        "nlp_model_classes": nlp_service.classes,
        "nlp_load_error": nlp_service.load_error,
        "municipal_boundary_loaded": gis_service.municipal_boundary is not None,
        "barangay_boundaries_loaded": gis_service.barangay_boundaries is not None,
    }


@app.post("/predict/text", response_model=TextPredictionResponse)
def predict_text(request: TextPredictionRequest) -> dict:
    try:
        return nlp_service.predict(request.text_report)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


@app.post("/predict/location", response_model=LocationPredictionResponse)
def predict_location(request: LocationPredictionRequest) -> dict:
    return gis_service.validate(request.latitude, request.longitude, request.barangay)


@app.post("/predict/multimodal", response_model=MultimodalPredictionResponse)
def predict_multimodal(request: MultimodalPredictionRequest) -> dict:
    text_result = nlp_service.predict(request.text_report)
    location_result = gis_service.validate(request.latitude, request.longitude, request.barangay)
    return fusion_service.fuse(request.image_result, request.image_confidence, text_result, location_result)
