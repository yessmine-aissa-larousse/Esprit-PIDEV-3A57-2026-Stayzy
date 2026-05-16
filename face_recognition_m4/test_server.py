"""
Smoke tests for the face recognition server.

IMPORTANT: start the server first in a separate terminal:
    source .venv/bin/activate
    python flask_face_server.py

Then run:
    python test_server.py
    python test_server.py /path/to/portrait.jpg   # optional real-image test
"""

import base64
import json
import sys
import urllib.error
import urllib.request

BASE = "http://127.0.0.1:5000"


def _post(route: str, payload: dict) -> dict:
    body = json.dumps(payload).encode()
    req = urllib.request.Request(
        BASE + route,
        data=body,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return json.loads(resp.read())
    except urllib.error.HTTPError as e:
        # Server returns JSON body even on 4xx — read it from the exception
        return json.loads(e.read())


def _check_server():
    try:
        urllib.request.urlopen(BASE + "/health", timeout=3)
    except (urllib.error.URLError, ConnectionRefusedError):
        print("ERROR: Cannot reach the server at", BASE)
        print()
        print("Start it first in a separate terminal:")
        print("  source .venv/bin/activate")
        print("  python flask_face_server.py")
        sys.exit(1)


def test_health():
    with urllib.request.urlopen(BASE + "/health", timeout=5) as resp:
        data = json.loads(resp.read())
    assert data["status"] == "ok", data
    print(f"[OK] /health   model={data['model']}  detector={data['detector']}  threshold={data['threshold']}")


def test_extract_no_image():
    r = _post("/extract-descriptor", {"image": ""})
    assert not r["success"]
    print(f"[OK] /extract-descriptor (empty image) → error: {r['error']}")


def test_compare_identical():
    d = [0.1] * 512
    r = _post("/compare-faces", {"descriptor1": d, "descriptor2": d})
    assert r["success"]
    assert r["verified"] is True
    assert abs(r["distance"]) < 1e-5
    print(f"[OK] /compare-faces (identical)  → distance={r['distance']:.6f}  verified={r['verified']}")


def test_compare_opposite():
    d1 = [1.0] + [0.0] * 511
    d2 = [-1.0] + [0.0] * 511
    r = _post("/compare-faces", {"descriptor1": d1, "descriptor2": d2})
    assert r["success"]
    assert r["verified"] is False
    print(f"[OK] /compare-faces (opposite)   → distance={r['distance']:.6f}  verified={r['verified']}")


def test_extract_with_file(path: str):
    with open(path, "rb") as f:
        b64 = base64.b64encode(f.read()).decode()
    r = _post("/extract-descriptor", {"image": b64})
    if r["success"]:
        print(f"[OK] /extract-descriptor ({path})")
        print(f"     descriptor[0]={r['descriptor'][0]:.4f}  len={len(r['descriptor'])}")
    else:
        print(f"[WARN] /extract-descriptor ({path}) → {r['error']}")


if __name__ == "__main__":
    _check_server()

    print("=== Stayzy Face Server Smoke Tests ===\n")
    test_health()
    test_extract_no_image()
    test_compare_identical()
    test_compare_opposite()

    if len(sys.argv) > 1:
        test_extract_with_file(sys.argv[1])

    print("\nAll tests passed.")
