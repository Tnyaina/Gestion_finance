-- Structure de la base de données en français pour la gestion financière d'entreprise
CREATE DATABASE gestion_finance;
USE gestion_finance;

-- Table des départements
CREATE TABLE departements (
    id_departement INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- Table des utilisateurs
CREATE TABLE utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom_utilisateur VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    id_departement INT NULL,
    role ENUM('admin', 'utilisateur_departement') NOT NULL DEFAULT 'utilisateur_departement',
    FOREIGN KEY (id_departement) REFERENCES departements(id_departement)
);

-- Table des catégories 
CREATE TABLE categories (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type ENUM('gain', 'depense') NOT NULL
);

-- Table des budgets prévisionnels
CREATE TABLE budgets (
    id_budget INT AUTO_INCREMENT PRIMARY KEY,
    id_departement INT NOT NULL,
    mois INT NOT NULL, -- 1-12
    annee INT NOT NULL,
    solde_depart DECIMAL(15, 2) NOT NULL,
    solde_final DECIMAL(15, 2) NOT NULL,
    statut ENUM('en_attente', 'approuve', 'rejete') DEFAULT 'en_attente',
    FOREIGN KEY (id_departement) REFERENCES departements(id_departement),
    UNIQUE (id_departement, mois, annee)
);

-- Table des détails des budgets prévisionnels
CREATE TABLE details_budget (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_budget INT NOT NULL,
    id_categorie INT NOT NULL,
    montant DECIMAL(15, 2) NOT NULL,
    description TEXT,
    FOREIGN KEY (id_budget) REFERENCES budgets(id_budget) ON DELETE CASCADE,
    FOREIGN KEY (id_categorie) REFERENCES categories(id_categorie)
);

-- Table des transactions réalisées
CREATE TABLE transactions (
    id_transaction INT AUTO_INCREMENT PRIMARY KEY,
    id_departement INT NOT NULL,
    mois INT NOT NULL, -- 1-12
    annee INT NOT NULL,
    id_categorie INT NOT NULL,
    montant DECIMAL(15, 2) NOT NULL,
    description TEXT,
    FOREIGN KEY (id_departement) REFERENCES departements(id_departement),
    FOREIGN KEY (id_categorie) REFERENCES categories(id_categorie)
);

-- Table pour les exports
CREATE TABLE exports (
    id_export INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    type_fichier ENUM('pdf', 'excel', 'csv') NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
);

-- Table pour la situation globale de l'entreprise par mois/année
CREATE TABLE situation_globale (
    mois INT NOT NULL,
    annee INT NOT NULL,
    solde_depart_previsionnel DECIMAL(15, 2) NOT NULL,
    gains_previsionnels DECIMAL(15, 2) NOT NULL DEFAULT 0,
    depenses_previsionnelles DECIMAL(15, 2) NOT NULL DEFAULT 0,
    solde_final_previsionnel DECIMAL(15, 2) NOT NULL,
    solde_depart_realise DECIMAL(15, 2) NOT NULL,
    gains_realises DECIMAL(15, 2) NOT NULL DEFAULT 0,
    depenses_realisees DECIMAL(15, 2) NOT NULL DEFAULT 0,
    solde_final_realise DECIMAL(15, 2) NOT NULL,
    solde_depart_mois_suivant DECIMAL(15, 2) NOT NULL DEFAULT 0,
    UNIQUE (mois, annee)  
);

-- Vue pour les écarts entre prévisions et réalisations (mise à jour pour utiliser la table)
CREATE VIEW ecarts AS
SELECT
    sg.mois,
    sg.annee,
    sg.gains_realises - sg.gains_previsionnels AS ecart_gains,
    sg.depenses_realisees - sg.depenses_previsionnelles AS ecart_depenses,
    sg.solde_final_realise - sg.solde_final_previsionnel AS ecart_solde_final
FROM
    situation_globale sg;

-- Insertion de données initiales
INSERT INTO departements (nom, description) VALUES
('Comptabilite', 'Departement de gestion des finances'),
('Ressources Humaines', 'Departement de gestion du personnel');

INSERT INTO categories (nom, type) VALUES
('Ventes de produits', 'gain'),
('Prestations de services', 'gain'),
('Revenus publicitaires', 'gain'),
('Investissements reçus', 'gain');

-- Insérer des catégories de type 'depense'
INSERT INTO categories (nom, type) VALUES
('Salaires', 'depense'),
('Loyer', 'depense'),
('Équipements', 'depense'),
('Maintenance', 'depense');