# DILG-RC Local AI Inference Server

One local FastAPI service for the trained CivicClear NLP model, Santa Cruz municipal
coverage validation, and image/text fusion. Image inference stays on the Android device.

## Model

- Format: scikit-learn joblib pipeline
- Vectorizer: `TfidfVectorizer`
- Classifier/label encoder: `LogisticRegression.classes_`
- Training serialization version: scikit-learn 1.9.0
- Artifact SHA-256: `eef576aa7b257b60674548c1e0322a9f8872cb543869314bae54bb445d238f6b`
- Classes: `construction_materials`, `garbage_debris`, `illegal_parking`,
  `no_violation`, `road_obstruction`, `sidewalk_obstruction`

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

Endpoints: `GET /health`, `POST /predict/text`, `POST /predict/location`, and
`POST /predict/multimodal`. This server is local-only for Phase 6A-6D.

Run tests with `pytest`.
