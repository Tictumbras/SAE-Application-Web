# TrackInsi — Gestion des incidents réseau

> Application web de gestion d'incidents réseau développée dans le cadre de la SAE23 à l'IUT de Béziers, Département R&T.

---

## Présentation

**TrackInsi** est une application web interne destinée aux entreprises de services numériques (ESN). Elle permet de centraliser, suivre et résoudre les incidents réseau remontés par les clients, avec une gestion par rôles (Administrateur, Technicien, Client).

---

## Fonctionnalités

### Administrateur
- Tableau de bord avec statistiques en temps réel
- Création et gestion des comptes utilisateurs
- Assignation des incidents aux techniciens
- Tri des incidents par priorité et statut
- Visualisation des détails de résolution
- Archivage des incidents résolus
- Gestion des demandes de réinitialisation de mot de passe
- Suivi des utilisateurs en ligne en temps réel

### Technicien
- Liste des interventions assignées
- Mise à jour du statut des incidents (En cours / Résolu)
- Description de résolution transmise à l'admin et au client
- Tri par priorité et par statut
- Historique des actions

### Client
- Création de nouveaux incidents avec niveau de priorité
- Suivi en temps réel de l'état de ses incidents
- Visualisation de la description de résolution du technicien
- Historique complet des actions sur chaque incident

### Général
- Authentification sécurisée (mots de passe hashés SHA-256)
- Photo de profil personnalisable
- Thème clair / sombre
- Demande de réinitialisation de mot de passe
- Suppression logique (soft delete) — les données ne sont jamais effacées

---

## Technologies utilisées

| Technologie | Usage |
|---|---|
| PHP | Logique serveur |
| MySQL | Base de données |
| HTML / CSS | Structure et style |
| JavaScript | Interactions dynamiques |

> Aucun framework PHP n'est utilisé.

---

## Installation

### Prérequis

- Serveur web avec **PHP 7.4+**
- **MySQL / MariaDB**
- **phpMyAdmin** ou accès MySQL direct
- Client FTP (**FileZilla** recommandé)

---

### 1. Cloner le dépôt

```bash
git clone https://github.com/Tictumbras/SAE-Application-Web.git
```

Ou téléchargez le ZIP via **Code → Download ZIP**.

---

### 2. Déposer les fichiers sur le serveur

Via FileZilla, déposez le dossier `TrackInsi` dans le répertoire web de votre serveur (`public_html/`, `www/` ou `htdocs/` selon votre hébergeur).

---

### 3. Créer la base de données

Dans phpMyAdmin, créez une base de données puis exécutez le script SQL suivant dans l'onglet **SQL** :

```sql
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    login VARCHAR(50) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technicien', 'client') NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    photo MEDIUMTEXT DEFAULT NULL,
    derniere_activite DATETIME DEFAULT NULL
);

CREATE TABLE incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    priorite ENUM('faible', 'moyen', 'critique') NOT NULL,
    statut ENUM('ouvert', 'en_cours', 'resolu', 'archive') DEFAULT 'ouvert',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_client INT NOT NULL,
    id_technicien INT DEFAULT NULL,
    archive TINYINT(1) DEFAULT 0,
    commentaire_resolution TEXT DEFAULT NULL,
    FOREIGN KEY (id_client) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_technicien) REFERENCES utilisateurs(id)
);

CREATE TABLE historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_incident INT NOT NULL,
    id_utilisateur INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    commentaire TEXT DEFAULT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_incident) REFERENCES incidents(id),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
);

CREATE TABLE demandes_mdp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'acceptee', 'refusee') DEFAULT 'en_attente'
);

-- Compte administrateur par défaut
-- Login : admin | Mot de passe : admin123
INSERT INTO utilisateurs (nom, prenom, login, mot_de_passe, role)
VALUES ('Admin', 'TrackInsi', 'admin', SHA2('admin123', 256), 'admin');
```

---

### 4. Configurer la connexion BDD

Modifiez le fichier `includes/db.php` :

```php
$host   = 'localhost';           // Adresse du serveur MySQL
$dbname = 'nom_de_votre_bdd';   // Nom de votre base de données
$user   = 'votre_login';        // Identifiant MySQL
$pass   = 'votre_mot_de_passe'; // Mot de passe MySQL
```

---

### 5. Configurer les chemins de redirection

Dans `includes/auth.php` et `logout.php`, adaptez le chemin de redirection à votre URL :

```php
header('Location: /TrackInsi/index.php'); // Remplacez par votre chemin réel
```

---

### 6. Première connexion

Accédez à votre application :

```
http://votre-domaine.com/TrackInsi/
```

Connectez-vous avec :

- **Login** : `admin`
- **Mot de passe** : `admin123`

> ⚠️ Changez ce mot de passe dès la première connexion.

---

## Structure des fichiers

```
TrackInsi/
├── index.php                   # Page de connexion
├── logout.php                  # Déconnexion
├── profil.php                  # Page de profil
├── mot_de_passe_oublie.php     # Réinitialisation MDP
│
├── admin/
│   ├── dashboard.php           # Tableau de bord
│   ├── incidents.php           # Gestion incidents
│   ├── utilisateurs.php        # Gestion comptes
│   ├── demandes_mdp.php        # Demandes MDP
│   ├── detail_incident.php     # Détail incident résolu
│   └── archiver.php            # Archivage
│
├── technicien/
│   ├── dashboard.php           # Mes interventions
│   └── incident_detail.php     # Gérer un incident
│
├── client/
│   ├── dashboard.php           # Mes incidents
│   ├── nouvel_incident.php     # Créer un incident
│   └── incident_detail.php     # Détail incident
│
├── includes/
│   ├── db.php                  # Connexion BDD ← À MODIFIER
│   ├── auth.php                # Sessions ← À MODIFIER
│   └── ping.php                # Maintien session
│
└── assets/
    ├── css/style.css           # Styles
    └── js/main.js              # Scripts
```

---

## Rôles et accès

| Rôle | Création du compte | Accès |
|---|---|---|
| **Administrateur** | Créé via le script SQL | Tableau de bord complet |
| **Technicien** | Créé par l'administrateur | Ses interventions uniquement |
| **Client** | Créé par l'administrateur | Ses incidents uniquement |

> Les utilisateurs doivent être connectés au même réseau que le serveur, ou le serveur doit être accessible depuis Internet.

---

## Réalisé par

Projet SAE23 — IUT de Béziers, Département Réseaux & Télécommunications


Créé par [Tom Dumont](https://github.com/Tictumbras)
