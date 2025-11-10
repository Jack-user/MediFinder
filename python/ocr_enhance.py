#!/usr/bin/env python3
"""
Enhanced OCR pipeline using OpenCV, Pillow, NumPy, and pytesseract.

Usage:
    python ocr_enhance.py --input /path/to/image.png [--lang eng] [--psm 6] [--oem 3]

Outputs JSON to stdout:
    {
        "text": "...",
        "confidence": 87.3,
        "warnings": [],
        "steps": [
            "grayscale",
            "denoise_bilateral",
            ...
        ]
    }
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
import tempfile
from typing import Dict, List, Optional, Tuple

import cv2  # type: ignore
import numpy as np
from PIL import Image
import pytesseract


def _load_image(path: str) -> np.ndarray:
    image = cv2.imread(path)
    if image is None:
        raise ValueError(f"Could not read image: {path}")
    return image


def _deskew(gray: np.ndarray) -> Tuple[np.ndarray, Optional[float]]:
    coords = np.column_stack(np.where(gray < 255))
    if len(coords) == 0:
        return gray, None

    angle = cv2.minAreaRect(coords)[-1]
    if angle < -45:
        angle = -(90 + angle)
    else:
        angle = -angle

    if abs(angle) < 0.1:
        return gray, None

    (h, w) = gray.shape[:2]
    center = (w // 2, h // 2)
    m = cv2.getRotationMatrix2D(center, angle, 1.0)
    rotated = cv2.warpAffine(gray, m, (w, h), flags=cv2.INTER_CUBIC, borderMode=cv2.BORDER_REPLICATE)
    return rotated, float(angle)


def _gamma_correct(image: np.ndarray, gamma: float) -> np.ndarray:
    inv_gamma = 1.0 / max(gamma, 1e-6)
    table = np.array([(i / 255.0) ** inv_gamma * 255 for i in np.arange(0, 256)]).astype("uint8")
    return cv2.LUT(image, table)


def _enhance_contrast(gray: np.ndarray) -> Tuple[np.ndarray, List[str]]:
    steps: List[str] = []

    # Denoise while preserving edges
    denoised = cv2.bilateralFilter(gray, d=9, sigmaColor=75, sigmaSpace=75)
    steps.append("denoise_bilateral")

    # Adaptive histogram equalization for contrast
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    contrast = clahe.apply(denoised)
    steps.append("clahe_contrast")

    # Remove uneven illumination / shadows
    morph_kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (15, 15))
    tophat = cv2.morphologyEx(contrast, cv2.MORPH_TOPHAT, morph_kernel)
    blackhat = cv2.morphologyEx(contrast, cv2.MORPH_BLACKHAT, morph_kernel)
    shadow_corrected = cv2.add(contrast, tophat)
    shadow_corrected = cv2.subtract(shadow_corrected, blackhat)
    steps.append("shadow_suppression")

    # Sharpen text edges via unsharp masking
    blur = cv2.GaussianBlur(shadow_corrected, (0, 0), sigmaX=1.0, sigmaY=1.0)
    sharpened = cv2.addWeighted(shadow_corrected, 1.5, blur, -0.5, 0)
    steps.append("unsharp_mask")

    return sharpened, steps


def _sauvola_threshold(gray: np.ndarray, window: int = 25, k: float = 0.2, r: float = 128.0) -> np.ndarray:
    window = max(3, window | 1)  # ensure odd >=3
    gray_f = gray.astype(np.float32)

    mean = cv2.boxFilter(gray_f, ddepth=-1, ksize=(window, window), borderType=cv2.BORDER_REPLICATE)
    sqmean = cv2.boxFilter(gray_f ** 2, ddepth=-1, ksize=(window, window), borderType=cv2.BORDER_REPLICATE)
    variance = np.maximum(sqmean - mean ** 2, 0.0)
    std = np.sqrt(variance)

    thresh = mean * (1.0 + k * ((std / r) - 1.0))
    binary = np.where(gray_f > thresh, 255, 0).astype(np.uint8)
    return binary


def _boost_edges(gray: np.ndarray) -> Tuple[np.ndarray, List[str]]:
    steps: List[str] = []
    laplace = cv2.Laplacian(gray, cv2.CV_16S, ksize=3)
    steps.append("laplacian_edge")
    abs_laplace = cv2.convertScaleAbs(laplace)
    boosted = cv2.addWeighted(gray, 0.85, abs_laplace, 0.35, 0)
    steps.append("edge_boost")
    return boosted, steps


def _build_variant(
    name: str,
    binary: np.ndarray,
    base_steps: List[str],
    base_warnings: List[str],
    *,
    closing_kernel: Tuple[int, int] = (2, 2),
    opening_kernel: Optional[Tuple[int, int]] = None,
) -> Dict[str, object]:
    steps = list(base_steps)
    warnings = list(base_warnings)

    processed = binary
    if opening_kernel:
        open_kernel = cv2.getStructuringElement(cv2.MORPH_RECT, opening_kernel)
        processed = cv2.morphologyEx(processed, cv2.MORPH_OPEN, open_kernel, iterations=1)
        steps.append(f"morph_open_{opening_kernel[0]}x{opening_kernel[1]}")

    if closing_kernel:
        close_kernel = cv2.getStructuringElement(cv2.MORPH_RECT, closing_kernel)
        processed = cv2.morphologyEx(processed, cv2.MORPH_CLOSE, close_kernel, iterations=1)
        steps.append(f"morph_close_{closing_kernel[0]}x{closing_kernel[1]}")

    # Median blur to cleanup salt-and-pepper noise
    processed = cv2.medianBlur(processed, 3)
    steps.append("median_blur_3")

    processed = cv2.bitwise_not(processed)
    steps.append("invert_binary")

    height, width = processed.shape
    if min(height, width) < 600:
        scale = 600 / float(min(height, width))
        new_size = (int(width * scale), int(height * scale))
        processed = cv2.resize(processed, new_size, interpolation=cv2.INTER_LINEAR)
        steps.append(f"scale_{scale:.2f}x")

    mean_intensity = np.mean(processed)
    if mean_intensity > 245 or mean_intensity < 10:
        warnings.append("Image appears almost blank after preprocessing; check exposure or contrast.")

    return {
        "name": name,
        "image": processed,
        "steps": steps,
        "warnings": warnings,
    }


def _color_channel_variants(image: np.ndarray, base_steps: List[str], base_warnings: List[str]) -> List[Dict[str, object]]:
    variants: List[Dict[str, object]] = []

    lab = cv2.cvtColor(image, cv2.COLOR_BGR2LAB)
    l_channel, _, _ = cv2.split(lab)
    clahe = cv2.createCLAHE(clipLimit=2.5, tileGridSize=(8, 8))
    l_equal = clahe.apply(l_channel)

    _, l_binary = cv2.threshold(l_equal, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    variants.append(
        _build_variant(
            "lab_l_otsu",
            l_binary,
            base_steps + ["lab_l_channel", "clahe_contrast_l", "otsu_threshold_l"],
            base_warnings,
            closing_kernel=(3, 3),
        )
    )

    hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)
    v_channel = hsv[:, :, 2]
    v_gamma = _gamma_correct(v_channel, gamma=1.4)
    _, v_binary = cv2.threshold(v_gamma, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    variants.append(
        _build_variant(
            "hsv_v_otsu",
            v_binary,
            base_steps + ["hsv_v_channel", "gamma_1.4", "otsu_threshold_v"],
            base_warnings,
            opening_kernel=(2, 2),
        )
    )

    return variants


def _auto_orient(image: np.ndarray) -> Tuple[np.ndarray, List[str], List[str]]:
    steps: List[str] = []
    warnings: List[str] = []

    try:
        rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        pil = Image.fromarray(rgb)
        osd = pytesseract.image_to_osd(pil)
        match = re.search(r"Rotate:\s+(\d+)", osd)
        if match:
            angle = int(match.group(1)) % 360
            if angle == 90:
                image = cv2.rotate(image, cv2.ROTATE_90_CLOCKWISE)
                steps.append("auto_orient_90")
            elif angle == 180:
                image = cv2.rotate(image, cv2.ROTATE_180)
                steps.append("auto_orient_180")
            elif angle == 270:
                image = cv2.rotate(image, cv2.ROTATE_90_COUNTERCLOCKWISE)
                steps.append("auto_orient_270")
        orientation_conf = re.search(r"Orientation confidence:\s+([\d.]+)", osd)
        if orientation_conf:
            steps.append(f"osd_confidence_{float(orientation_conf.group(1)):.1f}")
    except pytesseract.TesseractError:
        warnings.append("Automatic orientation check failed (Tesseract OSD).")
    except Exception as exc:  # noqa: BLE001
        warnings.append(f"Auto orientation unavailable: {exc}")

    return image, steps, warnings


def _preprocess_variants(
    image: np.ndarray,
    *,
    initial_steps: Optional[List[str]] = None,
    initial_warnings: Optional[List[str]] = None,
) -> List[Dict[str, object]]:
    variants: List[Dict[str, object]] = []

    common_steps = list(initial_steps or [])
    common_warnings = list(initial_warnings or [])

    # Convert to grayscale
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    common_steps.append("grayscale")

    gray, enhance_steps = _enhance_contrast(gray)
    common_steps.extend(enhance_steps)

    # Deskew
    gray, angle = _deskew(gray)
    if angle is not None:
        common_steps.append(f"deskew_{angle:.2f}_deg")

    # Variant A: Adaptive Gaussian threshold (default)
    adaptive_gauss = cv2.adaptiveThreshold(
        gray,
        255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY,
        31,
        10,
    )
    variants.append(
        _build_variant(
            "adaptive_gaussian",
            adaptive_gauss,
            common_steps + ["adaptive_threshold_gaussian"],
            common_warnings,
        )
    )

    # Variant B: Adaptive mean threshold with smaller window
    adaptive_mean = cv2.adaptiveThreshold(
        gray,
        255,
        cv2.ADAPTIVE_THRESH_MEAN_C,
        cv2.THRESH_BINARY,
        21,
        8,
    )
    variants.append(
        _build_variant(
            "adaptive_mean",
            adaptive_mean,
            common_steps + ["adaptive_threshold_mean"],
            common_warnings,
            opening_kernel=(1, 3),
        )
    )

    # Variant C: Otsu threshold on Gaussian blurred image
    gaussian_blur = cv2.GaussianBlur(gray, (5, 5), 0)
    _, otsu = cv2.threshold(gaussian_blur, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    variants.append(
        _build_variant(
            "otsu_global",
            otsu,
            common_steps + ["gaussian_blur_5x5", "otsu_threshold"],
            common_warnings,
            closing_kernel=(3, 3),
        )
    )

    # Variant D: Morphological gradient emphasis to boost faint strokes
    gradient_kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3))
    gradient = cv2.morphologyEx(gray, cv2.MORPH_GRADIENT, gradient_kernel)
    _, gradient_thresh = cv2.threshold(gradient, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    variants.append(
        _build_variant(
            "gradient_enhanced",
            gradient_thresh,
            common_steps + ["morph_gradient", "otsu_threshold_gradient"],
            common_warnings,
            closing_kernel=(2, 2),
        )
    )

    # Variant E: Sauvola adaptive thresholding for textured backgrounds
    sauvola = _sauvola_threshold(gray, window=31, k=0.18)
    variants.append(
        _build_variant(
            "sauvola_local",
            sauvola,
            common_steps + ["sauvola_threshold"],
            common_warnings,
            opening_kernel=(1, 5),
            closing_kernel=(3, 3),
        )
    )

    # Variant F: Edge-boosted + Otsu for faint strokes
    boosted, boost_steps = _boost_edges(gray)
    boosted_thresh = cv2.adaptiveThreshold(
        boosted,
        255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY,
        41,
        12,
    )
    variants.append(
        _build_variant(
            "edge_boosted_gaussian",
            boosted_thresh,
            common_steps + boost_steps + ["adaptive_threshold_gaussian_large"],
            common_warnings,
            closing_kernel=(1, 5),
        )
    )

    # Variants G/H: Color channel specific binarization
    variants.extend(_color_channel_variants(image, common_steps, common_warnings))

    return variants


def _run_ocr(
    processed: np.ndarray,
    *,
    lang: str,
    psm: int,
    oem: int,
) -> Tuple[str, Optional[float]]:
    pil_image = Image.fromarray(processed)

    custom_config = f"--oem {oem} --psm {psm}"
    text = pytesseract.image_to_string(pil_image, config=custom_config, lang=lang)

    try:
        data = pytesseract.image_to_data(
            pil_image,
            config=custom_config,
            lang=lang,
            output_type=pytesseract.Output.DICT,
        )
        confidences = [float(c) for c in data.get("conf", []) if c not in ("-1", None)]
        confidence = float(np.mean(confidences)) if confidences else None
    except pytesseract.TesseractError:
        confidence = None

    return text.strip(), confidence


def _save_debug_image(image: np.ndarray) -> Optional[str]:
    try:
        handle, path = tempfile.mkstemp(prefix="ocr_debug_", suffix=".png")
        os.close(handle)
        cv2.imwrite(path, image)
        return path
    except Exception:
        return None


def enhance_ocr(
    path: str,
    *,
    lang: str = "eng",
    psm: int = 6,
    oem: int = 3,
    save_debug: bool = True,
    auto_orient: bool = True,
) -> Dict[str, object]:
    original = _load_image(path)
    orientation_steps: List[str] = []
    orientation_warnings: List[str] = []
    if auto_orient:
        original, orientation_steps, orientation_warnings = _auto_orient(original)
    variants = _preprocess_variants(
        original,
        initial_steps=orientation_steps,
        initial_warnings=orientation_warnings,
    )

    all_variant_results: List[Dict[str, object]] = []
    best_index = 0
    best_score = float("-inf")

    for idx, variant in enumerate(variants):
        text, confidence = _run_ocr(
            variant["image"],  # type: ignore[dict-item]
            lang=lang,
            psm=psm,
            oem=oem,
        )
        score = confidence if confidence is not None else 0.0
        if confidence is None and text:
            score = len(text) * 0.01  # heuristic fallback

        all_variant_results.append(
            {
                "name": variant["name"],
                "text": text,
                "confidence": confidence,
                "steps": variant["steps"],
                "warnings": variant["warnings"],
            }
        )

        if score > best_score:
            best_score = score
            best_index = idx

    best_variant = all_variant_results[best_index]
    if save_debug:
        debug_path = _save_debug_image(variants[best_index]["image"])  # type: ignore[dict-item]
        if debug_path:
            best_variant["steps"] = list(best_variant["steps"]) + [f"debug_saved:{debug_path}"]

    result: Dict[str, object] = {
        "text": best_variant["text"],
        "confidence": best_variant["confidence"],
        "warnings": best_variant["warnings"],
        "steps": best_variant["steps"],
        "best_variant": best_variant["name"],
        "alternatives": [
            {
                "name": v["name"],
                "confidence": v["confidence"],
                "text_preview": (v["text"][:120] + "…") if v["text"] and len(v["text"]) > 120 else v["text"],
            }
            for v in all_variant_results
        ],
    }
    return result


def main(argv: Optional[List[str]] = None) -> int:
    parser = argparse.ArgumentParser(description="Enhanced OCR pipeline wrapper.")
    parser.add_argument("--input", required=True, help="Path to the input image file")
    parser.add_argument("--tesseract-path", help="Override pytesseract TESSDATA_PREFIX/Tesseract path")
    parser.add_argument("--no-debug-image", action="store_true", help="Skip saving debug image")
    parser.add_argument("--no-auto-orient", action="store_true", help="Disable automatic orientation detection via Tesseract OSD")
    parser.add_argument("--lang", default="eng", help="Language(s) to use with Tesseract (comma separated)")
    parser.add_argument("--psm", type=int, default=6, help="Page segmentation mode for Tesseract")
    parser.add_argument("--oem", type=int, default=3, help="OCR Engine mode for Tesseract")

    args = parser.parse_args(argv)

    if args.tesseract_path:
        pytesseract.pytesseract.tesseract_cmd = args.tesseract_path

    try:
        result = enhance_ocr(
            args.input,
            lang=args.lang,
            psm=args.psm,
            oem=args.oem,
            save_debug=not args.no_debug_image,
            auto_orient=not args.no_auto_orient,
        )

        if args.no_debug_image:
            result["steps"] = [step for step in result["steps"] if not step.startswith("debug_saved:")]

        print(json.dumps({"success": True, **result}))
        return 0
    except Exception as exc:  # noqa: BLE001
        print(json.dumps({"success": False, "error": str(exc)}))
        return 1


if __name__ == "__main__":
    sys.exit(main())

