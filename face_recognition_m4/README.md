# Face Recognition Server — macOS M4 (Apple Silicon)

Flask micro-service that powers the **face login** feature of Stayzy.  
Called by `src/Controller/FaceAuthController.php` via cURL.

| Endpoint | Method | What it does |
|---|---|---|
| `/extract-descriptor` | POST | Detect a face in a base64 image → return 512-d embedding |
| `/compare-faces` | POST | Cosine distance between two embeddings → `verified` flag |
| `/health` | GET | Liveness check, active model, threshold |

**Stack: DeepFace + Facenet512 — dlib-free, facenet-pytorch-free.**  
Single `pip install deepface` pulls in everything needed. Works on Python 3.11 and
3.13 with no version-conflict workarounds.

---

## File Overview

```
face_recognition_m4/
├── flask_face_server.py   Flask server (DeepFace / Facenet512)
├── requirements.txt       Four dependencies — deepface, flask, opencv, numpy
├── install.sh             One-shot installer + import verification
├── test_server.py         Smoke tests (server must be running first)
└── README.md              This file
```

---

## Why DeepFace instead of facenet-pytorch?

`facenet-pytorch 2.6.0` (the previous approach) has hard version constraints that
conflict with modern Python 3.13 packages:

| Package | facenet-pytorch requires | Python 3.13 reality |
|---|---|---|
| Pillow | `>=10.2.0,<10.3.0` | No cp313 wheel — builds from source and fails |
| numpy | `<2.0.0` | numpy 2.x is required for cp313 pre-built wheels |
| torch | `<2.3.0` | Latest is 2.12 |

DeepFace has no such constraints, is actively maintained, and handles model
downloads, face detection, and embedding in a single call.

---

## Prerequisites

| Tool | Version | How to check |
|---|---|---|
| macOS | Sonoma 14 or Sequoia 15 | `sw_vers` |
| Xcode Command Line Tools | latest | `xcode-select --version` |
| Homebrew | 4.x | `brew --version` |
| Python | **3.11** (recommended) or 3.13 | `python3 --version` |

**Install Xcode Command Line Tools** (skip if already installed):

```bash
xcode-select --install
```

**Install Homebrew** (skip if already installed):

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

---

## Installation

### Step 1 — Python via pyenv (recommended)

Using the system Python is the most common source of silent failures on M4.

```bash
brew install pyenv

echo 'export PYENV_ROOT="$HOME/.pyenv"' >> ~/.zshrc
echo 'export PATH="$PYENV_ROOT/bin:$PATH"' >> ~/.zshrc
echo 'eval "$(pyenv init -)"' >> ~/.zshrc
source ~/.zshrc

pyenv install 3.11.9
pyenv local 3.11.9

python --version   # → Python 3.11.9
```

> Python 3.13 is also supported — no extra steps required.

---

### Step 2 — Virtual environment

```bash
cd face_recognition_m4/

python -m venv .venv
source .venv/bin/activate
```

---

### Step 3 — Install

```bash
bash install.sh
```

The script upgrades pip, runs `pip install -r requirements.txt`, and verifies
imports. Expected time: **3–7 minutes** (DeepFace pulls in TensorFlow, ~300 MB).

> If you have a broken venv from a previous attempt with facenet-pytorch, wipe it:
>
> ```bash
> deactivate
> rm -rf .venv
> python -m venv .venv && source .venv/bin/activate
> bash install.sh
> ```

---

### Step 4 — Verify

```bash
python -c "import deepface, cv2, numpy, flask; print('OK')"
```

---

## Full Stack — Starting Everything (MAMP)

Face login requires **two services running at the same time**.  
Start them in this order:

### 1 — MAMP (MySQL + PHP)

1. Open the **MAMP** application and click **Start Servers**.
2. Confirm both the Apache/Nginx and MySQL indicators are green.
3. The Symfony app is served via the MAMP PHP server — open it in your browser at the URL configured in MAMP (e.g. `http://localhost:8888` or `http://localhost`).

> **`SQLSTATE[HY000] [2002] Connection refused`** means MAMP is not running or
> MySQL did not start. Click **Start Servers** in MAMP and wait until both
> indicators turn green before opening the Symfony app.

### 2 — Face recognition server

From the `face_recognition_m4/` directory:

```bash
source .venv/bin/activate
python flask_face_server.py
```

Expected startup output:

```
INFO Model: Facenet512 | Detector: opencv | Threshold: 0.30
INFO Starting face server on port 5000
 * Running on http://127.0.0.1:5000
```

### Startup order summary

```
1. Open MAMP → Start Servers  (MySQL + PHP/Apache)
2. cd face_recognition_m4/
   source .venv/bin/activate
   python flask_face_server.py   (face recognition API on port 5000)
3. Open Symfony app in browser
```

---

The **Facenet512 model weights (~90 MB)** are downloaded automatically on the
**first request** and cached in `~/.deepface/weights/`. All subsequent starts are
instant.

### Environment variables

| Variable | Default | Effect |
|---|---|---|
| `FACE_PORT` | `5000` | Listening port |
| `FACE_THRESHOLD` | `0.30` | Cosine distance cut-off (see table below) |
| `FACE_MODEL` | `Facenet512` | DeepFace model name |
| `FACE_DETECTOR` | `opencv` | Face detector backend |

```bash
FACE_THRESHOLD=0.25 python flask_face_server.py
```

### Threshold guide (Facenet512 + cosine distance)

| Value | Behaviour |
|---|---|
| `0.30` | Too strict for webcam — real-world lighting variance often exceeds this |
| `0.40` | **Default** — reliable for indoor webcam quality |
| `0.50` | Permissive — good for poor lighting; slightly higher false-positive risk |

The server logs `distance=X.XXXX` on every comparison so you can tune this value
for your specific setup without guessing.

### Available models

| `FACE_MODEL` | Embedding size | Notes |
|---|---|---|
| `Facenet512` | 512-d | **Default** — best accuracy |
| `Facenet` | 128-d | Lighter, slightly less accurate |
| `ArcFace` | 512-d | Alternative if Facenet512 has issues |

### Available detectors

| `FACE_DETECTOR` | Notes |
|---|---|
| `opencv` | **Default** — fast, no extra download |
| `retinaface` | More accurate, slower, ~1 MB model download |
| `mediapipe` | Google library — requires `pip install mediapipe` |

---

## Testing the Server

The server must be running before tests are executed.

**Terminal 1 — start the server:**

```bash
source .venv/bin/activate
python flask_face_server.py
```

**Terminal 2 — run tests:**

```bash
source .venv/bin/activate
python test_server.py
```

Test with a real portrait image:

```bash
python test_server.py /path/to/portrait.jpg
```

Quick manual check:

```bash
curl -s http://127.0.0.1:5000/health | python -m json.tool
```

---

## How It Works

```
Browser (webcam frame — base64 JPEG)
        │
        ▼  POST /extract-descriptor
FaceAuthController.php
        │
        ▼
flask_face_server.py
   ├── cv2.imdecode()        decode base64 → BGR image
   └── DeepFace.represent()  detect face + encode → 512-d embedding
        │
        │  { "success": true, "descriptor": [ ...512 floats... ] }
        ▼
FaceAuthController.php  →  stores descriptor in DB column `face_descriptor`

── Login flow ────────────────────────────────────────────────────────────────

   live descriptor   ──┐
                        ├─► POST /compare-faces
   stored descriptor ──┘         │
                                  ▼
                     cosine distance < 0.30 → verified: true → session login
```

---

## Running as a Background Service (launchd)

To start the server automatically at login:

1. Create `~/Library/LaunchAgents/com.stayzy.face.plist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN"
  "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key>
  <string>com.stayzy.face</string>
  <key>ProgramArguments</key>
  <array>
    <string>/ABSOLUTE/PATH/TO/face_recognition_m4/.venv/bin/python</string>
    <string>/ABSOLUTE/PATH/TO/face_recognition_m4/flask_face_server.py</string>
  </array>
  <key>RunAtLoad</key>   <true/>
  <key>KeepAlive</key>   <true/>
  <key>StandardOutPath</key>   <string>/tmp/stayzy-face.log</string>
  <key>StandardErrorPath</key> <string>/tmp/stayzy-face-err.log</string>
</dict>
</plist>
```

2. Load and start:

```bash
launchctl load  ~/Library/LaunchAgents/com.stayzy.face.plist
launchctl start com.stayzy.face
tail -f /tmp/stayzy-face.log
```

3. Stop or unload:

```bash
launchctl stop   com.stayzy.face
launchctl unload ~/Library/LaunchAgents/com.stayzy.face.plist
```

---

## Troubleshooting

### `SQLSTATE[HY000] [2002] Connection refused` (Symfony / MySQL)

MAMP's MySQL server is not running. Open the **MAMP** application and click
**Start Servers** — wait until both the Apache and MySQL status indicators turn
green, then reload the Symfony page.

---

### `findBy … createdAt` error / Symfony page shows a Doctrine exception

The database schema has not been applied yet. Open a terminal, navigate to the
project root, and run:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Make sure MAMP is running and the `DATABASE_URL` in your `.env.local` points to
the MAMP MySQL port (default `3306`, or `8889` on some MAMP setups):

```
DATABASE_URL="mysql://root:root@127.0.0.1:3306/pidev_db?serverVersion=8.0"
```

---

### `ValueError: You have tensorflow 2.x and this requires tf-keras`

```
ValueError: You have tensorflow 2.21.0 and this requires tf-keras package.
Please run `pip install tf-keras` or downgrade your tensorflow.
```

`retina-face` (installed as a deepface dependency) requires the compatibility
shim `tf-keras` when running with TensorFlow 2.x. It is listed in
`requirements.txt` so `bash install.sh` installs it automatically.  
If you hit this on an existing venv, just run:

```bash
pip install tf-keras
```

---

### `HTTPError: HTTP Error 400: BAD REQUEST` in `test_server.py`

`urllib.request.urlopen` raises an exception for any 4xx response, even when the
server returns a valid JSON body. `test_server.py` now catches `HTTPError` and
reads the JSON from the exception, so this no longer crashes. If you have an
older copy, pull the latest `test_server.py` or add the try/except yourself:

```python
try:
    with urllib.request.urlopen(req, timeout=30) as resp:
        return json.loads(resp.read())
except urllib.error.HTTPError as e:
    return json.loads(e.read())
```

---

### `ConnectionRefusedError` when running `test_server.py`

The server is not running. Start it first in a **separate terminal**:

```bash
source .venv/bin/activate
python flask_face_server.py
```

Then run `test_server.py` in another terminal.

---

### Version conflict warnings from pip (facenet-pytorch, numpy, Pillow, torch)

These come from a previous `facenet-pytorch` install. They have no effect on the
current DeepFace-based server. Do a clean reinstall to remove the old packages:

```bash
deactivate
rm -rf .venv
python -m venv .venv && source .venv/bin/activate
bash install.sh
```

---

### `zsh: 2.31.0 not found` (or similar) when typing pip install manually

zsh treats bare `>=` as a file-redirect operator. Always quote version specifiers:

```bash
pip install "deepface>=0.0.93"   # correct
pip install  deepface>=0.0.93    # wrong — zsh parses >= as redirect
```

`install.sh` uses `pip install -r requirements.txt` so it is never affected.

---

### Port 5000 already in use

macOS Monterey and later run **AirPlay Receiver** on port 5000.  
Disable it: **System Settings → General → AirDrop & Handoff → AirPlay Receiver** (off).  
Or use a different port:

```bash
FACE_PORT=5001 python flask_face_server.py
```

Update `$flaskUrl` in `FaceAuthController.php` to match.

---

### Camera not visible / page gets darker but no camera window appears (login, client profile, proprietaire profile)

Two root causes:

**1 — Stale Symfony template cache.**  
Twig compiles templates to PHP and caches them. If you changed `login.html.twig`
but did not clear the cache, the browser still receives the old version (which
had the Bootstrap modal that caused the darkening). Always clear the cache after
template changes:

```bash
php bin/console cache:clear
```

**2 — Camera overlay blocked by parent `overflow: hidden`.**  
The login card has `overflow: hidden`. Any element positioned relative to it
(including Bootstrap modals) can be clipped. The fix is to place the camera
overlay with `position: fixed` **after `</main>`**, completely outside every
container. It is then anchored to the viewport, not to any parent element.

**Current implementation** uses a fully custom fixed overlay — no Bootstrap modal
at all. Applied to all three pages:

| Template | Feature |
|---|---|
| `login.html.twig` | Face login (auto-detects every 2 s) |
| `profile/client_profile.html.twig` | Face registration (manual Capturer button) |
| `profile/proprietaire_profile.html.twig` | Face registration (manual Capturer button) |

| Element | Role |
|---|---|
| `#faceBackdrop` | `position:fixed; inset:0; z-index:9998` — dark background, click to close |
| `#faceWindow` | `position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:9999` — camera window |

Both elements live at the end of `{% block body %}`, after `</section>`, and are
toggled with plain `style.display` — no Bootstrap, no animation races, no
z-index conflicts with `overflow:hidden` cards.

After any template change always clear the Symfony cache:

```bash
php bin/console cache:clear
```

---

### "Erreur réseau. Réessayez" on the login page

The generic "Erreur réseau" message was masking three different root causes.
The JavaScript now distinguishes them and shows a specific message for each:

| What you see | Real cause | Fix |
|---|---|---|
| "Serveur inaccessible. Vérifiez que MAMP est démarré." | `fetch('/face/login')` threw a network error — Symfony is down | Start MAMP |
| "Erreur serveur HTTP 500. Consultez la console (F12)" | Symfony returned an HTML error page instead of JSON | Open browser DevTools → Console for the real PHP error |
| "Serveur de reconnaissance non démarré. Lancez flask_face_server.py." | Symfony reached but Flask is not running (`cURL connect failed`) | Run `python flask_face_server.py` in `face_recognition_m4/` |

**Quick diagnostic checklist:**
1. Is MAMP running? → green indicators in MAMP
2. Is Flask running? → `curl http://127.0.0.1:5000/health` should return JSON
3. Open browser DevTools (F12) → Console tab → look for `[FaceLogin]` log lines
4. Clear Symfony cache after any PHP change: `php bin/console cache:clear`

---

### Face registered but login still says "Visage non reconnu"

Two causes, both now fixed:

**1 — `/compare-faces` returns 400 for some users.**  
The PHP controller skipped users with `null` face descriptors but didn't check
for `json_decode` failure. Users whose descriptor was saved with the old
`facenet-pytorch` server (incompatible embedding space) have valid JSON stored
but the comparison is meaningless — they now cause 400 errors from Flask.

Fix applied in `FaceAuthController.php`:
```php
$savedDescriptor = json_decode($user->getFaceDescriptor(), true);
if (!is_array($savedDescriptor) || count($savedDescriptor) === 0) continue;
```

**2 — Threshold 0.30 too strict for webcam.**  
DeepFace Facenet512's nominal threshold (0.30) is calibrated for controlled
studio conditions. Webcam images at different times have lighting and alignment
variation that pushes the distance above 0.30. The default is now **0.40**.

Watch the server log for the actual distance of your face:
```
INFO compare-faces  distance=0.3521  threshold=0.40  verified=True
```
If your distance is consistently above 0.40, raise the threshold:
```bash
FACE_THRESHOLD=0.50 python flask_face_server.py
```

**Important:** after switching from `facenet-pytorch` to `DeepFace`, all users
must **re-register their face** — the two models produce incompatible embeddings.
Old descriptors in the database will never match.

---

### `No face detected` for every image

- The face must occupy at least **80×80 pixels** in the frame.
- Test with a well-lit, front-facing portrait first.
- Try the `retinaface` detector for better accuracy:

```bash
FACE_DETECTOR=retinaface python flask_face_server.py
```

---

### `source: no such file or directory: .venv/bin/activate`

You are inside the `.venv` directory. Do **not** `cd .venv` — activate from the
`face_recognition_m4/` folder:

```bash
cd face_recognition_m4/        # correct working directory
source .venv/bin/activate      # activate from here, not from inside .venv/
python flask_face_server.py
```

---

### `libomp` / OpenMP error

```bash
brew install libomp
```

---

### `OMP: Error #15` — duplicate OpenMP library

```bash
export KMP_DUPLICATE_LIB_OK=TRUE
python flask_face_server.py
```
