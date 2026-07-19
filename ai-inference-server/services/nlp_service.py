from __future__ import annotations

import hashlib
import logging
from pathlib import Path
from typing import Any

import joblib

LOGGER = logging.getLogger(__name__)

SUPPORTED_VIOLATIONS = {
    "construction_materials",
    "garbage_debris",
    "illegal_parking",
    "road_obstruction",
    "sidewalk_obstruction",
}
MODEL_LABEL_MAPPING = {
    "construction_materials": "construction_materials",
    "garbage_debris": "garbage_debris",
    "illegal_parking": "illegal_parking",
    "road_obstruction": "road_obstruction",
    "sidewalk_obstruction": "sidewalk_obstruction",
    # This is intentionally retained as a non-violation signal. Mapping it to a
    # violation would fabricate a DILG classification.
    "no_violation": "no_violation",
}


class NLPService:
    def __init__(self, model_path: Path, review_threshold: float = 0.70) -> None:
        self.model_path = model_path
        self.review_threshold = review_threshold
        self.model: Any | None = None
        self.load_error: str | None = None
        self.model_version = "temporary-rule-fallback"
        self.classes: list[str] = []

    @property
    def loaded(self) -> bool:
        return self.model is not None

    def load(self) -> None:
        try:
            model = joblib.load(self.model_path)
            if not hasattr(model, "predict") or not hasattr(model, "predict_proba"):
                raise TypeError("The artifact does not expose predict and predict_proba.")

            classes = [str(label) for label in model.classes_]
            unknown = set(classes) - set(MODEL_LABEL_MAPPING)
            if unknown:
                raise ValueError(f"Unmapped trained labels: {sorted(unknown)}")

            digest = hashlib.sha256(self.model_path.read_bytes()).hexdigest()
            self.model = model
            self.classes = classes
            self.model_version = f"civiclear-nlp-{digest[:12]}"
            self.load_error = None
            LOGGER.info("Loaded NLP model %s with classes %s", self.model_version, classes)
        except Exception as exc:  # fallback is authorized only for a genuine load failure
            self.model = None
            self.classes = []
            self.load_error = str(exc)
            LOGGER.exception("NLP model load failed; temporary fallback enabled.")

    def predict(self, text_report: str) -> dict[str, Any]:
        cleaned = " ".join(text_report.split())
        if not cleaned:
            raise ValueError("text_report must not be blank")

        if self.model is None:
            return self._fallback_predict(cleaned)

        probabilities = self.model.predict_proba([cleaned])[0]
        best_index = int(probabilities.argmax())
        trained_label = str(self.model.classes_[best_index])
        prediction = MODEL_LABEL_MAPPING[trained_label]
        confidence = round(float(probabilities[best_index]), 4)

        return {
            "prediction": prediction,
            "confidence": confidence,
            "model_version": self.model_version,
            "decision_source": "trained_nlp_model",
            "needs_manual_review": confidence < self.review_threshold or prediction == "no_violation",
        }

    def _fallback_predict(self, text_report: str) -> dict[str, Any]:
        text = text_report.casefold()
        rules = (
            ("construction_materials", ("hollow block", "buhangin", "construction", "semento")),
            ("garbage_debris", ("basura", "garbage", "debris", "tambak")),
            ("illegal_parking", ("nakaparada", "sasakyan", "parking", "vehicle")),
            ("sidewalk_obstruction", ("bangketa", "sidewalk", "vendor")),
            ("road_obstruction", ("nakaharang", "obstruction", "harang", "kalsada")),
        )
        prediction = next((label for label, words in rules if any(word in text for word in words)), None)
        return {
            "prediction": prediction,
            "confidence": 0.35 if prediction else 0.0,
            "model_version": self.model_version,
            "decision_source": "temporary_rule_fallback",
            "needs_manual_review": True,
        }
