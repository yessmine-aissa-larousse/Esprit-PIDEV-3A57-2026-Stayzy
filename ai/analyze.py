

import sys
import json
import warnings
import numpy as np
import pandas as pd
from sklearn.linear_model import LinearRegression

warnings.filterwarnings("ignore")

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')


# ══════════════════════════════════════════════════════════════
# UTILITAIRES
# ══════════════════════════════════════════════════════════════
def lire_donnees():
    if len(sys.argv) < 2:
        erreur("Aucune donnee recue de Symfony.")
    arg = sys.argv[1]
    if arg.endswith('.json') or '\\' in arg or '/' in arg:
        try:
            with open(arg, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            erreur(f"Impossible de lire le fichier JSON : {e}")
    try:
        return json.loads(arg)
    except json.JSONDecodeError as e:
        erreur(f"JSON invalide : {e}")


def erreur(msg):
    print(json.dumps({"erreur": msg}, ensure_ascii=True))
    sys.exit(1)


# ══════════════════════════════════════════════════════════════
# MODÈLE NLP SVM — Dataset + Pipeline entraîné
# ══════════════════════════════════════════════════════════════
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import SVC
from sklearn.pipeline import Pipeline
from sklearn.metrics import accuracy_score

DATASET_NLP = [
    ("combien j'ai gagne ce mois", "revenus"),
    ("quel est mon revenu total", "revenus"),
    ("combien d'argent j'ai fait", "revenus"),
    ("mes gains cette annee", "revenus"),
    ("chiffre d'affaires de mes logements", "revenus"),
    ("combien j'ai encaisse", "revenus"),
    ("quel est mon bilan financier", "revenus"),
    ("mes recettes totales", "revenus"),
    ("combien mes logements ont rapporte", "revenus"),
    ("revenus generes par mes annonces", "revenus"),
    ("bilan de mes locations", "revenus"),
    ("combien j ai fait comme argent", "revenus"),
    ("total des paiements recus", "revenus"),
    ("mes benefices sur stayzy", "revenus"),
    ("quel est mon meilleur logement", "performance"),
    ("quel logement performe le mieux", "performance"),
    ("mon top logement", "performance"),
    ("lequel est le plus rentable", "performance"),
    ("classement de mes logements", "performance"),
    ("quel appartement marche le mieux", "performance"),
    ("mon logement le plus populaire", "performance"),
    ("lequel a le plus de reservations", "performance"),
    ("comparaison entre mes logements", "performance"),
    ("quel est le moins performant", "performance"),
    ("logement avec le meilleur score", "performance"),
    ("lequel genere le plus de revenus", "performance"),
    ("quel logement est le plus demande", "performance"),
    ("mon meilleur investissement", "performance"),
    ("dois je creer une promotion", "promotion"),
    ("je veux faire une reduction", "promotion"),
    ("comment booster mes reservations", "promotion"),
    ("offrir une remise aux clients", "promotion"),
    ("mise en place d une promo", "promotion"),
    ("quelle reduction proposer", "promotion"),
    ("je veux attirer plus de clients avec une promo", "promotion"),
    ("mes logements ont ils des promotions actives", "promotion"),
    ("creer une offre speciale", "promotion"),
    ("discount pour mes logements", "promotion"),
    ("baisser le prix temporairement", "promotion"),
    ("offre de bienvenue pour nouveaux clients", "promotion"),
    ("faut il que je fasse une promo", "promotion"),
    ("j ai besoin de plus de photos", "photos"),
    ("mes photos sont insuffisantes", "photos"),
    ("comment ameliorer mes images", "photos"),
    ("ajouter des photos a mon annonce", "photos"),
    ("combien de photos faut il", "photos"),
    ("mes visuels ne sont pas assez bons", "photos"),
    ("photos de qualite pour mon logement", "photos"),
    ("quelles photos mettre sur mon annonce", "photos"),
    ("mes images attirent elles les clients", "photos"),
    ("prendre de meilleures photos", "photos"),
    ("logements avec peu de photos", "photos"),
    ("manque t il des photos a mon annonce", "photos"),
    ("nombre de photos recommande", "photos"),
    ("quel prix mettre pour mon logement", "prix"),
    ("mon tarif est il correct", "prix"),
    ("dois je augmenter mon prix", "prix"),
    ("dois je baisser mon prix", "prix"),
    ("comment fixer le bon prix", "prix"),
    ("mon prix est trop eleve", "prix"),
    ("prix par rapport au marche", "prix"),
    ("tarification optimale", "prix"),
    ("quel tarif pour attirer plus de clients", "prix"),
    ("je suis trop cher ou pas assez", "prix"),
    ("prix recommande pour mon appartement", "prix"),
    ("optimiser mon tarif nuitee", "prix"),
    ("prix en haute saison", "prix"),
    ("ajuster mes prix selon la saison", "prix"),
    ("comment rediger une bonne description", "description"),
    ("ma description est elle bonne", "description"),
    ("aide moi a ecrire mon annonce", "description"),
    ("comment presenter mon logement", "description"),
    ("texte pour mon annonce", "description"),
    ("je veux ameliorer ma description", "description"),
    ("ecrire une description professionnelle", "description"),
    ("quoi mettre dans la description", "description"),
    ("ma description est trop courte", "description"),
    ("contenu de mon annonce", "description"),
    ("comment bien decrire mon appartement", "description"),
    ("ameliorer le texte de mon logement", "description"),
    ("donne moi des conseils", "conseils"),
    ("comment ameliorer mes performances", "conseils"),
    ("aide moi a optimiser mes logements", "conseils"),
    ("que dois je faire pour progresser", "conseils"),
    ("conseils pour avoir plus de reservations", "conseils"),
    ("comment devenir un meilleur hote", "conseils"),
    ("mes points faibles", "conseils"),
    ("que puis je ameliorer", "conseils"),
    ("comment augmenter mon taux d occupation", "conseils"),
    ("suggestions pour mes annonces", "conseils"),
    ("bonnes pratiques pour les proprietaires", "conseils"),
    ("comment maximiser mes revenus", "conseils"),
    ("strategie pour mes logements", "conseils"),
    ("je veux progresser comment faire", "conseils"),
    ("combien de personnes ont mis en favori", "favoris"),
    ("mes logements sont ils aimes", "favoris"),
    ("nombre de likes sur mes annonces", "favoris"),
    ("qui s interesse a mes logements", "favoris"),
    ("beaucoup de favoris mais peu de reservations", "favoris"),
    ("pourquoi on met en favori sans reserver", "favoris"),
    ("mes logements sont dans les favoris", "favoris"),
    ("taux de conversion des favoris", "favoris"),
    ("logement avec le plus de likes", "favoris"),
    ("mon taux de reservation est faible", "reservation"),
    ("pourquoi peu de reservations", "reservation"),
    ("comment avoir plus de reservations", "reservation"),
    ("taux d occupation de mes logements", "reservation"),
    ("mes reservations ont baisse", "reservation"),
    ("nombre de reservations ce mois", "reservation"),
    ("pourquoi mes logements ne se louent pas", "reservation"),
    ("augmenter le nombre de locataires", "reservation"),
    ("taux d annulation eleve", "reservation"),
    ("trop d annulations comment reduire", "reservation"),
    ("mes reservations confirmees", "reservation"),
    ("pourquoi les clients annulent", "reservation"),
    ("fideliser les clients", "reservation"),
    ("quelle est la meilleure saison pour louer", "saisonnalite"),
    ("en ete j ai plus de reservations", "saisonnalite"),
    ("periodes creuses de l annee", "saisonnalite"),
    ("quand creer des promotions saisonnieres", "saisonnalite"),
    ("haute saison et basse saison", "saisonnalite"),
    ("vacances d ete et reservations", "saisonnalite"),
    ("mois les plus calmes", "saisonnalite"),
    ("quand louer le plus", "saisonnalite"),
    ("saisonnalite de mes locations", "saisonnalite"),
    ("mois de janvier peu de reservations", "saisonnalite"),
    ("comment gerer les periodes creuses", "saisonnalite"),
    ("vacances scolaires et reservations", "saisonnalite"),
    ("ramadan et impact sur les locations", "saisonnalite"),
]


def _entrainer_svm():
    """Entraîne le pipeline TF-IDF + SVM et retourne pipeline + accuracy."""
    questions  = [d[0] for d in DATASET_NLP]
    intentions = [d[1] for d in DATASET_NLP]

    pipeline = Pipeline([
        ('tfidf', TfidfVectorizer(
            ngram_range=(1, 2),
            min_df=1,
            max_features=5000,
            sublinear_tf=True,
        )),
        ('svm', SVC(
            kernel='linear',
            C=1.0,
            probability=True,
            random_state=42,
        ))
    ])

    pipeline.fit(questions, intentions)
    preds    = pipeline.predict(questions)
    accuracy = round(accuracy_score(intentions, preds) * 100, 1)
    return pipeline, accuracy


def _generer_reponse_svm(intention, question, logements_ctx):
    """Génère la réponse personnalisée selon l'intention SVM."""
    total_rev    = round(sum(l["revenu_total"] for l in logements_ctx), 2)
    total_res    = sum(l["nb_reservations"] for l in logements_ctx)
    meilleur     = max(logements_ctx, key=lambda l: l["revenu_total"]) if logements_ctx else None
    moins_bon    = min(logements_ctx, key=lambda l: l["nb_reservations"]) if logements_ctx else None
    prix_moyen   = round(np.mean([l["prix"] for l in logements_ctx]), 2) if logements_ctx else 0
    sans_promo   = [l for l in logements_ctx if not l["a_promo"]]
    peu_photos   = [l for l in logements_ctx if l["nb_photos"] < 3]
    top_fav      = max(logements_ctx, key=lambda l: l["nb_favoris"]) if logements_ctx else None
    nb_logements = len(logements_ctx)

    if intention == "revenus":
        if total_res == 0:
            return "Vous n'avez pas encore de reservations confirmees. Commencez par optimiser vos annonces !"
        rep = f"Vos {nb_logements} logement(s) ont genere {total_rev} EUR pour {total_res} reservation(s)."
        if meilleur:
            rep += f" Le meilleur est '{meilleur['titre']}' avec {meilleur['revenu_total']} EUR."
        return rep

    elif intention == "performance":
        if not meilleur:
            return "Aucune donnee de performance disponible. Attendez vos premieres reservations !"
        rep = f"Votre meilleur logement est '{meilleur['titre']}' : {meilleur['nb_reservations']} reservation(s) pour {meilleur['revenu_total']} EUR."
        if moins_bon and moins_bon["titre"] != meilleur["titre"]:
            rep += f" Le moins actif est '{moins_bon['titre']}' avec {moins_bon['nb_reservations']} reservation(s)."
        return rep

    elif intention == "promotion":
        if sans_promo:
            noms = ", ".join([l["titre"] for l in sans_promo[:3]])
            return (f"Je recommande une promotion sur : {noms}. "
                    f"Une reduction de 15-20% peut declencher des reservations. "
                    f"Utilisez l'onglet 'Meilleures periodes' pour savoir quand la lancer !")
        return "Tous vos logements ont deja une promotion active. Surveillez les resultats dans 2 semaines."

    elif intention == "photos":
        if peu_photos:
            noms = ", ".join([l["titre"] for l in peu_photos[:3]])
            return (f"Ces logements manquent de photos : {noms}. "
                    f"Ajoutez minimum 5 photos (salon, chambre, salle de bain, cuisine, vue exterieure). "
                    f"La lumiere naturelle est essentielle !")
        return "Tous vos logements ont suffisamment de photos. Assurez-vous de leur qualite."

    elif intention == "prix":
        return (f"Votre prix moyen est de {prix_moyen} EUR/nuit. "
                f"Conseil : +15-25% en juillet-aout et fetes, -10-15% en janvier-fevrier. "
                f"Utilisez l'onglet 'Prix optimal' pour une analyse detaillee !")

    elif intention == "description":
        return ("Pour une description efficace : "
                "1) Accroche emotive sur l'experience, "
                "2) Equipements cles (wifi, parking...), "
                "3) Proximite des attractions, "
                "4) Regles de la maison. "
                "Utilisez l'onglet 'Generateur de description' pour un texte complet automatique !")

    elif intention == "conseils":
        conseils = []
        if sans_promo:
            conseils.append(f"Creer une promotion sur '{sans_promo[0]['titre']}'")
        if peu_photos:
            conseils.append(f"Ajouter des photos a '{peu_photos[0]['titre']}'")
        if total_res < 5:
            conseils.append("Baisser les prix de 10% pendant 30 jours pour les premieres reservations")
        if not conseils:
            conseils.append("Collecter plus d'avis clients pour booster votre credibilite")
        return ("Mes priorites : "
                + " | ".join([f"{i+1}) {c}" for i, c in enumerate(conseils[:3])])
                + ". Voulez-vous des details ?")

    elif intention == "favoris":
        if not top_fav:
            return "Aucune donnee de favoris disponible."
        rep = f"'{top_fav['titre']}' est le plus aime avec {top_fav['nb_favoris']} favori(s). "
        if top_fav["nb_reservations"] < 3 and top_fav["nb_favoris"] >= 5:
            rep += "Beaucoup d'interet mais peu de reservations : une promo de 10% peut convertir ces visiteurs !"
        return rep

    elif intention == "reservation":
        rep = f"Vous avez {total_res} reservation(s) confirmee(s). "
        vides = [l for l in logements_ctx if l["nb_reservations"] == 0]
        if vides:
            rep += f"{len(vides)} logement(s) sans reservation : verifiez prix et photos. "
        rep += "Repondez vite aux messages et maintenez votre calendrier a jour."
        return rep

    elif intention == "saisonnalite":
        return ("Haute saison : juillet-aout (vacances estivales). "
                "Periodes creuses : janvier-fevrier et novembre. "
                "Strategie : prix +25% en ete, promo -20% en janvier. "
                "Cliquez sur 'Meilleures periodes' pour une analyse personnalisee !")

    return (f"Je gere {nb_logements} logement(s), {total_res} reservation(s), {total_rev} EUR de revenus. "
            f"Posez-moi des questions sur : revenus, prix, photos, promotions, descriptions ou reservations.")


# ══════════════════════════════════════════════════════════════
# MODE : CHAT IA (avec vrai modèle SVM)
# ══════════════════════════════════════════════════════════════
def mode_chat(data):
    question     = data.get("question", "").strip()
    logements    = data.get("logements", [])
    reservations = data.get("reservations", [])

    if not question:
        erreur("Question vide.")

    # Construire le contexte des logements
    ctx = []
    for l in logements:
        res_log    = [r for r in reservations if r["logement_id"] == l["id"]]
        confirmees = [r for r in res_log if r["status"] == "confirmee"]
        revenu     = sum(r["prix_total"] for r in confirmees)
        ctx.append({
            "titre":          l["titre"],
            "prix":           l["prix"],
            "nb_reservations":len(confirmees),
            "revenu_total":   round(revenu, 2),
            "nb_photos":      l.get("nb_photos", 0),
            "nb_favoris":     l.get("nb_favoris", 0),
            "a_promo":        l.get("a_promo", False),
        })

    # ✅ Entraîner le SVM + prédire l'intention
    pipeline, accuracy  = _entrainer_svm()
    intention, confiance = (
        pipeline.predict([question.lower()])[0],
        round(max(pipeline.predict_proba([question.lower()])[0]) * 100, 1)
    )

    # Générer la réponse basée sur l'intention
    reponse = _generer_reponse_svm(intention, question, ctx)

    print(json.dumps({
        "reponse":   reponse,
        "intention": intention,
        "confiance": confiance,
        "accuracy":  accuracy,
        "mode":      "chat"
    }, ensure_ascii=True))


# ══════════════════════════════════════════════════════════════
# MODE : GÉNÉRATEUR DE DESCRIPTION
# ══════════════════════════════════════════════════════════════
def mode_description(data):
    logement = data.get("logement", {})

    titre      = logement.get("titre", "logement")
    prix       = logement.get("prix", 0)
    superficie = logement.get("superficie", 0)
    chambres   = logement.get("nombre_chambres", 1)
    sdb        = logement.get("nombre_salle_de_bain", 1)
    amenites   = logement.get("amenites", [])
    ville      = logement.get("ville", "")
    categorie  = logement.get("categorie", "")

    # Construire les blocs de description
    parties = []

    # Accroche
    accroches = [
        f"Decouvrez '{titre}', un logement d'exception qui vous attend",
        f"Bienvenue dans '{titre}', votre havre de paix ideal",
        f"'{titre}' vous ouvre ses portes pour un sejour inoubliable",
        f"Evadez-vous dans '{titre}', un espace pensé pour votre confort",
    ]
    import random
    parties.append(accroches[hash(titre) % len(accroches)])
    if ville:
        parties[-1] += f" au coeur de {ville}."
    else:
        parties[-1] += "."

    # Espace
    desc_espace = f"Ce logement de {superficie} m2 " if superficie else "Ce logement spacieux "
    desc_espace += f"dispose de {chambres} chambre(s) et {sdb} salle(s) de bain"
    desc_espace += f", offrant tout le confort necessaire pour votre sejour."
    parties.append(desc_espace)

    # Amenités
    if amenites:
        amenites_fr = {
            "wifi": "connexion WiFi haut debit",
            "parking": "parking prive",
            "piscine": "piscine privee",
            "climatisation": "climatisation",
            "cuisine": "cuisine entierement equipee",
            "lave_linge": "lave-linge",
            "television": "television ecran plat",
            "balcon": "balcon avec vue",
        }
        liste = [amenites_fr.get(a, a) for a in amenites[:5]]
        parties.append(f"Vous profiterez de : {', '.join(liste)}.")

    # Prix
    parties.append(
        f"A partir de {prix} EUR/nuit, ce logement offre un excellent rapport qualite-prix "
        f"pour un sejour en toute serenite."
    )

    # Appel à l'action
    parties.append(
        "Reservez des maintenant et profitez d'une experience unique. "
        "N'hesitez pas a nous contacter pour toute question !"
    )

    description = " ".join(parties)

    # Version courte (accroche + espace)
    description_courte = " ".join(parties[:2])

    print(json.dumps({
        "description":        description,
        "description_courte": description_courte,
        "nb_mots":            len(description.split()),
        "mode":               "description"
    }, ensure_ascii=True))


# ══════════════════════════════════════════════════════════════
# MODE : SUGGESTION DE PRIX OPTIMAL
# ══════════════════════════════════════════════════════════════
def mode_prix(data):
    logement     = data.get("logement", {})
    reservations = data.get("reservations", [])
    tous_logements = data.get("tous_logements", [])

    prix_actuel  = logement.get("prix", 0)
    superficie   = logement.get("superficie", 50)
    chambres     = logement.get("nombre_chambres", 1)
    amenites     = logement.get("amenites", [])
    nb_photos    = logement.get("nb_photos", 0)
    note_moyenne = logement.get("note_moyenne", 0) or 0

    # Prix moyen du marché
    if tous_logements:
        prix_marche = round(float(np.mean([l["prix"] for l in tous_logements])), 2)
    else:
        prix_marche = prix_actuel

    # Calcul du prix suggéré
    prix_base = prix_marche

    # Bonus/malus selon les caractéristiques
    score_amenites = min(len(amenites) * 3, 20)  # max +20%
    score_photos   = min(nb_photos * 2, 10)        # max +10%
    score_note     = round((note_moyenne / 5) * 15, 1) if note_moyenne > 0 else 0  # max +15%
    score_surface  = min((superficie - 30) * 0.1, 15) if superficie > 30 else 0    # max +15%
    score_chambres = min((chambres - 1) * 5, 15)   # max +15%

    bonus_total = score_amenites + score_photos + score_note + score_surface + score_chambres
    prix_suggere = round(prix_base * (1 + bonus_total / 100), 0)

    # Fourchettes
    prix_min_suggere = round(prix_suggere * 0.85, 0)
    prix_max_suggere = round(prix_suggere * 1.15, 0)

    # Analyse des réservations par prix
    tendance_prix = "stable"
    if reservations:
        df = pd.DataFrame(reservations)
        if "prix_total" in df.columns and len(df) >= 3:
            confirmees = df[df["status"] == "confirmee"]
            if len(confirmees) > 0:
                revenu_moyen = confirmees["prix_total"].mean()
                if revenu_moyen > prix_actuel * 3:
                    tendance_prix = "hausse"
                elif revenu_moyen < prix_actuel * 1.5:
                    tendance_prix = "baisse"

    # Conseils tarifaires
    conseils = []
    diff = round(((prix_actuel - prix_suggere) / prix_suggere) * 100, 1)

    if diff > 15:
        conseils.append(f"Votre prix est {abs(diff)}% au-dessus du prix optimal. Une baisse pourrait augmenter vos reservations.")
    elif diff < -15:
        conseils.append(f"Vous etes {abs(diff)}% en dessous du prix optimal. Vous pouvez augmenter sans perdre de clients.")
    else:
        conseils.append("Votre prix est bien positionne par rapport au marche.")

    if nb_photos < 5:
        conseils.append("Ajoutez plus de photos pour justifier un prix plus eleve.")
    if note_moyenne >= 4.5:
        conseils.append(f"Votre excellente note ({note_moyenne}/5) vous permet de pratiquer un prix premium.")

    # Prix par saison
    prix_saison = {
        "printemps": round(prix_suggere * 1.05, 0),
        "ete":       round(prix_suggere * 1.25, 0),
        "automne":   round(prix_suggere * 1.0, 0),
        "hiver":     round(prix_suggere * 0.90, 0),
    }

    print(json.dumps({
        "prix_actuel":       prix_actuel,
        "prix_suggere":      int(prix_suggere),
        "prix_min":          int(prix_min_suggere),
        "prix_max":          int(prix_max_suggere),
        "prix_marche":       prix_marche,
        "diff_pourcent":     diff,
        "tendance":          tendance_prix,
        "score_amenites":    score_amenites,
        "score_photos":      score_photos,
        "score_note":        score_note,
        "prix_par_saison":   prix_saison,
        "conseils":          conseils,
        "mode":              "prix"
    }, ensure_ascii=True))


# ══════════════════════════════════════════════════════════════
# MODE : MEILLEURES PÉRIODES POUR LES PROMOS
# ══════════════════════════════════════════════════════════════
def mode_periodes(data):
    logement     = data.get("logement", {})
    reservations = data.get("reservations", [])

    if not reservations:
        # Conseils génériques si pas de données
        print(json.dumps({
            "periodes_creuses": [
                {"periode": "Janvier-Fevrier", "conseil": "Periode la plus creuse. Promotion de 25-30% recommandee."},
                {"periode": "Novembre",        "conseil": "Faible activite. Promotion de 15-20% pour maintenir le taux."},
            ],
            "periodes_fortes": [
                {"periode": "Juillet-Aout",    "conseil": "Haute saison. Pas de promotion necessaire, augmentez vos prix."},
                {"periode": "Decembre",        "conseil": "Fetes de fin d'annee. Demande elevee, prix premium possible."},
            ],
            "meilleur_moment_promo": "Janvier",
            "promo_recommandee":     25,
            "analyse":               "Basee sur les tendances generales du marche (donnees insuffisantes pour votre logement).",
            "mode":                  "periodes"
        }, ensure_ascii=True))
        return

    df = pd.DataFrame(reservations)
    df["date_debut"] = pd.to_datetime(df["date_debut"], errors="coerce")
    df = df.dropna(subset=["date_debut"])
    df["mois"] = df["date_debut"].dt.month
    df["mois_nom"] = df["date_debut"].dt.strftime("%B")

    # Réservations confirmées par mois
    confirmees = df[df["status"] == "confirmee"]
    par_mois   = confirmees.groupby("mois").size().reset_index(name="nb")

    # Compléter les mois manquants
    tous_mois = pd.DataFrame({"mois": range(1, 13)})
    par_mois  = tous_mois.merge(par_mois, on="mois", how="left").fillna(0)

    noms_mois = ["Janvier","Fevrier","Mars","Avril","Mai","Juin",
                 "Juillet","Aout","Septembre","Octobre","Novembre","Decembre"]
    par_mois["nom"] = [noms_mois[i-1] for i in par_mois["mois"]]

    moyenne = par_mois["nb"].mean()

    # Périodes creuses (< 70% de la moyenne)
    creuses = par_mois[par_mois["nb"] < moyenne * 0.7].sort_values("nb")
    # Périodes fortes (> 130% de la moyenne)
    fortes  = par_mois[par_mois["nb"] > moyenne * 1.3].sort_values("nb", ascending=False)

    periodes_creuses = []
    for _, row in creuses.head(4).iterrows():
        pct = round((1 - row["nb"] / (moyenne + 0.01)) * 30 + 10)
        pct = min(max(pct, 10), 40)
        periodes_creuses.append({
            "periode":  row["nom"],
            "nb_res":   int(row["nb"]),
            "conseil":  f"Seulement {int(row['nb'])} reservation(s). Promotion de {pct}% recommandee.",
            "promo_pct": pct,
        })

    periodes_fortes = []
    for _, row in fortes.head(4).iterrows():
        periodes_fortes.append({
            "periode": row["nom"],
            "nb_res":  int(row["nb"]),
            "conseil": f"{int(row['nb'])} reservation(s). Haute demande, pas de promotion necessaire.",
        })

    # Meilleur moment pour une promo
    if len(creuses) > 0:
        meilleur_mois = creuses.iloc[0]["nom"]
        promo_pct     = min(round((1 - creuses.iloc[0]["nb"] / (moyenne + 0.01)) * 30 + 10), 40)
    else:
        meilleur_mois = "Janvier"
        promo_pct     = 15

    print(json.dumps({
        "periodes_creuses":      periodes_creuses,
        "periodes_fortes":       periodes_fortes,
        "meilleur_moment_promo": meilleur_mois,
        "promo_recommandee":     int(promo_pct),
        "analyse":               f"Analyse basee sur {len(confirmees)} reservation(s) confirmee(s).",
        "mode":                  "periodes"
    }, ensure_ascii=True))


# ══════════════════════════════════════════════════════════════
# MODE : RAPPORT COMPLET (appelé par AiRapportController)
# ══════════════════════════════════════════════════════════════
def mode_rapport(data):
    logements    = data.get("logements", [])
    reservations = data.get("reservations", [])

    if not logements:
        erreur("Aucun logement trouve pour ce proprietaire.")

    prix_moyen_marche = round(float(np.mean([l["prix"] for l in logements])), 2)

    resultats = []
    for logement in logements:
        logement_id           = logement["id"]
        reservations_logement = [r for r in reservations if r["logement_id"] == logement_id]

        stats           = _analyser_logement(logement, reservations_logement, prix_moyen_marche)
        prediction      = _predire_reservations(logement, reservations_logement)
        recommandations = _generer_recommandations(logement, stats, prix_moyen_marche)
        saisonnalite    = _analyser_saisonnalite(reservations_logement)

        resultats.append({
            "logement":        {"id": logement_id, "titre": logement["titre"], "prix": logement["prix"]},
            "stats":           stats,
            "prediction":      prediction,
            "recommandations": recommandations,
            "saisonnalite":    saisonnalite,
        })

    resultats.sort(key=lambda x: x["stats"]["score"], reverse=True)
    score_global = round(float(np.mean([r["stats"]["score"] for r in resultats])), 0)

    rapport = {
        "score_global":      int(score_global),
        "prix_moyen_marche": prix_moyen_marche,
        "nb_logements":      len(logements),
        "logements":         resultats,
        "genere_le":         pd.Timestamp.now().strftime("%d/%m/%Y a %H:%M"),
    }
    print(json.dumps(rapport, ensure_ascii=True, indent=2))


def _analyser_logement(logement, reservations_logement, prix_moyen_marche):
    confirmees      = [r for r in reservations_logement if r["status"] == "confirmee"]
    annulees        = [r for r in reservations_logement if r["status"] == "annulee"]
    total           = len(reservations_logement)
    revenu_total    = sum(r["prix_total"] for r in confirmees)
    taux_annulation = round(len(annulees) / total * 100, 1) if total > 0 else 0

    durees = []
    for r in confirmees:
        try:
            debut = pd.to_datetime(r["date_debut"])
            fin   = pd.to_datetime(r["date_fin"])
            durees.append((fin - debut).days)
        except Exception:
            pass
    duree_moyenne = round(np.mean(durees), 1) if durees else 0

    prix        = logement["prix"]
    diff_marche = round(((prix - prix_moyen_marche) / prix_moyen_marche) * 100, 1) if prix_moyen_marche > 0 else 0

    score = _calculer_score(
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


def _calculer_score(nb_confirmees, taux_annulation, note_moyenne,
                    nb_favoris, diff_marche, a_promo, nb_photos, description_longue):
    score = 0
    score += min(nb_confirmees * 2, 35)
    if note_moyenne and note_moyenne > 0:
        score += round((note_moyenne / 5) * 20)
    score += min(nb_favoris, 10)
    if abs(diff_marche) < 10:   score += 10
    elif abs(diff_marche) < 20: score += 5
    if taux_annulation < 10:    score += 10
    elif taux_annulation < 25:  score += 5
    if nb_photos >= 3:          score += 5
    elif nb_photos >= 1:        score += 2
    if description_longue:      score += 5
    if a_promo:                 score += 5
    return min(score, 100)


def _predire_reservations(logement, reservations_logement):
    if len(reservations_logement) < 3:
        return {"min": 1, "max": 3, "methode": "estimation_defaut"}

    df = pd.DataFrame(reservations_logement)
    df["date_debut"] = pd.to_datetime(df["date_debut"], errors="coerce")
    df = df.dropna(subset=["date_debut"])
    df["mois"] = df["date_debut"].dt.to_period("M")

    rpm = (df[df["status"] == "confirmee"]
           .groupby("mois").size().reset_index(name="nb"))

    if len(rpm) < 2:
        return {"min": 1, "max": 4, "methode": "estimation_defaut"}

    X = np.arange(len(rpm)).reshape(-1, 1)
    model = LinearRegression()
    model.fit(X, rpm["nb"].values)
    pred = max(0, model.predict([[len(rpm)]])[0])

    return {
        "min":      int(max(0, round(pred * 0.7))),
        "max":      int(round(pred * 1.3)),
        "tendance": "hausse" if model.coef_[0] > 0.1 else ("baisse" if model.coef_[0] < -0.1 else "stable"),
        "methode":  "regression_lineaire",
    }


def _generer_recommandations(logement, stats, prix_moyen_marche):
    recommandations = []
    prix            = logement["prix"]
    nb_favoris      = logement.get("nb_favoris") or 0
    a_promo         = logement.get("a_promo") or False
    nb_photos       = logement.get("nb_photos") or 0
    nb_confirmees   = stats["nb_confirmees"]
    taux_annulation = stats["taux_annulation"]
    diff_marche     = stats["diff_prix_marche"]

    if diff_marche > 20:
        recommandations.append({"priorite": "haute", "icone": "bi-cash-coin",
            "titre": "Prix trop eleve",
            "texte": f"Votre prix ({prix} EUR) est {diff_marche}% au-dessus du marche. Envisagez de baisser."})
    elif diff_marche < -20:
        recommandations.append({"priorite": "info", "icone": "bi-graph-up-arrow",
            "titre": "Potentiel inexploite",
            "texte": f"Votre prix ({prix} EUR) est {abs(diff_marche)}% en dessous du marche."})
    if nb_favoris >= 5 and nb_confirmees < 3:
        recommandations.append({"priorite": "haute", "icone": "bi-heart-fill",
            "titre": "Fort interet, peu de reservations",
            "texte": f"{nb_favoris} favoris mais peu de reservations. Une promo de 10-15% peut aider."})
    if not a_promo and nb_confirmees < 5:
        recommandations.append({"priorite": "moyenne", "icone": "bi-tag-fill",
            "titre": "Lancez une promotion",
            "texte": "Peu de reservations. Une promotion de 15-20% peut booster la visibilite."})
    if nb_photos < 3:
        recommandations.append({"priorite": "moyenne", "icone": "bi-camera-fill",
            "titre": "Ajoutez des photos",
            "texte": f"Seulement {nb_photos} photo(s). Les logements avec 5+ photos recoivent 3x plus de reservations."})
    if taux_annulation > 30:
        recommandations.append({"priorite": "haute", "icone": "bi-exclamation-triangle-fill",
            "titre": "Taux annulation eleve",
            "texte": f"Taux de {taux_annulation}%. Verifiez la coherence de votre annonce."})
    if nb_confirmees >= 10 and taux_annulation < 15:
        recommandations.append({"priorite": "succes", "icone": "bi-trophy-fill",
            "titre": "Excellent logement !",
            "texte": "Logement tres performant. Pensez a augmenter le prix en haute saison."})
    if not logement.get("description_longue"):
        recommandations.append({"priorite": "info", "icone": "bi-pencil-fill",
            "titre": "Enrichissez la description",
            "texte": "Une description detaillee rassure les clients et augmente le taux de conversion."})
    if not recommandations:
        recommandations.append({"priorite": "succes", "icone": "bi-check-circle-fill",
            "titre": "Tout est bon !",
            "texte": "Votre logement est bien optimise. Continuez ainsi !"})
    return recommandations


def _analyser_saisonnalite(reservations_logement):
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
            saisons[get_saison(pd.to_datetime(r["date_debut"]).month)] += 1
        except Exception:
            pass

    return {
        "repartition": saisons,
        "meilleure":   max(saisons, key=saisons.get),
        "pire":        min(saisons, key=saisons.get),
    }


# ══════════════════════════════════════════════════════════════
# MAIN
# ══════════════════════════════════════════════════════════════
def main():
    data = lire_donnees()
    mode = data.get("mode", "rapport")

    if mode == "rapport":
        mode_rapport(data)
    elif mode == "chat":
        mode_chat(data)
    elif mode == "description":
        mode_description(data)
    elif mode == "prix":
        mode_prix(data)
    elif mode == "periodes":
        mode_periodes(data)
    else:
        erreur(f"Mode inconnu : {mode}")


if __name__ == "__main__":
    main()