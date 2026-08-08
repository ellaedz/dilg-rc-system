# DILG-RC Local AI Inference Server

The Phase 8C FastAPI service owns image inference, trained-text inference, Santa Cruz
municipal validation, and advisory fusion. The previous Android TFLite implementation
is intentionally retained until later mobile parity and migration work.

## Models

- Image: YOLOv8s Float16 TFLite weights with Float32 input/output, run by
  `ai-edge-litert==2.1.6`.
- Image input: RGB Float32 NHWC `[1, 640, 640, 3]`, normalized by `/255`, with
  aspect-ratio-preserving 640-pixel letterboxing using RGB `(114, 114, 114)`.
- Image output: raw Float32 `[1, 9, 8400]` with normalized xywh coordinates, decoded
  with a 0.25 candidate threshold, per-class 0.45 IoU NMS, and 20-detection maximum.
  Results below 0.60 remain low-confidence evidence requiring manual review.
- Text: scikit-learn 1.9.0 joblib pipeline using TF-IDF and logistic regression.

`no_violation` remains an explicit non-violation signal and always requires staff
review. It is never falsely converted into a violation class.

## Run

```powershell
cd ai-inference-server
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 9000 --reload
```

New multipart endpoints:

- `POST /v1/predict/image`
- `POST /v1/predict/multimodal` with `image`, `text_report`, `latitude`, `longitude`,
  and optional non-authoritative `barangay_hint`

Compatibility endpoints remain available:

- `POST /predict/text`
- `POST /predict/location`
- `POST /predict/multimodal`

`GET /health` always provides process liveness and safe component status. `GET /ready`
returns HTTP 503 if image inference, NLP, fusion, or the municipal boundary is
unavailable. Missing barangay polygons are reported as a routing limitation but do not
make inference unready.

Run tests with:

```powershell
pytest -q -p no:cacheprovider
```
