"""
Face Recognition Flask Server — macOS M4 (Apple Silicon) Edition
Backend: DeepFace + Facenet512 (no dlib, no facenet-pytorch version conflicts)

Endpoints consumed by FaceAuthController.php:
  POST /extract-descriptor  — detect face in base64 image, return 512-d embedding
  POST /compare-faces       — cosine distance between two descriptors
  GET  /health              — liveness check
"""

import base64
import logging
import os

import cv2
import numpy as np
from flask import Flask, jsonify, request

app = Flask(__name__)
logging.basicConfig(level=logging.INFO, format="%(levelname)s %(message)s")
log = logging.getLogger(__name__)

# ── Config (all overridable via env vars) ─────────────────────────────────────
MODEL_NAME       = os.getenv("FACE_MODEL",     "Facenet512")
DETECTOR_BACKEND = os.getenv("FACE_DETECTOR",  "opencv")
THRESHOLD        = float(os.getenv("FACE_THRESHOLD", "0.40"))

log.info("Model: %s | Detector: %s | Threshold: %.2f",
         MODEL_NAME, DETECTOR_BACKEND, THRESHOLD)

# Import DeepFace after logging is set up so its own logs appear in order
from deepface import DeepFace  # noqa: E402


# ── Helpers ───────────────────────────────────────────────────────────────────

def _decode_image(b64: str) -> np.ndarray:
    """Accept raw base64 or data-URL, return BGR numpy array."""
    if "," in b64:
        b64 = b64.split(",", 1)[1]
    raw = base64.b64decode(b64)
    arr = np.frombuffer(raw, dtype=np.uint8)
    img = cv2.imdecode(arr, cv2.IMREAD_COLOR)
    if img is None:
        raise ValueError("Image buffer could not be decoded")
    return img


def _cosine_distance(v1: list, v2: list) -> float:
    a = np.array(v1, dtype=np.float32)
    b = np.array(v2, dtype=np.float32)
    na, nb = np.linalg.norm(a), np.linalg.norm(b)
    if na == 0 or nb == 0:
        raise ValueError("Zero-norm descriptor — invalid embedding")
    return float(1.0 - np.dot(a / na, b / nb))


# ── Routes ────────────────────────────────────────────────────────────────────

@app.route("/extract-descriptor", methods=["POST"])
def extract_descriptor():
    """
    Request  JSON: { "image": "<base64 or data-URL>" }
    Response JSON: { "success": true,  "descriptor": [...512 floats...] }
                   { "success": false, "error": "<reason>" }
    """
    data = request.get_json(silent=True) or {}
    b64 = data.get("image", "")
    if not b64:
        return jsonify(success=False, error="No image provided"), 400

    try:
        img = _decode_image(b64)
        result = DeepFace.represent(
            img_path=img,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR_BACKEND,
            enforce_detection=True,
        )
        descriptor = result[0]["embedding"]
        return jsonify(success=True, descriptor=descriptor)

    except ValueError as exc:
        return jsonify(success=False, error=str(exc))
    except Exception as exc:
        log.warning("extract-descriptor: %s", exc)
        return jsonify(success=False, error="No face detected")


@app.route("/compare-faces", methods=["POST"])
def compare_faces():
    """
    Request  JSON: { "descriptor1": [...], "descriptor2": [...] }
    Response JSON: { "success": true, "verified": bool, "distance": float }
                   { "success": false, "error": "<reason>" }
    """
    data = request.get_json(silent=True) or {}
    d1 = data.get("descriptor1")
    d2 = data.get("descriptor2")

    if d1 is None or d2 is None:
        return jsonify(success=False,
                       error="descriptor1 and descriptor2 are required"), 400

    try:
        distance = _cosine_distance(d1, d2)
        verified = bool(distance < THRESHOLD)
        log.info("compare-faces  distance=%.4f  threshold=%.2f  verified=%s",
                 distance, THRESHOLD, verified)
        return jsonify(success=True, verified=verified, distance=distance)
    except Exception as exc:
        return jsonify(success=False, error=str(exc)), 400


@app.route("/health", methods=["GET"])
def health():
    return jsonify(
        status="ok",
        model=MODEL_NAME,
        detector=DETECTOR_BACKEND,
        threshold=THRESHOLD,
    )


# ── Entry point ───────────────────────────────────────────────────────────────

if __name__ == "__main__":
    port = int(os.getenv("FACE_PORT", "5000"))
    log.info("Starting face server on port %d", port)
    app.run(host="127.0.0.1", port=port, debug=False)
