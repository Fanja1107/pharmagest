-- =========================================================
-- PHARMAGEST - Structure de la base de données
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- UTILISATEURS
-- ---------------------------------------------------------
CREATE TABLE utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pharmacien', 'vendeur') NOT NULL DEFAULT 'vendeur',
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- CATEGORIES
-- ---------------------------------------------------------
CREATE TABLE categories (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- UNITES
-- ---------------------------------------------------------
CREATE TABLE unites (
    id_unite INT AUTO_INCREMENT PRIMARY KEY,
    nom_unite VARCHAR(50) NOT NULL,
    symbole VARCHAR(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- MEDICAMENTS
-- ---------------------------------------------------------
CREATE TABLE medicaments (
    id_medicament INT AUTO_INCREMENT PRIMARY KEY,
    id_categorie INT NOT NULL,
    reference VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(150) NOT NULL,
    dosage VARCHAR(50) NULL,
    forme VARCHAR(50) NULL,
    description TEXT NULL,
    seuil_alerte INT NOT NULL DEFAULT 10,
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_medicament_categorie
        FOREIGN KEY (id_categorie) REFERENCES categories(id_categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- CONDITIONNEMENTS
-- ---------------------------------------------------------
CREATE TABLE conditionnements (
    id_conditionnement INT AUTO_INCREMENT PRIMARY KEY,
    id_medicament INT NOT NULL,
    id_unite INT NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    quantite_base INT NOT NULL DEFAULT 1,
    prix_vente DECIMAL(12,2) NOT NULL,
    vendable TINYINT(1) NOT NULL DEFAULT 1,
    achetable TINYINT(1) NOT NULL DEFAULT 1,
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    CONSTRAINT fk_conditionnement_medicament
        FOREIGN KEY (id_medicament) REFERENCES medicaments(id_medicament),
    CONSTRAINT fk_conditionnement_unite
        FOREIGN KEY (id_unite) REFERENCES unites(id_unite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- FOURNISSEURS
-- ---------------------------------------------------------
CREATE TABLE fournisseurs (
    id_fournisseur INT AUTO_INCREMENT PRIMARY KEY,
    raison_sociale VARCHAR(150) NOT NULL,
    telephone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    adresse VARCHAR(255) NULL,
    nif VARCHAR(50) NULL,
    stat VARCHAR(50) NULL,
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- LOTS
-- ---------------------------------------------------------
CREATE TABLE lots (
    id_lot INT AUTO_INCREMENT PRIMARY KEY,
    id_medicament INT NOT NULL,
    numero_lot VARCHAR(50) NOT NULL,
    date_fabrication DATE NULL,
    date_expiration DATE NOT NULL,
    quantite_base INT NOT NULL DEFAULT 0,
    prix_achat_base DECIMAL(12,2) NOT NULL,
    statut ENUM('actif', 'epuise', 'expire') NOT NULL DEFAULT 'actif',
    CONSTRAINT fk_lot_medicament
        FOREIGN KEY (id_medicament) REFERENCES medicaments(id_medicament)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- APPROVISIONNEMENTS
-- ---------------------------------------------------------
CREATE TABLE approvisionnements (
    id_approvisionnement INT AUTO_INCREMENT PRIMARY KEY,
    id_fournisseur INT NOT NULL,
    id_utilisateur INT NOT NULL,
    numero_appro VARCHAR(50) NOT NULL UNIQUE,
    date_approvisionnement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_achat DECIMAL(14,2) NOT NULL DEFAULT 0,
    statut ENUM('brouillon', 'valide', 'annule') NOT NULL DEFAULT 'brouillon',
    CONSTRAINT fk_appro_fournisseur
        FOREIGN KEY (id_fournisseur) REFERENCES fournisseurs(id_fournisseur),
    CONSTRAINT fk_appro_utilisateur
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- APPROVISIONNEMENT_DETAILS
-- ---------------------------------------------------------
CREATE TABLE approvisionnement_details (
    id_appro_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_approvisionnement INT NOT NULL,
    id_medicament INT NOT NULL,
    id_conditionnement INT NOT NULL,
    id_lot INT NULL,
    quantite INT NOT NULL,
    prix_achat DECIMAL(12,2) NOT NULL,
    quantite_base INT NOT NULL,
    sous_total DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_apprydetail_appro
        FOREIGN KEY (id_approvisionnement) REFERENCES approvisionnements(id_approvisionnement),
    CONSTRAINT fk_apprydetail_medicament
        FOREIGN KEY (id_medicament) REFERENCES medicaments(id_medicament),
    CONSTRAINT fk_apprydetail_conditionnement
        FOREIGN KEY (id_conditionnement) REFERENCES conditionnements(id_conditionnement),
    CONSTRAINT fk_apprydetail_lot
        FOREIGN KEY (id_lot) REFERENCES lots(id_lot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- CLIENTS
-- ---------------------------------------------------------
CREATE TABLE clients (
    id_client INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NULL,
    telephone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    adresse VARCHAR(255) NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- VENTES
-- ---------------------------------------------------------
CREATE TABLE ventes (
    id_vente INT AUTO_INCREMENT PRIMARY KEY,
    id_client INT NULL,
    id_utilisateur INT NOT NULL,
    numero_vente VARCHAR(50) NOT NULL UNIQUE,
    date_vente DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    statut ENUM('en_cours', 'validee', 'annulee') NOT NULL DEFAULT 'en_cours',
    mode_paiement ENUM('especes', 'mobile_money', 'carte', 'virement') NOT NULL DEFAULT 'especes',
    CONSTRAINT fk_vente_client
        FOREIGN KEY (id_client) REFERENCES clients(id_client),
    CONSTRAINT fk_vente_utilisateur
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- VENTE_DETAILS
-- ---------------------------------------------------------
CREATE TABLE vente_details (
    id_vente_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_vente INT NOT NULL,
    id_medicament INT NOT NULL,
    id_conditionnement INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(12,2) NOT NULL,
    quantite_base INT NOT NULL,
    sous_total DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_ventedetail_vente
        FOREIGN KEY (id_vente) REFERENCES ventes(id_vente),
    CONSTRAINT fk_ventedetail_medicament
        FOREIGN KEY (id_medicament) REFERENCES medicaments(id_medicament),
    CONSTRAINT fk_ventedetail_conditionnement
        FOREIGN KEY (id_conditionnement) REFERENCES conditionnements(id_conditionnement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- VENTE_LOTS (traçabilité FEFO)
-- ---------------------------------------------------------
CREATE TABLE vente_lots (
    id_vente_lot INT AUTO_INCREMENT PRIMARY KEY,
    id_vente_detail INT NOT NULL,
    id_lot INT NOT NULL,
    quantite_base INT NOT NULL,
    CONSTRAINT fk_ventelot_ventedetail
        FOREIGN KEY (id_vente_detail) REFERENCES vente_details(id_vente_detail),
    CONSTRAINT fk_ventelot_lot
        FOREIGN KEY (id_lot) REFERENCES lots(id_lot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- VUE : stock calculé par médicament
-- ---------------------------------------------------------
CREATE VIEW vue_stock_medicaments AS
SELECT
    m.id_medicament,
    m.reference,
    m.nom,
    m.dosage,
    m.seuil_alerte,
    m.statut AS statut_medicament,
    COALESCE(SUM(CASE WHEN l.statut = 'actif' THEN l.quantite_base ELSE 0 END), 0) AS stock_total,
    CASE
        WHEN COALESCE(SUM(CASE WHEN l.statut = 'actif' THEN l.quantite_base ELSE 0 END), 0) = 0 THEN 'rupture'
        WHEN COALESCE(SUM(CASE WHEN l.statut = 'actif' THEN l.quantite_base ELSE 0 END), 0) <= m.seuil_alerte THEN 'faible'
        ELSE 'normal'
    END AS statut_stock
FROM medicaments m
LEFT JOIN lots l ON l.id_medicament = m.id_medicament
GROUP BY m.id_medicament, m.reference, m.nom, m.dosage, m.seuil_alerte, m.statut;

SET FOREIGN_KEY_CHECKS = 1;