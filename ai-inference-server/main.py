from __future__ import annotations

import os
import time
from contextlib import asynccontextmanager
from pathlib import Path
from typing import Any

from fastapi import FastAPI, File, Form, HTTPException, Request, UploadFile
from fastapi.responses import JSONResponse
from starlette.concurrency import run_in_threadpool

from schemas.requests import LocationPredictionRequest, MultimodalPredictionRequest, TextPredictionRequest
from schemas.responses import LocationPredictionResponse, MultimodalPredictionResponse, TextPredictionResponse
from services.fusion_service import FusionService
from services.gis_service import GISService
from services.image_inference_service import ImageInferenceService
from services.image_preprocessing import ImageInputError
from services.nlp_service import NLPService
from services.yolo_decoder import ModelContractError

BASE_DIR = Path(__file__).resolve().parent
REPOSITORY_DIR = BASE_DIR.parent
MAX_UPLOAD_BYTES = int(os.getenv("MAX_IMAGE_UPLOAD_BYTES", str(5 * 1024 * 1024)))

nlp_service = NLPService(Path(os.getenv("NLP_MODEL_PATH", BASE_DIR / "models/nlp/civiclear_nlp_model.joblib")))
gis_service = GISService(
    Path(os.getenv("MUNICIPAL_BOUNDARY_PATH", REPOSITORY_DIR / "public/gis/boundary.geojson")),
    Path(os.getenv("BARANGAY_BOUNDARY_PATH", REPOSITORY_DIR / "public/gis/santa_cruz_barangays.geojson")),
)
fusion_service = FusionService()
image_service = ImageInferenceService(
    Path(os.getenv("IMAGE_MODEL_PATH", BASE_DIR / "models/image/best_float16.tflite")),
    Path(os.getenv("IMAGE_LABELS_PATH", BASE_DIR / "models/image/labels.txt")),
    Path(os.getenv("IMAGE_METADATA_PATH", BASE_DIR / "models/image/model_metadata.json")),
)


@asynccontextmanager
async def lifespan(_: FastAPI):
    nlp_service.load()
    image_service.load()
    yield


app = FastAPI(title="DILG-RC AI Inference Server", version="8.3.0", lifespan=lifespan)


def operational_error(code: str, message: str, status_code: int) -> JSONResponse:
    return JSONResponse(
        status_code=status_code,
        content={"error": {"code": code, "message": message}},
    )


@app.exception_handler(ImageInputError)
async def image_input_error_handler(_: Request, exc: ImageInputError) -> JSONResponse:
    return operational_error(exc.code, exc.message, exc.status_code)


async def read_bounded_upload(image: UploadFile) -> bytes:
    content = await image.read(MAX_UPLOAD_BYTES + 1)
    await image.close()
    if len(content) > MAX_UPLOAD_BYTES:
        raise ImageInputError(
            "image_size_exceeded",
            f"The uploaded image exceeds the {MAX_UPLOAD_BYTES}-byte limit.",
            status_code=413,
        )
    return content


def model_sections() -> dict[str, Any]:
    return {
        "image": {
            "runtime": image_service.runtime,
            "runtime_version": image_service.runtime_version,
            "model_version": image_service.metadata.get("model", {}).get("version"),
            "sha256": image_service.model_sha256,
            "class_order": image_service.labels,
        },
        "text": {
            "model_version": nlp_service.model_version,
            "classes": nlp_service.classes,
        },
    }


def ai_review_reasons(
    image_result: dict[str, Any],
    text_result: dict[str, Any] | None = None,
    fusion_result: dict[str, Any] | None = None,
    text_report: str | None = None,
) -> list[str]:
    reasons: list[str] = []
    image_prediction = image_result.get("prediction")
    image_confidence = float(image_result.get("confidence", 0.0))
    if not image_prediction:
        reasons.append("no_image_detection")
    elif image_prediction not in image_service.EXPECTED_LABELS:
        reasons.append("unsupported_category")
    elif image_confidence < 0.60:
        reasons.append("low_image_confidence")

    if text_result is not None:
        text_prediction = text_result.get("prediction")
        text_confidence = float(text_result.get("confidence", 0.0))
        if text_report is not None and len(" ".join(text_report.split())) < 10:
            reasons.append("insufficient_text")
        if text_confidence < 0.70:
            reasons.append("low_text_confidence")
        if text_prediction not in {*image_service.EXPECTED_LABELS, "no_violation", None}:
            reasons.append("unsupported_category")
        if (
            image_prediction in image_service.EXPECTED_LABELS
            and text_prediction in image_service.EXPECTED_LABELS
            and image_prediction != text_prediction
        ):
            reasons.append("image_text_disagreement")

    if fusion_result is not None and float(fusion_result.get("final_confidence", 0.0)) < 0.70:
        reasons.append("insufficient_fusion_confidence")

    approved_order = (
        "no_image_detection",
        "low_image_confidence",
        "low_text_confidence",
        "image_text_disagreement",
        "unsupported_category",
        "insufficient_text",
        "insufficient_fusion_confidence",
    )
    return [reason for reason in approved_order if reason in reasons]


def gis_section(location_result: dict[str, Any]) -> dict[str, Any]:
    return {
        "inside_santa_cruz": location_result["inside_santa_cruz"],
        "municipality_name": location_result["municipality_name"],
        "barangay": location_result["barangay"],
        "barangay_assignment_status": location_result["barangay_detection_status"],
        "needs_manual_barangay_review": location_result["needs_manual_barangay_review"],
        "location_context": location_result["location_context"],
    }


@app.get("/health")
def health() -> dict:
    required_loaded = (
        image_service.loaded
        and nlp_service.loaded
        and gis_service.municipal_boundary is not None
    )
    return {
        "status": "ok" if required_loaded else "degraded",
        "liveness": "alive",
        "components": {
            "image": image_service.safe_status(),
            "nlp": {
                "loaded": nlp_service.loaded,
                "model_version": nlp_service.model_version,
                "classes": nlp_service.classes,
                "error_code": None if nlp_service.loaded else "nlp_model_unavailable",
            },
            "fusion": {"loaded": fusion_service is not None},
            "gis": {
                "municipal_boundary_loaded": gis_service.municipal_boundary is not None,
                "barangay_boundaries_loaded": gis_service.barangay_boundaries is not None,
                "barangay_routing_status": (
                    "available"
                    if gis_service.barangay_boundaries
                    and gis_service.barangay_boundaries.get("features")
                    else "barangay_boundary_unavailable"
                ),
            },
        },
        # Retained for Phase 6 clients while the structured component object is adopted.
        "nlp_model_loaded": nlp_service.loaded,
        "nlp_model_version": nlp_service.model_version,
        "nlp_model_classes": nlp_service.classes,
        "municipal_boundary_loaded": gis_service.municipal_boundary is not None,
        "barangay_boundaries_loaded": gis_service.barangay_boundaries is not None,
    }


@app.get("/ready")
def ready() -> JSONResponse:
    missing: list[str] = []
    if not image_service.loaded:
        missing.append("image_model")
    if not nlp_service.loaded:
        missing.append("nlp_model")
    if fusion_service is None:
        missing.append("fusion_service")
    if gis_service.municipal_boundary is None:
        missing.append("municipal_boundary")

    status_code = 200 if not missing else 503
    return JSONResponse(
        status_code=status_code,
        content={
            "ready": not missing,
            "missing_required_components": missing,
            "barangay_routing_status": (
                "available"
                if gis_service.barangay_boundaries
                and gis_service.barangay_boundaries.get("features")
                else "barangay_boundary_unavailable"
            ),
        },
    )


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


@app.post("/v1/predict/image", response_model=None)
async def predict_image_v1(image: UploadFile = File(...)) -> JSONResponse | dict[str, Any]:
    if not image_service.loaded:
        return operational_error("image_model_unavailable", "Image inference is unavailable.", 503)
    content_type = image.content_type
    image_bytes = await read_bounded_upload(image)
    try:
        result = await run_in_threadpool(image_service.predict, image_bytes, content_type)
    except ImageInputError:
        raise
    except ModelContractError:
        return operational_error("image_model_contract_error", "Image-model output was invalid.", 500)
    except Exception:
        return operational_error("image_inference_failed", "Image inference failed.", 500)
    reasons = ai_review_reasons(result)
    return {
        "image": result,
        "models": {"image": model_sections()["image"]},
        "timing": result["timing_ms"],
        "review": {
            "ai_needs_manual_review": bool(reasons),
            "ai_manual_review_reasons": reasons,
        },
    }


@app.post("/v1/predict/multimodal", response_model=None)
async def predict_multimodal_v1(
    image: UploadFile = File(...),
    text_report: str = Form(...),
    latitude: float = Form(...),
    longitude: float = Form(...),
    barangay_hint: str | None = Form(default=None),
) -> JSONResponse | dict[str, Any]:
    del barangay_hint  # Compatibility hint is deliberately non-authoritative.
    if not image_service.loaded:
        return operational_error("image_model_unavailable", "Image inference is unavailable.", 503)
    if not nlp_service.loaded:
        return operational_error("nlp_model_unavailable", "Text inference is unavailable.", 503)
    if gis_service.municipal_boundary is None:
        return operational_error(
            "municipal_boundary_unavailable",
            "Municipal jurisdiction validation is unavailable.",
            503,
        )
    if not -90 <= latitude <= 90 or not -180 <= longitude <= 180:
        return operational_error("invalid_coordinates", "Coordinates are outside valid ranges.", 422)
    if not text_report.strip():
        return operational_error("invalid_text_report", "text_report must not be blank.", 422)
    if len(text_report) > 5000:
        return operational_error("text_report_too_long", "text_report exceeds 5000 characters.", 422)

    request_started = time.perf_counter()
    content_type = image.content_type
    image_bytes = await read_bounded_upload(image)
    try:
        image_result = await run_in_threadpool(image_service.predict, image_bytes, content_type)
        text_result = nlp_service.predict(text_report)
        # The optional barangay hint cannot override polygon evidence or establish jurisdiction.
        location_result = gis_service.validate(latitude, longitude, None)
        fusion_result = fusion_service.fuse(
            image_result["prediction"],
            image_result["confidence"],
            text_result,
            location_result,
        )
    except ImageInputError:
        raise
    except ModelContractError:
        return operational_error("image_model_contract_error", "Image-model output was invalid.", 500)
    except Exception:
        return operational_error("inference_failed", "An inference component failed.", 500)

    reasons = ai_review_reasons(image_result, text_result, fusion_result, text_report)
    return {
        "image": image_result,
        "text": text_result,
        "gis": gis_section(location_result),
        "fusion": {
            "final_violation_type": fusion_result["final_violation_type"],
            "final_confidence": fusion_result["final_confidence"],
            "decision_source": fusion_result["decision_source"],
        },
        "models": model_sections(),
        "timing": {
            "image": image_result["timing_ms"],
            "total_ms": round((time.perf_counter() - request_started) * 1000, 3),
        },
        "review": {
            "ai_needs_manual_review": bool(reasons),
            "ai_manual_review_reasons": reasons,
        },
    }
