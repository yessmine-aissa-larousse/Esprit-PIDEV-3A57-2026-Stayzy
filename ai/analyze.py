"""
analyze.py
──────────
Script IA principal de Stayzy.
Reçoit les données en JSON (fichier temporaire), analyse les logements
d'un propriétaire et retourne un rapport JSON (stdout).

Appelé par Symfony via shell_exec() :
    python ai/analyze.py '/tmp/stayzy_ai_xxx.json'

Algorithmes utilisés :
    1. Analyse descriptive   — statistiques de performance
    2. Régression linéaire   — prédiction des réservations
    3. Règles métier         — recommandations personnalisées
"""

import sys
import json
import warnings
import numpy as np
import pandas as pd
from sklearn.linear_model import LinearRegression

warnings.filterwarnings("ignore")

# ✅ FIX ENCODAGE : force UTF-8 pour éviter les caractères cassés (€, é, etc.)
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')


# ══════════════════════════════════════════════════════════════
# 0. LECTURE DES DONNÉES DEPUIS SYMFONY (fichier JSON temp)
# ══════════════════════════════════════════════════════════════
def lire_donnees():
    if len(sys.argv) < 2:
        erreur("Aucune donnée reçue de Symfony.")

    arg = sys.argv[1]

    # Si c'est un chemin de fichier, lire le fichier
    if arg.endswith('.json') or '\\' in arg or '/' in arg:
        try:
            with open(arg, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            erreur(f"Impossible de lire le fichier JSON : {e}")

    # Sinon, parser directement comme JSON string
    try:
        return json.loads(arg)
    except json.JSONDecodeError as e:
        erreur(f"JSON invalide : {e}")


def erreur(msg):
    # ✅ ensure_ascii=True pour éviter tout problème d'encodage dans l'erreur
    print(json.dumps({"erreur": msg}, ensure_ascii=True))
    sys.exit(1)


# ══════════════════════════════════════════════════════════════
# 1. ANALYSE DESCRIPTIVE — performance de chaque logement
# ══════════════════════════════════════════════════════════════
def analyser_logement(logement, reservations_logement, prix_moyen_marche):
    """Calcule les KPIs de performance d'un logement."""

    confirmees = [r for r in reservations_logement if r["status"] == "confirmee"]
    annulees   = [r for r in reservations_logement if r["status"] == "annulee"]
    total      = len(reservations_logement)

    # Revenus générés
    revenu_total = sum(r["prix_total"] for r in confirmees)

    # Taux d'annulation
    taux_annulation = round(len(annulees) / total * 100, 1) if total > 0 else 0

    # Durée moyenne des séjours
    durees = []
    for r in confirmees:
        try:
            debut = pd.to_datetime(r["date_debut"])
            fin   = pd.to_datetime(r["date_fin"])
            durees.append((fin - debut).days)
        except Exception:
            pass
    duree_moyenne = round(np.mean(durees), 1) if durees else 0

    # Comparaison prix vs marché
    prix = logement["prix"]
    diff_marche = round(((prix - prix_moyen_marche) / prix_moyen_marche) * 100, 1) if prix_moyen_marche > 0 else 0

    # Score de performance /100
    score = calculer_score(
        nb_confirmees=len(confirmees),
        taux_annulation=taux_annulation,
        note_moyenne=logement.get("note_moyenne") or 0,
        nb_favoris=logement.get("nb_favoris") or 0,
        diff_marche=diff_marche,
        a_promo=logement.get("a_promo") or False,
        nb_photos=logement.get("nb_photos") or 0,
        description_longue=logement.get("description_longue") or False,
    )

    return {
        "nb_reservations_total": total,
        "nb_confirmees":         len(confirmees),
        "nb_annulees":           len(annulees),
        "taux_annulation":       taux_annulation,
        "revenu_total":          round(revenu_total, 2),
        "duree_moyenne_sejour":  duree_moyenne,
        "diff_prix_marche":      diff_marche,
        "score":                 score,
    }


def calculer_score(nb_confirmees, taux_annulation, note_moyenne,
                   nb_favoris, diff_marche, a_promo, nb_photos, description_longue):
    """Calcule un score de performance de 0 à 100."""
    score = 0

    # Réservations confirmées (max 35 pts)
    score += min(nb_confirmees * 2, 35)

    # Note moyenne (max 20 pts)
    if note_moyenne and note_moyenne > 0:
        score += round((note_moyenne / 5) * 20)

    # Favoris (max 10 pts)
    score += min(nb_favoris, 10)

    # Prix cohérent avec le marché (max 10 pts)
    if abs(diff_marche) < 10:
        score += 10
    elif abs(diff_marche) < 20:
        score += 5

    # Taux d'annulation faible (max 10 pts)
    if taux_annulation < 10:
        score += 10
    elif taux_annulation < 25:
        score += 5

    # Photos (max 5 pts)
    if nb_photos >= 3:
        score += 5
    elif nb_photos >= 1:
        score += 2

    # Description complète (max 5 pts)
    if description_longue:
        score += 5

    # Promotion active (max 5 pts)
    if a_promo:
        score += 5

    return min(score, 100)


# ══════════════════════════════════════════════════════════════
# 2. PRÉDICTION — régression linéaire
# ══════════════════════════════════════════════════════════════
def predire_reservations(logement, reservations_logement):
    """
    Prédit le nombre de réservations du mois prochain.
    Utilise une régression linéaire sur les derniers mois.
    """
    if len(reservations_logement) < 3:
        return {"min": 1, "max": 3, "methode": "estimation_defaut"}

    df = pd.DataFrame(reservations_logement)
    df["date_debut"] = pd.to_datetime(df["date_debut"], errors="coerce")
    df = df.dropna(subset=["date_debut"])
    df["mois"] = df["date_debut"].dt.to_period("M")

    reservations_par_mois = (
        df[df["status"] == "confirmee"]
        .groupby("mois")
        .size()
        .reset_index(name="nb")
    )

    if len(reservations_par_mois) < 2:
        return {"min": 1, "max": 4, "methode": "estimation_defaut"}

    X = np.arange(len(reservations_par_mois)).reshape(-1, 1)
    y = reservations_par_mois["nb"].values

    model = LinearRegression()
    model.fit(X, y)

    prochain_mois_idx = len(reservations_par_mois)
    prediction        = model.predict([[prochain_mois_idx]])[0]
    prediction        = max(0, prediction)

    pred_min = max(0, round(prediction * 0.7))
    pred_max = round(prediction * 1.3)

    return {
        "min":      int(pred_min),
        "max":      int(pred_max),
        "tendance": "hausse" if model.coef_[0] > 0.1 else (
                    "baisse" if model.coef_[0] < -0.1 else "stable"),
        "methode":  "regression_lineaire",
    }


# ══════════════════════════════════════════════════════════════
# 3. RECOMMANDATIONS — règles métier intelligentes
# ══════════════════════════════════════════════════════════════
def generer_recommandations(logement, stats, prix_moyen_marche):
    """Génère des recommandations personnalisées pour le propriétaire."""
    recommandations = []

    prix            = logement["prix"]
    nb_favoris      = logement.get("nb_favoris") or 0
    a_promo         = logement.get("a_promo") or False
    nb_photos       = logement.get("nb_photos") or 0
    nb_confirmees   = stats["nb_confirmees"]
    taux_annulation = stats["taux_annulation"]
    diff_marche     = stats["diff_prix_marche"]

    # ── Prix trop élevé ──
    if diff_marche > 20:
        prix_suggere = round(prix_moyen_marche * 1.10, 0)
        recommandations.append({
            "priorite": "haute",
            "icone":    "bi-cash-coin",
            "titre":    "Prix trop eleve",
            "texte":    f"Votre prix ({prix} EUR) est {diff_marche}% au-dessus du marche. "
                        f"Envisagez de baisser a {prix_suggere} EUR/nuit pour attirer plus de clients.",
        })

    # ── Prix trop bas — sous-évalué ──
    elif diff_marche < -20:
        prix_suggere = round(prix_moyen_marche * 0.95, 0)
        recommandations.append({
            "priorite": "info",
            "icone":    "bi-graph-up-arrow",
            "titre":    "Potentiel de revenus inexploite",
            "texte":    f"Votre prix ({prix} EUR) est {abs(diff_marche)}% en dessous du marche. "
                        f"Vous pouvez augmenter jusqu'a {prix_suggere} EUR/nuit sans perdre de clients.",
        })

    # ── Beaucoup de favoris mais peu de réservations ──
    if nb_favoris >= 5 and nb_confirmees < 3:
        recommandations.append({
            "priorite": "haute",
            "icone":    "bi-heart-fill",
            "titre":    "Fort interet mais peu de reservations",
            "texte":    f"{nb_favoris} personnes ont mis votre logement en favori mais peu reservent. "
                        "Une promotion de 10-15% pourrait declencher des reservations.",
        })

    # ── Pas de promotion alors que le logement performe peu ──
    if not a_promo and nb_confirmees < 5:
        recommandations.append({
            "priorite": "moyenne",
            "icone":    "bi-tag-fill",
            "titre":    "Lancez une promotion",
            "texte":    "Ce logement a peu de reservations. Une promotion de 15-20% "
                        "peut booster sa visibilite sur la plateforme.",
        })

    # ── Photos insuffisantes ──
    if nb_photos < 3:
        recommandations.append({
            "priorite": "moyenne",
            "icone":    "bi-camera-fill",
            "titre":    "Ajoutez plus de photos",
            "texte":    f"Votre logement n'a que {nb_photos} photo(s). "
                        "Les logements avec 5+ photos recoivent 3x plus de reservations.",
        })

    # ── Taux d'annulation élevé ──
    if taux_annulation > 30:
        recommandations.append({
            "priorite": "haute",
            "icone":    "bi-exclamation-triangle-fill",
            "titre":    "Taux d'annulation eleve",
            "texte":    f"Votre taux d'annulation est de {taux_annulation}%. "
                        "Verifiez que les informations de l'annonce correspondent bien a la realite.",
        })

    # ── Logement performant ──
    if nb_confirmees >= 10 and taux_annulation < 15:
        recommandations.append({
            "priorite": "succes",
            "icone":    "bi-trophy-fill",
            "titre":    "Excellent logement !",
            "texte":    "Ce logement performe tres bien. Maintenez la qualite "
                        "et pensez a augmenter legerement le prix en haute saison.",
        })

    # ── Description courte ──
    if not logement.get("description_longue"):
        recommandations.append({
            "priorite": "info",
            "icone":    "bi-pencil-fill",
            "titre":    "Enrichissez votre description",
            "texte":    "Une description detaillee (equipements, regles, quartier) "
                        "rassure les clients et augmente le taux de conversion.",
        })

    if not recommandations:
        recommandations.append({
            "priorite": "succes",
            "icone":    "bi-check-circle-fill",
            "titre":    "Tout est bon !",
            "texte":    "Votre logement est bien optimise. Continuez ainsi !",
        })

    return recommandations


# ══════════════════════════════════════════════════════════════
# 4. ANALYSE PAR SAISON
# ══════════════════════════════════════════════════════════════
def analyser_saisonnalite(reservations_logement):
    """Détermine les meilleures et pires saisons."""
    saisons = {"Printemps": 0, "Ete": 0, "Automne": 0, "Hiver": 0}

    def get_saison(mois):
        if mois in [3, 4, 5]:   return "Printemps"
        if mois in [6, 7, 8]:   return "Ete"
        if mois in [9, 10, 11]: return "Automne"
        return "Hiver"

    for r in reservations_logement:
        if r.get("status") != "confirmee":
            continue
        try:
            mois = pd.to_datetime(r["date_debut"]).month
            saisons[get_saison(mois)] += 1
        except Exception:
            pass

    meilleure = max(saisons, key=saisons.get)
    pire      = min(saisons, key=saisons.get)

    return {
        "repartition": saisons,
        "meilleure":   meilleure,
        "pire":        pire,
    }


# ══════════════════════════════════════════════════════════════
# MAIN — point d'entrée
# ══════════════════════════════════════════════════════════════
def main():
    data = lire_donnees()

    logements    = data.get("logements", [])
    reservations = data.get("reservations", [])

    if not logements:
        erreur("Aucun logement trouve pour ce proprietaire.")

    # Prix moyen du marché
    prix_moyen_marche = round(float(np.mean([l["prix"] for l in logements])), 2)

    resultats = []

    for logement in logements:
        logement_id = logement["id"]

        reservations_logement = [
            r for r in reservations if r["logement_id"] == logement_id
        ]

        stats           = analyser_logement(logement, reservations_logement, prix_moyen_marche)
        prediction      = predire_reservations(logement, reservations_logement)
        recommandations = generer_recommandations(logement, stats, prix_moyen_marche)
        saisonnalite    = analyser_saisonnalite(reservations_logement)

        resultats.append({
            "logement": {
                "id":    logement_id,
                "titre": logement["titre"],
                "prix":  logement["prix"],
            },
            "stats":           stats,
            "prediction":      prediction,
            "recommandations": recommandations,
            "saisonnalite":    saisonnalite,
        })

    # Trier par score décroissant
    resultats.sort(key=lambda x: x["stats"]["score"], reverse=True)

    score_global = round(float(np.mean([r["stats"]["score"] for r in resultats])), 0)

    rapport = {
        "score_global":      int(score_global),
        "prix_moyen_marche": prix_moyen_marche,
        "nb_logements":      len(logements),
        "logements":         resultats,
        "genere_le":         pd.Timestamp.now().strftime("%d/%m/%Y a %H:%M"),
    }

    # ✅ ensure_ascii=True : évite TOUS les problèmes d'encodage Windows
    print(json.dumps(rapport, ensure_ascii=True, indent=2))


if __name__ == "__main__":
    main()