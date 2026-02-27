"""
train_model.py
==============
Entraîne un modèle ML qui prédit la qualité d'une réponse à une réclamation.

Labels :
  - 'excellent'  → réponse claire, longue, professionnelle
  - 'acceptable' → réponse correcte mais courte ou générique
  - 'faible'     → réponse trop courte, vague ou inutile

Lancer UNE SEULE FOIS :
  python train_model.py
→ génère model.pkl
"""

import pickle
import os
from sklearn.pipeline import Pipeline
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report

# ──────────────────────────────────────────────────────────────
# DONNÉES D'ENTRAÎNEMENT
# (contenu de réponse → qualité)
# ──────────────────────────────────────────────────────────────

data = [
    # EXCELLENT
    ("Bonjour, nous avons bien pris en compte votre réclamation concernant votre appartement. Après vérification, nous confirmons que le problème a été identifié et une intervention est planifiée sous 48h. Nous vous prions d'accepter nos excuses pour la gêne occasionnée.", "excellent"),
    ("Suite à votre signalement, notre équipe technique a analysé la situation et a procédé aux corrections nécessaires. Le paiement refusé a été remboursé et vous recevrez une confirmation par email dans les prochaines heures.", "excellent"),
    ("Nous avons transmis votre réclamation au service concerné. Une enquête interne a été ouverte et nous vous tiendrons informé de l'avancement. Un responsable vous contactera dans les 24 heures pour vous donner une réponse détaillée.", "excellent"),
    ("Votre réclamation a été traitée en priorité. Le bug signalé sur notre application a été corrigé dans la dernière mise à jour. Nous vous recommandons de vider le cache et de relancer l'application pour bénéficier des améliorations.", "excellent"),
    ("Nous comprenons votre frustration et nous nous excusons sincèrement pour les désagréments causés. Votre dossier a été escaladé au responsable de région qui vous contactera personnellement dans les meilleurs délais.", "excellent"),
    ("Après examen de votre dossier, nous avons constaté une erreur de notre part. Un remboursement complet de 150 euros a été initié et apparaîtra sur votre compte sous 3 à 5 jours ouvrés.", "excellent"),
    ("Nous avons bien reçu votre signalement concernant l'état de la villa. Un technicien a été mandaté pour effectuer une inspection complète dès demain matin entre 9h et 12h. Merci de votre patience.", "excellent"),
    ("Votre problème de connexion a été résolu suite à une mise à jour de nos serveurs. Veuillez réessayer de vous connecter. Si le problème persiste, notre support est disponible 24/7 au numéro indiqué.", "excellent"),

    # ACCEPTABLE
    ("Votre réclamation a été prise en compte. Nous traitons votre demande dans les meilleurs délais.", "acceptable"),
    ("Merci pour votre signalement. Notre équipe va examiner votre dossier prochainement.", "acceptable"),
    ("Nous avons bien reçu votre message et nous vous répondrons dès que possible.", "acceptable"),
    ("Votre demande est en cours de traitement. Vous serez contacté sous peu.", "acceptable"),
    ("Le problème que vous signalez a été noté. Nous allons vérifier et vous tenir informé.", "acceptable"),
    ("Merci de nous avoir contactés. Nous prenons votre réclamation au sérieux.", "acceptable"),
    ("Votre dossier est en cours d'examen par notre service compétent.", "acceptable"),
    ("Nous traitons votre demande et reviendrons vers vous rapidement.", "acceptable"),

    # FAIBLE
    ("Ok.", "faible"),
    ("Reçu.", "faible"),
    ("On va voir.", "faible"),
    ("D'accord.", "faible"),
    ("Merci.", "faible"),
    ("Votre message a été reçu.", "faible"),
    ("Nous verrons.", "faible"),
    ("Traitement en cours.", "faible"),
]

# ──────────────────────────────────────────────────────────────
# ENTRAÎNEMENT
# ──────────────────────────────────────────────────────────────

texts, labels = zip(*data)
texts, labels = list(texts), list(labels)

X_train, X_test, y_train, y_test = train_test_split(
    texts, labels, test_size=0.2, random_state=42, stratify=labels
)

pipeline = Pipeline([
    ('tfidf', TfidfVectorizer(ngram_range=(1, 2), max_features=3000)),
    ('clf',   LogisticRegression(max_iter=1000, C=1.0)),
])

pipeline.fit(X_train, y_train)

print("=" * 50)
print("  Résultats du modèle — Qualité des réponses")
print("=" * 50)
y_pred = pipeline.predict(X_test)
print(classification_report(y_test, y_pred, zero_division=0))

# ──────────────────────────────────────────────────────────────
# SAUVEGARDE
# ──────────────────────────────────────────────────────────────

with open("model.pkl", "wb") as f:
    pickle.dump(pipeline, f)

print("✅ model.pkl sauvegardé !")
print("🚀 Lance maintenant : python app.py")