import requests, pickle
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score

# Récupérer les données depuis Symfony
r = requests.get('http://127.0.0.1:8000/api/export-training-data')
data = r.json()

if len(data) < 10:
    print("⚠️ Pas assez de données pour entraîner le modèle.")
    exit()

df = pd.DataFrame(data)
X  = df[['nb_reservations_passees','duree_mois','moyenne_duree_passee','nombre_personnes']]
y  = df['label']

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

model = RandomForestClassifier(n_estimators=100, random_state=42)
model.fit(X_train, y_train)

print(f"🎯 Précision : {accuracy_score(y_test, model.predict(X_test))*100:.1f}%")

with open('model.pkl', 'wb') as f:
    pickle.dump(model, f)

print("✅ model.pkl sauvegardé !")