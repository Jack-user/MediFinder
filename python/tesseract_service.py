#!/usr/bin/env python3
"""
FastAPI-based wrapper around Tesseract OCR.

Run:
    uvicorn python.tesseract_service:app --host 127.0.0.1 --port 8001

Environment:
    TESSDATA_PREFIX should point to the tessdata directory if not in default path.
"""
from __future__ import annotations

import os
from typing import List, Optional

import cv2  # type: ignore
import numpy as np
import pytesseract
from fastapi import FastAPI, File, HTTPException, UploadFile
from pydantic import BaseModel
from starlette.responses import JSONResponse
from PIL import Image

app = FastAPI(title="Local Tesseract OCR Service")


class OCRResponse(BaseModel):
    text: str
    confidence: Optional[float]
    warnings: List[str] = []


def _ensure_tesseract_path() -> None:
    """
    Allow overriding the tesseract binary via env.
    """
    tesseract_cmd = os.getenv("TESSERACT_CMD")
    if tesseract_cmd:
        pytesseract.pytesseract.tesseract_cmd = tesseract_cmd


def _decode_image(data: bytes) -> np.ndarray:
    """
    Decode raw bytes into a BGR image using OpenCV.
    """
    buffer = np.frombuffer(data, dtype=np.uint8)
    image = cv2.imdecode(buffer, cv2.IMREAD_COLOR)
    if image is None:
        raise ValueError("Unsupported or corrupt image data.")
    return image


def _run_ocr(image: np.ndarray, *, lang: str, psm: int, oem: int) -> OCRResponse:
    pil_image = Image.fromarray(cv2.cvtColor(image, cv2.COLOR_BGR2RGB))

    config = f"--oem {oem} --psm {psm}"
    text = pytesseract.image_to_string(pil_image, config=config, lang=lang)

    confidence: Optional[float]
    try:
        data = pytesseract.image_to_data(
            pil_image,
            config=config,
            lang=lang,
            output_type=pytesseract.Output.DICT,
        )
        confidences = [float(c) for c in data.get("conf", []) if c not in ("-1", None)]
        confidence = float(np.mean(confidences)) if confidences else None
    except pytesseract.TesseractError:
        confidence = None

    return OCRResponse(text=text.strip(), confidence=confidence)


@app.post("/ocr", response_model=OCRResponse)
async def ocr_endpoint(
    file: UploadFile = File(...),
    lang: str = "eng",
    psm: int = 6,
    oem: int = 3,
) -> JSONResponse:
    _ensure_tesseract_path()

    try:
        contents = await file.read()
        image = _decode_image(contents)
        result = _run_ocr(image, lang=lang, psm=psm, oem=oem)
        payload = result.model_dump() if hasattr(result, "model_dump") else result.dict()
        return JSONResponse(content=payload)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    except pytesseract.TesseractNotFoundError as exc:
        raise HTTPException(status_code=500, detail="Tesseract binary not available on server.") from exc
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=500, detail=f"OCR failed: {exc}") from exc


__all__ = ["app"]

