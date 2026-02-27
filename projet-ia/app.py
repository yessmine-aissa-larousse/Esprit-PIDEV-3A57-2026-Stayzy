"""
app.py
======
API Flask — Score de qualité des réponses aux réclamations.

Endpoints :
  POST /score-reponse   → score d'une réponse
  GET  /health          → statut de l'API
"""

import pickle
import os
from flask import Flask, request, jsonify
from datetime import datetime

app = Flask(__name__)

# ──────────────────────────────────────────────
# CHARGEMENT DU MODÈLE
# ──────────────────────────────────────────────

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")

try:
    with open(MODEL_PATH, "rb") as f:
        model = pickle.load(f)
    print("✅ model.pkl chargé.")
except FileNotFoundError:
    model = None
    print("❌ model.pkl introuvable. Lance train_model.py d'abord.")


# ──────────────────────────────────────────────
# HELPERS
# ──────────────────────────────────────────────

# Score numérique selon le label prédit
SCORE_MAP = {
    "excellent":  90,
    "acceptable": 55,
    "faible":     20,
}

COLOR_MAP = {
    "excellent":  "success",
    "acceptable": "warning",
    "faible":     "danger",
}

ICON_MAP = {
    "excellent":  "⭐",
    "acceptable": "👍",
    "faible":     "⚠️",
}

def score_from_length(contenu: str) -> int:
    """Bonus de score basé sur la longueur du contenu."""
    length = len(contenu.strip())
    if length > 300:
        return 10
    elif length > 150:
        return 5
    elif length < 30:
        return -10
    return 0

def compute_delay_score(date_reclamation: str | None, date_reponse: str | None) -> dict:
    """Évalue la rapidité de traitement."""
    if not date_reclamation or not date_reponse:
        return {"label": "inconnu", "hours": None, "color": "secondary"}

    try:
        dt_rec = datetime.fromisoformat(date_reclamation)
        dt_rep = datetime.fromisoformat(date_reponse)
        hours  = round((dt_rep - dt_rec).total_seconds() / 3600, 1)

        if hours <= 24:
            return {"label": "rapide", "hours": hours, "color": "success"}
        elif hours <= 72:
            return {"label": "normal", "hours": hours, "color": "warning"}
        else:
            return {"label": "lent",   "hours": hours, "color": "danger"}
    except Exception:
        return {"label": "inconnu", "hours": None, "color": "secondary"}


# ──────────────────────────────────────────────
# ROUTES
# ──────────────────────────────────────────────

@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "status": "ok" if model else "error",
        "model_loaded": model is not None,
        "timestamp": datetime.now().isoformat(),
    }), 200 if model else 500


@app.route("/score-reponse", methods=["POST"])
def score_reponse():
    if not model:
        return jsonify({"error": "Modèle non chargé. Lance train_model.py."}), 500

    data = request.get_json(silent=True)
    if not data or "contenu" not in data:
        return jsonify({"error": "Champ 'contenu' manquant."}), 400

    contenu          = data.get("contenu", "")
    date_reclamation = data.get("date_reclamation")
    date_reponse     = data.get("date_reponse")
    reponse_id       = data.get("id")

    # ── Prédiction ML ──
    label      = model.predict([contenu])[0]
    proba      = model.predict_proba([contenu])[0]
    confidence = round(float(max(proba)) * 100, 1)

    # ── Score final ──
    base_score   = SCORE_MAP.get(label, 50)
    length_bonus = score_from_length(contenu)
    final_score  = max(0, min(100, base_score + length_bonus))

    # ── Délai ──
    delay = compute_delay_score(date_reclamation, date_reponse)

    return jsonify({
        "id":         reponse_id,
        "quality": {
            "label":      label,
            "score":      final_score,
            "confidence": confidence,
            "color":      COLOR_MAP.get(label, "secondary"),
            "icon":       ICON_MAP.get(label, "ℹ️"),
        },
        "delay":      delay,
        "scored_at":  datetime.now().isoformat(),
    })


# ──────────────────────────────────────────────
# MAIN
# ──────────────────────────────────────────────

if __name__ == "__main__":
    print("🚀 Flask API sur http://localhost:5000")
    app.run(debug=True, host="0.0.0.0", port=5000)