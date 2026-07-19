from __future__ import annotations

from typing import Any

from services.nlp_service import SUPPORTED_VIOLATIONS


class FusionService:
    STRONG_THRESHOLD = 0.70

    def fuse(
        self,
        image_prediction: str | None,
        image_confidence: float | None,
        text_result: dict[str, Any],
        location_result: dict[str, Any],
    ) -> dict[str, Any]:
        image_confidence = round(float(image_confidence or 0.0), 4)
        image_supported = image_prediction in SUPPORTED_VIOLATIONS
        text_prediction = text_result.get("prediction")
        text_confidence = round(float(text_result.get("confidence", 0.0)), 4)
        text_supported = text_prediction in SUPPORTED_VIOLATIONS
        image_strong = image_supported and image_confidence >= self.STRONG_THRESHOLD
        text_strong = text_supported and text_confidence >= self.STRONG_THRESHOLD

        if image_supported and text_supported and image_prediction == text_prediction:
            final_prediction = image_prediction
            final_confidence = round((image_confidence + text_confidence) / 2, 4)
            decision_source = "image_text_agreement"
            manual_review = not (image_strong or text_strong)
        elif image_strong and text_strong:
            final_prediction = image_prediction if image_confidence >= text_confidence else text_prediction
            final_confidence = max(image_confidence, text_confidence)
            decision_source = "strong_disagreement_manual_review"
            manual_review = True
        elif image_strong:
            final_prediction = image_prediction
            final_confidence = image_confidence
            decision_source = "image_priority"
            manual_review = False
        elif text_strong:
            final_prediction = text_prediction
            final_confidence = text_confidence
            decision_source = "nlp_priority"
            manual_review = False
        else:
            candidates = []
            if image_supported:
                candidates.append((image_prediction, image_confidence))
            if text_supported:
                candidates.append((text_prediction, text_confidence))
            final_prediction, final_confidence = max(candidates, key=lambda item: item[1]) if candidates else (None, 0.0)
            decision_source = "weak_evidence_manual_review"
            manual_review = True

        return {
            "final_violation_type": final_prediction,
            "final_confidence": round(float(final_confidence), 4),
            "decision_source": decision_source,
            "needs_manual_review": bool(manual_review or text_result.get("prediction") == "no_violation"),
            "text_result": text_result,
            "image_result": {
                "prediction": image_prediction,
                "confidence": image_confidence,
                "supported_class": image_supported,
                "model_location": "android_device",
            },
            "location_result": location_result,
        }
