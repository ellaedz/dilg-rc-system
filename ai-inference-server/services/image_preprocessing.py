from __future__ import annotations

import io
import math
import warnings
from dataclasses import dataclass

import numpy as np
from PIL import Image, ImageOps, UnidentifiedImageError

INPUT_SIZE = 640
LETTERBOX_COLOR = (114, 114, 114)
ALLOWED_FORMATS = {"JPEG", "PNG"}
ALLOWED_CONTENT_TYPES = {"image/jpeg", "image/png"}
CONTENT_TYPE_FORMATS = {"image/jpeg": "JPEG", "image/png": "PNG"}
MAX_IMAGE_PIXELS = 20_000_000
Image.MAX_IMAGE_PIXELS = MAX_IMAGE_PIXELS


class ImageInputError(ValueError):
    def __init__(self, code: str, message: str, status_code: int = 422) -> None:
        super().__init__(message)
        self.code = code
        self.message = message
        self.status_code = status_code


@dataclass(frozen=True)
class LetterboxTransform:
    original_width: int
    original_height: int
    resized_width: int
    resized_height: int
    pad_x: int
    pad_y: int
    scale: float


def _positive_round(value: float) -> int:
    """Match JavaScript Math.round for the positive dimensions used here."""
    return math.floor(value + 0.5)


def preprocess_image(
    image_bytes: bytes,
    declared_content_type: str | None,
) -> tuple[np.ndarray, LetterboxTransform]:
    if not image_bytes:
        raise ImageInputError("empty_image", "The uploaded image is empty.")
    if declared_content_type not in ALLOWED_CONTENT_TYPES:
        raise ImageInputError(
            "unsupported_image_type",
            "Only JPEG and PNG uploads are supported.",
            status_code=415,
        )

    try:
        with warnings.catch_warnings():
            warnings.simplefilter("error", Image.DecompressionBombWarning)
            with Image.open(io.BytesIO(image_bytes)) as source:
                source.verify()
            with Image.open(io.BytesIO(image_bytes)) as source:
                if source.format not in ALLOWED_FORMATS:
                    raise ImageInputError(
                        "unsupported_image_type",
                        "The decoded image is not JPEG or PNG.",
                        status_code=415,
                    )
                if source.format != CONTENT_TYPE_FORMATS[declared_content_type]:
                    raise ImageInputError(
                        "image_type_mismatch",
                        "The declared image type does not match the decoded image.",
                    )
                if source.width <= 0 or source.height <= 0:
                    raise ImageInputError("invalid_image", "The image has invalid dimensions.")
                oriented = ImageOps.exif_transpose(source)
                rgb = oriented.convert("RGB")
    except ImageInputError:
        raise
    except (UnidentifiedImageError, OSError, ValueError, Image.DecompressionBombError):
        raise ImageInputError("invalid_image", "The uploaded file is not a valid image.") from None
    except Image.DecompressionBombWarning:
        raise ImageInputError("image_dimensions_exceeded", "The image dimensions are too large.", 413) from None

    original_width, original_height = rgb.size
    scale = min(INPUT_SIZE / original_width, INPUT_SIZE / original_height)
    resized_width = min(INPUT_SIZE, max(1, _positive_round(original_width * scale)))
    resized_height = min(INPUT_SIZE, max(1, _positive_round(original_height * scale)))
    pad_x = (INPUT_SIZE - resized_width) // 2
    pad_y = (INPUT_SIZE - resized_height) // 2

    resized = rgb.resize((resized_width, resized_height), Image.Resampling.BILINEAR)
    canvas = Image.new("RGB", (INPUT_SIZE, INPUT_SIZE), LETTERBOX_COLOR)
    canvas.paste(resized, (pad_x, pad_y))
    tensor = np.asarray(canvas, dtype=np.float32) / np.float32(255.0)
    tensor = np.expand_dims(tensor, axis=0)

    return tensor, LetterboxTransform(
        original_width=original_width,
        original_height=original_height,
        resized_width=resized_width,
        resized_height=resized_height,
        pad_x=pad_x,
        pad_y=pad_y,
        scale=scale,
    )
