# Stayzy – Plateforme de Gestion Locative et de Réservation de Logements

## Overview

Ce projet a été développé dans le cadre du module **PIDEV – Projet d’Intégration et de Développement** du programme d’ingénierie 3ème année à **Esprit School of Engineering** (Année académique 2025–2026).

**Stayzy** est une plateforme web de gestion locative permettant aux utilisateurs de rechercher des logements, comparer différentes offres, effectuer des réservations en ligne et gérer leurs favoris.
La plateforme intègre également des fonctionnalités avancées telles que la gestion des visites, la gestion des réclamations et un espace administrateur permettant de superviser et gérer les différentes ressources du système.

---

## Features

La plateforme propose plusieurs fonctionnalités principales :

* Authentification et gestion des comptes utilisateurs
* Gestion des logements
* Système de réservation de logements et des commandes
* Comparaison de logements
* Gestion des réclamations
* Planification et gestion des visites
* Module de discussion (Forum)
* Tableau de bord administrateur
* API pour certaines fonctionnalités
* Architecture métier avancée avec services

---

## Project Modules

Le projet est composé de plusieurs modules :

* Gestion des utilisateurs
* Gestion des logements
* Gestion des réservations
* Gestion des favoris
* Gestion des réclamations
* Gestion des visites
* Forum de discussion
* Dashboard administrateur

---

## Tech Stack

### Backend

* PHP
* Symfony 6.4
* Doctrine ORM
* MySQL
* Composer
* API REST

### Frontend

* Twig
* HTML
* CSS
* Bootstrap
* JavaScript
* AJAX

### Environnement de développement

* XAMPP
* Git & GitHub

---

## Architecture

Le projet suit l’architecture **MVC (Model – View – Controller)** proposée par Symfony.

Structure principale du projet :

* **Controllers** : gestion de la logique applicative et traitement des requêtes
* **Entities** : représentation des tables de la base de données
* **Repositories** : gestion des requêtes vers la base de données
* **Services** : implémentation de la logique métier avancée
* **Forms** : gestion et validation des formulaires Symfony
* **Templates (Twig)** : gestion de l’interface utilisateur

Cette architecture permet une meilleure organisation du code et facilite la maintenance et l’évolution du projet.

---

## Contributors
Team **NovaMind** – Class **3A57**

* Jazi Oumaima
* Aissa Laarousse Yessmine
* Ayadi Ghaya
* Khemiri Nourchene
* Laabidi Mayas

---

## Academic Context

Projet développé à **Esprit School of Engineering – Tunisia**
PIDEV – 3A | Année académique 2025–2026

Ce projet s’inscrit dans le cadre du module PIDEV visant à mettre en pratique les compétences acquises en développement web, conception logicielle et intégration de technologies modernes.

---

## Getting Started

### 1. Cloner le repository

git clone https://github.com/

### 2. Accéder au projet

cd piDev_Stayzy

### 3. Installer les dépendances

composer install

### 4. Configurer la base de données

Modifier le fichier `.env` pour configurer la connexion à la base de données MySQL.

Exemple :

DATABASE_URL="mysql://root:@127.0.0.1:3306/pidev_db"

### 5. Créer la base de données

php bin/console doctrine:database:create

### 6. Exécuter les migrations

php bin/console doctrine:migrations:migrate

### 7. Lancer le serveur Symfony

symfony server:start

Puis ouvrir le projet dans le navigateur :

http://localhost:8000

---

## Acknowledgments

Ce projet a été réalisé dans le cadre du module **PIDEV** à **Esprit School of Engineering**.

Nous remercions les enseignants et encadrants pour leur accompagnement tout au long du projet.
