"""
generate_fake_data.py
─────────────────────
Génère des réservations fictives dans la BDD pidev_db
pour alimenter le modèle IA d'analyse des logements.

Usage :
    python ai/generate_fake_data.py
"""

import mysql.connector
import random
from datetime import datetime, timedelta

# ══════════════════════════════════════════════
# CONFIG BDD — adapte si nécessaire
# ══════════════════════════════════════════════
DB_CONFIG = {
    "host":     "127.0.0.1",
    "port":     3306,
    "user":     "root",
    "password": "",
    "database": "pidev_db",
}

# ══════════════════════════════════════════════
# CONNEXION
# ══════════════════════════════════════════════
conn   = mysql.connector.connect(**DB_CONFIG)
cursor = conn.cursor()

# ══════════════════════════════════════════════
# 1. Récupérer tous les logements + leur proprio
# ══════════════════════════════════════════════
cursor.execute("SELECT id, prix, proprietaire_id FROM logement")
logements = cursor.fetchall()

if not logements:
    print("❌ Aucun logement trouvé en BDD. Publie d'abord des logements.")
    exit()

print(f"✅ {len(logements)} logement(s) trouvé(s)")

# ══════════════════════════════════════════════
# 2. Récupérer tous les clients (ROLE_CLIENT)
# ══════════════════════════════════════════════
cursor.execute("SELECT id FROM `user` WHERE roles LIKE '%ROLE_CLIENT%'")
clients = cursor.fetchall()

if not clients:
    print("⚠️  Aucun client trouvé. On utilisera les utilisateurs disponibles.")
    cursor.execute("SELECT id FROM `user` LIMIT 10")
    clients = cursor.fetchall()

if not clients:
    print("❌ Aucun utilisateur en BDD.")
    exit()

print(f"✅ {len(clients)} client(s) trouvé(s)")

# ══════════════════════════════════════════════
# 3. Supprimer les anciennes réservations fictives
#    (optionnel — commente si tu veux garder)
# ══════════════════════════════════════════════
cursor.execute("DELETE FROM reservation WHERE message_demande = 'FAKE_DATA_AI'")
conn.commit()
print("🗑️  Anciennes données fictives supprimées")

# ══════════════════════════════════════════════
# 4. Générer les réservations fictives
# ══════════════════════════════════════════════
STATUTS = ["confirmee", "confirmee", "confirmee", "annulee", "en_attente"]

# Chaque logement a un "score de popularité" différent
# → permet à l'IA de voir des différences entre logements
def get_popularite(logement_id, prix):
    """Simule un taux de popularité basé sur le prix."""
    if prix < 50:
        return random.randint(15, 25)   # pas cher = très populaire
    elif prix < 100:
        return random.randint(8, 15)    # moyen = populaire
    else:
        return random.randint(2, 8)     # cher = moins populaire

reservations_inserees = 0

for logement_id, prix, proprietaire_id in logements:
    nb_reservations = get_popularite(logement_id, prix)

    for _ in range(nb_reservations):
        # Date de début aléatoire dans les 12 derniers mois
        jours_passes = random.randint(1, 365)
        date_debut   = datetime.now() - timedelta(days=jours_passes)

        # Durée du séjour : 1 à 14 nuits
        duree        = random.randint(1, 14)
        date_fin     = date_debut + timedelta(days=duree)

        # Prix total
        prix_total   = round(prix * duree * random.uniform(0.85, 1.15), 2)

        # Client aléatoire (pas le propriétaire)
        client       = random.choice(clients)
        client_id    = client[0]

        # Statut
        statut       = random.choice(STATUTS)

        # Nombre de personnes
        nb_personnes = random.randint(1, 4)

        cursor.execute("""
            INSERT INTO reservation
                (date_debut, date_fin, nombre_personnes, prix_total, status,
                 message_demande, user_id, logement_id)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """, (
            date_debut.strftime("%Y-%m-%d"),
            date_fin.strftime("%Y-%m-%d"),
            nb_personnes,
            prix_total,
            statut,
            "FAKE_DATA_AI",   # marqueur pour identifier les données fictives
            client_id,
            logement_id,
        ))
        reservations_inserees += 1

conn.commit()
cursor.close()
conn.close()

print(f"\n🎉 Terminé ! {reservations_inserees} réservations fictives insérées.")
print("Tu peux maintenant générer le rapport IA depuis Stayzy.")