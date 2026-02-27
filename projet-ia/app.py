from flask import Flask, request, jsonify
import pickle, numpy as np, os

app = Flask(__name__)

# Charger le modèle si existe, sinon scoring de secours
MODEL_PATH = 'model.pkl'
model = None
if os.path.exists(MODEL_PATH):
    with open(MODEL_PATH, 'rb') as f:
        model = pickle.load(f)

@app.route('/predict', methods=['POST'])
def predict():
    data = request.json

    nb_res    = data.get('nb_reservations_passees', 0)
    duree     = data.get('duree_mois', 0)
    moy_duree = data.get('moyenne_duree_passee', 0)
    nb_pers   = data.get('nombre_personnes', 1)

    if model:
        # ── Vrai modèle ML ──
        features    = [[nb_res, duree, moy_duree, nb_pers]]
        proba       = model.predict_proba(features)[0]
        score       = round(proba[1] * 100, 1)
    else:
        # ── Scoring de secours (règles simples) si pas encore de modèle ──
        score = 0
        score += min(nb_res * 8, 40)   # fidélité → max 40 pts
        score += min(duree * 2.5, 35)  # durée    → max 35 pts
        score += max(0, 25 - nb_pers * 5)  # nb personnes → max 25 pts
        score = round(min(score, 100), 1)

    if score >= 75:
        priorite, emoji = "Priorité Haute", "🥇"
    elif score >= 40:
        priorite, emoji = "Priorité Moyenne", "🥈"
    else:
        priorite, emoji = "Nouveau / Risqué", "🥉"

    return jsonify({
        'score'   : score,
        'fiable'  : score >= 50,
        'priorite': priorite,
        'emoji'   : emoji
    })

@app.route('/health')
def health():
    return jsonify({'status': 'ok', 'model_loaded': model is not None})

if __name__ == '__main__':
    app.run(port=5000, debug=True)