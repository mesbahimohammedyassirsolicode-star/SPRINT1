-- =====================================================================
-- Base de Données : reservation_hotels
-- SGBD            : MySQL 8.0+ / MariaDB 10.5+
-- Description     : Script DDL de création de la structure relationnelle
--                   Conforme au MCD et au Dictionnaire de Données
-- =====================================================================

DROP DATABASE IF EXISTS reservation_hotels;
CREATE DATABASE reservation_hotels
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE reservation_hotels;

-- ---------------------------------------------------------------------
-- 1. Table : hotel
-- ---------------------------------------------------------------------
CREATE TABLE hotel (
    id_hotel INT AUTO_INCREMENT PRIMARY KEY,
    nom_hotel VARCHAR(100) NOT NULL,
    adresse VARCHAR(200) NOT NULL,
    ville VARCHAR(50) NOT NULL,
    pays VARCHAR(50) NOT NULL,
    telephone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    categorie_etoiles TINYINT NULL,
    date_ouverture DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_hotel_etoiles CHECK (categorie_etoiles IS NULL OR (categorie_etoiles BETWEEN 1 AND 5))
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Table : type_hebergement
-- ---------------------------------------------------------------------
CREATE TABLE type_hebergement (
    id_type INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL,
    description TEXT NULL,
    capacite_max TINYINT NOT NULL,
    superficie_m2 DECIMAL(5,2) NULL,
    prix_base_nuit DECIMAL(10,2) NOT NULL,
    CONSTRAINT chk_type_capacite CHECK (capacite_max > 0),
    CONSTRAINT chk_type_prix CHECK (prix_base_nuit >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. Table : equipement
-- ---------------------------------------------------------------------
CREATE TABLE equipement (
    id_equipement INT AUTO_INCREMENT PRIMARY KEY,
    nom_equipement VARCHAR(50) NOT NULL,
    description VARCHAR(200) NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Table : employe
-- ---------------------------------------------------------------------
CREATE TABLE employe (
    id_employe INT AUTO_INCREMENT PRIMARY KEY,
    id_hotel INT NOT NULL,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    poste VARCHAR(50) NOT NULL,
    telephone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    date_embauche DATE NULL,
    CONSTRAINT fk_employe_hotel FOREIGN KEY (id_hotel) 
        REFERENCES hotel(id_hotel) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. Table : client
-- ---------------------------------------------------------------------
CREATE TABLE client (
    id_client INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) NULL,
    adresse VARCHAR(200) NULL,
    ville VARCHAR(50) NULL,
    pays VARCHAR(50) NULL,
    cin_passeport VARCHAR(30) NULL,
    date_naissance DATE NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut_fidelite ENUM('Standard', 'Silver', 'Gold', 'Platinum') DEFAULT 'Standard',
    CONSTRAINT uq_client_email UNIQUE (email),
    CONSTRAINT uq_client_cin_passeport UNIQUE (cin_passeport)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. Table : promotion
-- ---------------------------------------------------------------------
CREATE TABLE promotion (
    id_promotion INT AUTO_INCREMENT PRIMARY KEY,
    code_promo VARCHAR(30) NOT NULL,
    description VARCHAR(200) NULL,
    pourcentage_reduction DECIMAL(5,2) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    CONSTRAINT uq_promotion_code UNIQUE (code_promo),
    CONSTRAINT chk_promotion_dates CHECK (date_fin >= date_debut),
    CONSTRAINT chk_promotion_pourcentage CHECK (pourcentage_reduction BETWEEN 0 AND 100)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. Table : service
-- ---------------------------------------------------------------------
CREATE TABLE service (
    id_service INT AUTO_INCREMENT PRIMARY KEY,
    nom_service VARCHAR(50) NOT NULL,
    description VARCHAR(200) NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    CONSTRAINT chk_service_prix CHECK (prix_unitaire >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. Table : chambre
-- ---------------------------------------------------------------------
CREATE TABLE chambre (
    id_chambre INT AUTO_INCREMENT PRIMARY KEY,
    id_hotel INT NOT NULL,
    id_type INT NOT NULL,
    numero_chambre VARCHAR(10) NOT NULL,
    etage TINYINT NULL,
    prix_nuit DECIMAL(10,2) NOT NULL,
    vue ENUM('Mer', 'Jardin', 'Ville', 'Piscine', 'Aucune') DEFAULT 'Aucune',
    fumeur BOOLEAN DEFAULT FALSE,
    statut_chambre ENUM('Disponible', 'Occupée', 'Maintenance', 'Nettoyage') DEFAULT 'Disponible',
    CONSTRAINT uq_chambre_hotel_numero UNIQUE (id_hotel, numero_chambre),
    CONSTRAINT chk_chambre_prix CHECK (prix_nuit >= 0),
    CONSTRAINT fk_chambre_hotel FOREIGN KEY (id_hotel) 
        REFERENCES hotel(id_hotel) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT fk_chambre_type FOREIGN KEY (id_type) 
        REFERENCES type_hebergement(id_type) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. Table de liaison : chambre_equipement (N:M Chambre <-> Equipement)
-- ---------------------------------------------------------------------
CREATE TABLE chambre_equipement (
    id_chambre INT NOT NULL,
    id_equipement INT NOT NULL,
    PRIMARY KEY (id_chambre, id_equipement),
    CONSTRAINT fk_ce_chambre FOREIGN KEY (id_chambre) 
        REFERENCES chambre(id_chambre) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_ce_equipement FOREIGN KEY (id_equipement) 
        REFERENCES equipement(id_equipement) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. Table : reservation
-- ---------------------------------------------------------------------
CREATE TABLE reservation (
    id_reservation INT AUTO_INCREMENT PRIMARY KEY,
    id_client INT NOT NULL,
    id_chambre INT NOT NULL,
    id_employe INT NULL,
    id_promotion INT NULL,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_arrivee DATE NOT NULL,
    date_depart DATE NOT NULL,
    nb_adultes TINYINT NOT NULL,
    nb_enfants TINYINT DEFAULT 0,
    statut_reservation ENUM('En attente', 'Confirmée', 'Enregistrée', 'Terminée', 'Annulée') DEFAULT 'En attente',
    source_reservation ENUM('Site web', 'Téléphone', 'Agence', 'Sur place') DEFAULT 'Site web',
    montant_total DECIMAL(10,2) NOT NULL,
    CONSTRAINT chk_reservation_dates CHECK (date_depart > date_arrivee),
    CONSTRAINT chk_reservation_adultes CHECK (nb_adultes > 0),
    CONSTRAINT chk_reservation_enfants CHECK (nb_enfants >= 0),
    CONSTRAINT chk_reservation_montant CHECK (montant_total >= 0),
    CONSTRAINT fk_reservation_client FOREIGN KEY (id_client) 
        REFERENCES client(id_client) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT fk_reservation_chambre FOREIGN KEY (id_chambre) 
        REFERENCES chambre(id_chambre) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT fk_reservation_employe FOREIGN KEY (id_employe) 
        REFERENCES employe(id_employe) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    CONSTRAINT fk_reservation_promotion FOREIGN KEY (id_promotion) 
        REFERENCES promotion(id_promotion) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. Table de liaison : reservation_service (N:M Reservation <-> Service)
-- ---------------------------------------------------------------------
CREATE TABLE reservation_service (
    id_reservation_service INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation INT NOT NULL,
    id_service INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    date_utilisation DATE NULL,
    prix_unitaire_applique DECIMAL(10,2) NOT NULL,
    CONSTRAINT chk_rs_quantite CHECK (quantite > 0),
    CONSTRAINT chk_rs_prix CHECK (prix_unitaire_applique >= 0),
    CONSTRAINT fk_rs_reservation FOREIGN KEY (id_reservation) 
        REFERENCES reservation(id_reservation) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_rs_service FOREIGN KEY (id_service) 
        REFERENCES service(id_service) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. Table : paiement
-- ---------------------------------------------------------------------
CREATE TABLE paiement (
    id_paiement INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    mode_paiement ENUM('Carte bancaire', 'Espèces', 'Virement', 'Chèque', 'PayPal') NOT NULL,
    statut_paiement ENUM('En attente', 'Validé', 'Refusé', 'Remboursé') DEFAULT 'En attente',
    reference_transaction VARCHAR(100) NULL,
    CONSTRAINT chk_paiement_montant CHECK (montant > 0),
    CONSTRAINT fk_paiement_reservation FOREIGN KEY (id_reservation) 
        REFERENCES reservation(id_reservation) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. Table : facture (1:1 avec Reservation)
-- ---------------------------------------------------------------------
CREATE TABLE facture (
    id_facture INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation INT NOT NULL,
    numero_facture VARCHAR(30) NOT NULL,
    date_emission DATE NOT NULL,
    montant_ht DECIMAL(10,2) NOT NULL,
    taux_tva DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    montant_ttc DECIMAL(10,2) NOT NULL,
    statut_facture ENUM('Émise', 'Payée', 'Impayée', 'Annulée') DEFAULT 'Émise',
    CONSTRAINT uq_facture_reservation UNIQUE (id_reservation),
    CONSTRAINT uq_facture_numero UNIQUE (numero_facture),
    CONSTRAINT chk_facture_montant_ht CHECK (montant_ht >= 0),
    CONSTRAINT chk_facture_montant_ttc CHECK (montant_ttc >= 0),
    CONSTRAINT fk_facture_reservation FOREIGN KEY (id_reservation) 
        REFERENCES reservation(id_reservation) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 14. Table : annulation (1:1 optionnel avec Reservation)
-- ---------------------------------------------------------------------
CREATE TABLE annulation (
    id_annulation INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation INT NOT NULL,
    date_annulation DATETIME DEFAULT CURRENT_TIMESTAMP,
    motif VARCHAR(255) NULL,
    montant_rembourse DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT uq_annulation_reservation UNIQUE (id_reservation),
    CONSTRAINT chk_annulation_remboursement CHECK (montant_rembourse >= 0),
    CONSTRAINT fk_annulation_reservation FOREIGN KEY (id_reservation) 
        REFERENCES reservation(id_reservation) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 15. Table : avis (1:1 optionnel par Reservation / Client)
-- ---------------------------------------------------------------------
CREATE TABLE avis (
    id_avis INT AUTO_INCREMENT PRIMARY KEY,
    id_client INT NOT NULL,
    id_reservation INT NOT NULL,
    note TINYINT NOT NULL,
    commentaire TEXT NULL,
    date_avis DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_avis_reservation UNIQUE (id_reservation),
    CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5),
    CONSTRAINT fk_avis_client FOREIGN KEY (id_client) 
        REFERENCES client(id_client) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT fk_avis_reservation FOREIGN KEY (id_reservation) 
        REFERENCES reservation(id_reservation) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INDEX DE RECHERCHE ET PERFORMANCE
-- ---------------------------------------------------------------------
CREATE INDEX idx_reservation_dates ON reservation(date_arrivee, date_depart);
CREATE INDEX idx_reservation_statut ON reservation(statut_reservation);
CREATE INDEX idx_chambre_statut ON chambre(statut_chambre);
CREATE INDEX idx_chambre_hotel_type ON chambre(id_hotel, id_type);
CREATE INDEX idx_paiement_reservation ON paiement(id_reservation);
CREATE INDEX idx_facture_statut ON facture(statut_facture);
