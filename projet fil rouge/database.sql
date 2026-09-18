-- =====================================================================
-- Base de donnees : reservation_hotels
-- Fichier unique : schema + donnees de demonstration
-- Import : phpMyAdmin > Import > selectionner database.sql
--          (ou : mysql -u root -p < database.sql)
-- =====================================================================
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

-- =====================================================================
-- Base de Données : reservation_hotels
-- SGBD            : MySQL 8.0+ / MariaDB 10.5+
-- Description     : Données de test réalistes (Fixtures / Seed data)
-- =====================================================================

USE reservation_hotels;

-- 1. Hôtels
INSERT INTO hotel (id_hotel, nom_hotel, adresse, ville, pays, telephone, email, categorie_etoiles, date_ouverture) VALUES
(1, 'Atlas Sky Luxury Hotel', 'Avenue Mohammed VI, Hivernage', 'Marrakech', 'Maroc', '+212524430000', 'contact@atlasskyluxury.com', 5, '2018-04-15'),
(2, 'Marina Bay View Resort', 'Boulevard de la Corniche, Malabata', 'Tanger', 'Maroc', '+212539320000', 'info@marinabayview.ma', 4, '2020-06-01'),
(3, 'Oasis Palms Business Hotel', 'Angle Boulevard Zerktouni & Roudani', 'Casablanca', 'Maroc', '+212522200000', 'reception@oasispalms.com', 4, '2019-11-10');

-- 2. Types d'hébergement
INSERT INTO type_hebergement (id_type, libelle, description, capacite_max, superficie_m2, prix_base_nuit) VALUES
(1, 'Chambre Simple Confort', 'Chambre équipée d un lit simple grand format, idéale pour voyageur solo.', 1, 22.00, 600.00),
(2, 'Chambre Double Standard', 'Chambre chaleureuse avec lit Queen-size ou deux lits jumeaux.', 2, 30.00, 950.00),
(3, 'Suite Junior', 'Espace salon séparé, literie King-size et balcon panoramique.', 3, 48.00, 1600.00),
(4, 'Suite Royale Panoramique', 'Suite de prestige avec terrasse privée, jacuzzi et vue imprenable.', 4, 85.00, 3200.00),
(5, 'Chambre Familiale', 'Deux espaces de couchage communicants adaptés aux familles.', 4, 52.00, 1400.00);

-- 3. Équipements
INSERT INTO equipement (id_equipement, nom_equipement, description) VALUES
(1, 'Wi-Fi Haut Débit Gratuit', 'Fibre optique disponible dans toute la chambre'),
(2, 'Climatisation / Chauffage', 'Contrôle individuel de la température'),
(3, 'Smart TV 55 pouces', 'Télévision connectée avec chaînes satellite et Netflix'),
(4, 'Mini-bar réfrigéré', 'Sélection de boissons fraîches et snacks'),
(5, 'Coffre-fort électronique', 'Adapté aux ordinateurs portables jusqu à 15 pouces'),
(6, 'Machine à café & Bouilloire', 'Cafetière Nespresso avec capsules offertes'),
(7, 'Jacuzzi privatif', 'Baignoire balnéothérapie avec hydrojets'),
(8, 'Balcon / Terrasse', 'Espace extérieur aménagé avec table et fauteuils');

-- 4. Employés
INSERT INTO employe (id_employe, id_hotel, nom, prenom, poste, telephone, email, date_embauche) VALUES
(1, 1, 'Benjelloun', 'Mehdi', 'Directeur Général', '+212661000001', 'm.benjelloun@atlassky.com', '2018-01-01'),
(2, 1, 'El Idrissi', 'Sara', 'Chef de Réception', '+212661000002', 's.elidrissi@atlassky.com', '2018-03-15'),
(3, 1, 'Chraibi', 'Youssef', 'Réceptionniste', '+212661000003', 'y.chraibi@atlassky.com', '2021-09-01'),
(4, 2, 'Mansouri', 'Amine', 'Directeur Résident', '+212662000001', 'a.mansouri@marinabay.ma', '2020-05-01'),
(5, 2, 'Kabbaj', 'Fatima', 'Réceptionniste', '+212662000002', 'f.kabbaj@marinabay.ma', '2021-02-15'),
(6, 3, 'Tazi', 'Karim', 'Manager Opérations', '+212663000001', 'k.tazi@oasispalms.com', '2019-10-01');

-- 5. Clients
INSERT INTO client (id_client, nom, prenom, email, mot_de_passe, telephone, adresse, ville, pays, cin_passeport, date_naissance, statut_fidelite) VALUES
(1, 'Alami', 'Omar', 'omar.alami@email.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ExampleHash1', '+212670112233', '15 Rue Ibn Batouta', 'Rabat', 'Maroc', 'AB123456', '1988-06-14', 'Gold'),
(2, 'Dupont', 'Sophie', 'sophie.dupont@paris.fr', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ExampleHash2', '+33612345678', '42 Rue de Rivoli', 'Paris', 'France', 'FR98765432', '1992-09-23', 'Silver'),
(3, 'Berrada', 'Hamza', 'hamza.berrada@gmail.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ExampleHash3', '+212661998877', '89 Boulevard Anfa', 'Casablanca', 'Maroc', 'BK765432', '1985-03-02', 'Platinum'),
(4, 'Smith', 'John', 'john.smith@techcorp.uk', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ExampleHash4', '+447911123456', '10 Downing Gate', 'Londres', 'Royaume-Uni', 'UK11223344', '1979-11-18', 'Standard');

-- 6. Promotions
INSERT INTO promotion (id_promotion, code_promo, description, pourcentage_reduction, date_debut, date_fin, actif) VALUES
(1, 'BIENVENUE10', 'Offre de bienvenue pour toute première réservation', 10.00, '2026-01-01', '2026-12-31', TRUE),
(2, 'SUMMER20', 'Promotion estivale valable pour les séjours de plus de 3 nuits', 20.00, '2026-06-01', '2026-08-31', TRUE),
(3, 'VIPGOLD15', 'Réduction permanente réservée aux membres Gold & Platinum', 15.00, '2026-01-01', '2026-12-31', TRUE);

-- 7. Services additionnels
INSERT INTO service (id_service, nom_service, description, prix_unitaire) VALUES
(1, 'Petit-déjeuner Buffet Gourmand', 'Buffet complet chaud et froid, spécialités locales et internationales', 150.00),
(2, 'Navette Aéroport Aller-Retour', 'Service de transfert privé avec chauffeur', 350.00),
(3, 'Accès Spa & Hammam Traditionnel', 'Séance détente au hammam avec gommage et massage aux huiles', 450.00),
(4, 'Place de Parking Sous-sol', 'Emplacement sécurisé 24h/24 avec borne de recharge électrique', 80.00),
(5, 'Dîner Gastronomique à la Carte', 'Menu 3 plats au restaurant signature de l hôtel', 380.00);

-- 8. Chambres
INSERT INTO chambre (id_chambre, id_hotel, id_type, numero_chambre, etage, prix_nuit, vue, fumeur, statut_chambre) VALUES
-- Atlas Sky (Hôtel 1 - Marrakech)
(1, 1, 2, '101', 1, 950.00, 'Jardin', FALSE, 'Disponible'),
(2, 1, 2, '102', 1, 1050.00, 'Piscine', FALSE, 'Occupée'),
(3, 1, 3, '201', 2, 1600.00, 'Piscine', FALSE, 'Disponible'),
(4, 1, 4, '301', 3, 3400.00, 'Jardin', FALSE, 'Disponible'),
-- Marina Bay View (Hôtel 2 - Tanger)
(5, 2, 1, '101', 1, 650.00, 'Ville', FALSE, 'Disponible'),
(6, 2, 2, '102', 1, 1100.00, 'Mer', FALSE, 'Occupée'),
(7, 2, 3, '201', 2, 1800.00, 'Mer', FALSE, 'Disponible'),
-- Oasis Palms (Hôtel 3 - Casablanca)
(8, 3, 1, '101', 1, 600.00, 'Ville', FALSE, 'Disponible'),
(9, 3, 2, '102', 1, 980.00, 'Ville', FALSE, 'Disponible'),
(10, 3, 5, '201', 2, 1450.00, 'Jardin', FALSE, 'Disponible');

-- 9. Équipements par chambre
INSERT INTO chambre_equipement (id_chambre, id_equipement) VALUES
-- Chambre 101 (Double Jardin Marrakech)
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 8),
-- Chambre 102 (Double Piscine Marrakech)
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 8),
-- Chambre 201 (Suite Junior Marrakech)
(3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 8),
-- Chambre 301 (Suite Royale Marrakech)
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5), (4, 6), (4, 7), (4, 8),
-- Chambre 102 (Double Mer Tanger)
(6, 1), (6, 2), (6, 3), (6, 4), (6, 8);

-- 10. Réservations
INSERT INTO reservation (id_reservation, id_client, id_chambre, id_employe, id_promotion, date_reservation, date_arrivee, date_depart, nb_adultes, nb_enfants, statut_reservation, source_reservation, montant_total) VALUES
-- Réservation 1 : Omar Alami à Marrakech (Suite Junior)
(1, 1, 3, 3, 3, '2026-09-01 14:30:00', '2026-10-10', '2026-10-14', 2, 1, 'Confirmée', 'Site web', 5440.00),
-- Réservation 2 : Sophie Dupont à Tanger (Chambre vue Mer)
(2, 2, 6, NULL, 1, '2026-09-05 10:15:00', '2026-09-15', '2026-09-18', 2, 0, 'Enregistrée', 'Site web', 2970.00),
-- Réservation 3 : Hamza Berrada à Marrakech (Chambre Double)
(3, 3, 2, 2, NULL, '2026-08-20 18:00:00', '2026-09-16', '2026-09-20', 2, 0, 'Enregistrée', 'Téléphone', 4200.00),
-- Réservation 4 : John Smith - Annulée
(4, 4, 1, NULL, NULL, '2026-08-10 09:00:00', '2026-09-01', '2026-09-03', 1, 0, 'Annulée', 'Agence', 1900.00);

-- 11. Services consommés par les réservations
INSERT INTO reservation_service (id_reservation, id_service, quantite, date_utilisation, prix_unitaire_applique) VALUES
(1, 1, 8, '2026-10-11', 150.00), -- 8 petits déjeuners
(1, 3, 2, '2026-10-12', 450.00), -- 2 séances Spa
(2, 2, 1, '2026-09-15', 350.00), -- 1 navette aéroport
(3, 1, 4, '2026-09-17', 150.00); -- 4 petits déjeuners

-- 12. Paiements
INSERT INTO paiement (id_paiement, id_reservation, montant, date_paiement, mode_paiement, statut_paiement, reference_transaction) VALUES
(1, 1, 5440.00, '2026-09-01 14:35:00', 'Carte bancaire', 'Validé', 'TXN-20260901-778899'),
(2, 2, 2970.00, '2026-09-05 10:20:00', 'PayPal', 'Validé', 'PAYPAL-98451247'),
(3, 3, 2000.00, '2026-08-20 18:05:00', 'Carte bancaire', 'Validé', 'TXN-20260820-112233'),
(4, 3, 2200.00, '2026-09-16 12:00:00', 'Espèces', 'Validé', 'CASH-REC-00124');

-- 13. Factures
INSERT INTO facture (id_facture, id_reservation, numero_facture, date_emission, montant_ht, taux_tva, montant_ttc, statut_facture) VALUES
(1, 1, 'FACT-2026-0001', '2026-09-01', 4533.33, 20.00, 5440.00, 'Payée'),
(2, 2, 'FACT-2026-0002', '2026-09-05', 2475.00, 20.00, 2970.00, 'Payée'),
(3, 3, 'FACT-2026-0003', '2026-09-16', 3500.00, 20.00, 4200.00, 'Payée');

-- 14. Annulations
INSERT INTO annulation (id_annulation, id_reservation, date_annulation, motif, montant_rembourse) VALUES
(1, 4, '2026-08-25 11:20:00', 'Changement imprévu d itinéraire professionnel', 1900.00);

-- 15. Avis clients
INSERT INTO avis (id_avis, id_client, id_reservation, note, commentaire, date_avis) VALUES
(1, 2, 2, 5, 'Séjour fantastique face à la mer ! Personnel accueillant et chambre très propre.', '2026-09-19 14:00:00');

