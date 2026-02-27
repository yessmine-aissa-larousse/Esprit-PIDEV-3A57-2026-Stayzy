from flask import Flask, request, jsonify
import pickle, numpy as np, os
from datetime import datetime, timedelta
app = Flask(__name__)

MODEL_PATH = 'model.pkl'
model = None
if os.path.exists(MODEL_PATH):
    with open(MODEL_PATH, 'rb') as f:
        model = pickle.load(f)

# ══════════════════════════════════════
# ROUTE : Score d'un candidat
# ══════════════════════════════════════
@app.route('/predict', methods=['POST'])
def predict():
    data = request.json

    nb_res    = data.get('nb_reservations_passees', 0)
    duree     = data.get('duree_mois', 0)
    moy_duree = data.get('moyenne_duree_passee', 0)
    nb_pers   = data.get('nombre_personnes', 1)

    if model:
        features = [[nb_res, duree, moy_duree, nb_pers]]
        proba    = model.predict_proba(features)[0]
        score    = round(proba[1] * 100, 1)
    else:
        # Scoring de secours
        score  = 0
        score += min(nb_res * 8, 40)
        score += min(duree * 2.5, 35)
        score += max(0, 25 - nb_pers * 5)
        score  = round(min(score, 100), 1)

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

# ══════════════════════════════════════
# ROUTE : Insights IA pour le Dashboard
# ══════════════════════════════════════
@app.route('/insights', methods=['POST'])
def insights():
    data       = request.json
    classement = data.get('classement', [])
    par_mois   = data.get('reservations_mois', [0]*12)

    # 1. Logement le plus demandé
    top_logement = classement[0]['titre'] if classement else 'N/A'

    # 2. Logements sous-performants (taux occupation < 30%)
    sous_perf = [
        l['titre'] for l in classement
        if l.get('tauxOccup', 0) < 30 and l.get('total', 0) > 0
    ]

    # 3. Période creuse — mois avec min réservations
    if par_mois and max(par_mois) > 0:
        min_val   = min(par_mois)
        min_idx   = par_mois.index(min_val)
        mois_noms = ['Jan','Fév','Mar','Avr','Mai','Jun',
                     'Jul','Aoû','Sep','Oct','Nov','Déc']
        from datetime import datetime, timedelta
        date_creuse = datetime.now() - timedelta(days=(11 - min_idx) * 30)
        periode_creuse = mois_noms[date_creuse.month - 1] + ' ' + str(date_creuse.year)
    else:
        periode_creuse = 'N/A'

    # 4. Tendance — en hausse ou en baisse ?
    if len(par_mois) >= 6:
        premiere_moitie = sum(par_mois[:6])
        deuxieme_moitie = sum(par_mois[6:])
        if deuxieme_moitie > premiere_moitie:
            tendance     = "📈 En hausse"
            tendance_cls = "success"
        elif deuxieme_moitie < premiere_moitie:
            tendance     = "📉 En baisse"
            tendance_cls = "danger"
        else:
            tendance     = "➡️ Stable"
            tendance_cls = "warning"
    else:
        tendance     = "➡️ Stable"
        tendance_cls = "warning"

    return jsonify({
        'top_logement'    : top_logement,
        'sous_performants': sous_perf,
        'periode_creuse'  : periode_creuse,
        'tendance'        : tendance,
        'tendance_cls'    : tendance_cls,
        'prediction_msg'  : f"Période creuse prévue en {periode_creuse} — lancez des promotions à l'avance."
    })
# ══════════════════════════════════════
# ROUTE 1 : Analyse Prix (Client)
# ══════════════════════════════════════
@app.route('/prix-analyse', methods=['POST'])
def prix_analyse():
    data      = request.json
    prix      = data.get('prix', 0)
    tous_prix = data.get('tous_prix', [])

    if not tous_prix or len(tous_prix) < 2:
        return jsonify({'badge': 'info', 'emoji': 'i', 'message': 'Prix non comparable', 'diff_pct': 0})

    moyenne  = sum(tous_prix) / len(tous_prix)
    diff_pct = round(((prix - moyenne) / moyenne) * 100, 1)

    if diff_pct <= -15:
        badge, emoji, msg = 'success', 'good', f"Tres bon prix — {abs(diff_pct)}% sous la moyenne"
    elif diff_pct <= 5:
        badge, emoji, msg = 'success', 'good', "Prix correct — dans la moyenne"
    elif diff_pct <= 20:
        badge, emoji, msg = 'warning', 'mid', f"Prix un peu eleve — {diff_pct}% au-dessus"
    else:
        badge, emoji, msg = 'danger', 'bad', f"Prix eleve — {diff_pct}% au-dessus de la moyenne"

    return jsonify({'badge': badge, 'emoji': emoji, 'message': msg, 'diff_pct': diff_pct})

# ══════════════════════════════════════
# ROUTE 2 : Rappel 24h (Client)
# ══════════════════════════════════════
@app.route('/rappel-24h', methods=['POST'])
def rappel_24h():
    data         = request.json
    reservations = data.get('reservations', [])
    alertes      = {}

    for r in reservations:
        if r.get('status') != 'EN_ATTENTE':
            alertes[r['id']] = {'niveau': 'ok', 'message': None, 'peut_modifier': True}
            continue
        try:
            date_debut = datetime.strptime(r['date_debut'], '%Y-%m-%d')
            heures     = (date_debut - datetime.now()).total_seconds() / 3600

            if heures < 0:
                alertes[r['id']] = {'niveau': 'ok', 'message': None, 'peut_modifier': False}
            elif heures <= 2:
                alertes[r['id']] = {
                    'niveau': 'danger',
                    'message': f"Plus que {int(heures)}h pour modifier !",
                    'peut_modifier': False
                }
            elif heures <= 24:
                alertes[r['id']] = {
                    'niveau': 'warning',
                    'message': f"Encore {int(heures)}h pour modifier",
                    'peut_modifier': True
                }
            else:
                alertes[r['id']] = {'niveau': 'ok', 'message': None, 'peut_modifier': True}
        except:
            alertes[r['id']] = {'niveau': 'ok', 'message': None, 'peut_modifier': True}

    return jsonify({'alertes': alertes})

# ══════════════════════════════════════
# ROUTE 3 : Dates Optimales (Client)
# ══════════════════════════════════════
@app.route('/dates-optimales', methods=['POST'])
def dates_optimales():
    data         = request.json
    reservations = data.get('reservations_existantes', [])
    prix_base    = data.get('prix_base', 100)
    now          = datetime.now()
    suggestions  = []

    for semaine in range(1, 4):
        debut = now + timedelta(weeks=semaine)
        nb    = sum(1 for r in reservations if _dans_semaine(r, debut))

        if nb == 0:
            reduction, dispo, badge = 15, 'Tres disponible', 'success'
        elif nb <= 2:
            reduction, dispo, badge = 5, 'Disponible', 'info'
        else:
            reduction, dispo, badge = 0, 'Tres demande', 'warning'

        suggestions.append({
            'semaine'    : "Semaine du " + debut.strftime('%d/%m'),
            'badge'      : badge,
            'disponible' : dispo,
            'reduction'  : reduction,
            'prix_estime': round(prix_base * (1 - reduction / 100)),
            'message'    : (str(reduction) + "% moins cher" if reduction > 0 else "Periode tres demandee")
        })

    suggestions.sort(key=lambda x: x['reduction'], reverse=True)
    return jsonify({'suggestions': suggestions})

def _dans_semaine(date_str, debut_semaine):
    try:
        d = datetime.strptime(date_str, '%Y-%m-%d')
        return debut_semaine <= d <= debut_semaine + timedelta(days=7)
    except:
        return False
# ══════════════════════════════════════
# HEALTH CHECK
# ══════════════════════════════════════
@app.route('/health')
def health():
    return jsonify({'status': 'ok', 'model_loaded': model is not None})

if __name__ == '__main__':
    app.run(port=5000, debug=True)