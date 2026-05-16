#!/usr/bin/env bash
# install.sh — one-shot setup for face_recognition_m4 on macOS M4 / Python 3.13
#
# Usage:
#   cd face_recognition_m4
#   python -m venv .venv
#   source .venv/bin/activate
#   bash install.sh

set -euo pipefail

echo "==> Upgrading pip..."
pip install --upgrade pip

echo ""
echo "==> Installing dependencies (deepface, flask, opencv, numpy)..."
pip install -r requirements.txt

echo ""
echo "==> Verifying imports..."
python -c "
import deepface, cv2, numpy, flask
print('  deepface :', deepface.__version__)
print('  numpy    :', numpy.__version__)
print('  opencv   :', cv2.__version__)
print()
print('All imports OK.')
print('NOTE: Facenet512 model weights (~90 MB) download on the first request.')
"

echo ""
echo "Done. Start the server with:"
echo "  python flask_face_server.py"
