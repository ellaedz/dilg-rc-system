import io

import numpy as np
import pytest
from PIL import Image

from services.image_preprocessing import LETTERBOX_COLOR, preprocess_image


def image_bytes(size: tuple[int, int], color=(255, 0, 0), image_format="PNG") -> bytes:
    buffer = io.BytesIO()
    Image.new("RGB", size, color).save(buffer, format=image_format)
    return buffer.getvalue()


@pytest.mark.parametrize(
    ("size", "resized", "padding"),
    [
        ((800, 400), (640, 320), (0, 160)),
        ((400, 800), (320, 640), (160, 0)),
        ((500, 500), (640, 640), (0, 0)),
    ],
)
def test_preprocessing_contract_for_common_aspect_ratios(size, resized, padding):
    tensor, transform = preprocess_image(image_bytes(size), "image/png")

    assert tensor.shape == (1, 640, 640, 3)
    assert tensor.dtype == np.float32
    assert 0.0 <= float(tensor.min()) <= float(tensor.max()) <= 1.0
    assert (transform.resized_width, transform.resized_height) == resized
    assert (transform.pad_x, transform.pad_y) == padding


def test_letterbox_uses_approved_color_and_rgb_normalization():
    tensor, transform = preprocess_image(image_bytes((800, 400)), "image/png")

    np.testing.assert_allclose(tensor[0, 0, 0], np.array(LETTERBOX_COLOR) / 255.0)
    np.testing.assert_allclose(tensor[0, transform.pad_y + 10, 10], [1.0, 0.0, 0.0])


def test_exif_orientation_is_applied_before_letterboxing():
    source = Image.new("RGB", (40, 20), (20, 30, 40))
    exif = source.getexif()
    exif[274] = 6
    buffer = io.BytesIO()
    source.save(buffer, format="JPEG", exif=exif)

    _, transform = preprocess_image(buffer.getvalue(), "image/jpeg")

    assert (transform.original_width, transform.original_height) == (20, 40)
