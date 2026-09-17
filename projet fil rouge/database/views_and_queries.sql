-- =====================================================================
-- Vues & Requêtes Métiers Utiles
-- Base de Données : reservation_hotels
-- =====================================================================

USE reservation_hotels;

-- 1. Vue : Détails complets des réservations avec client, hôtel, type et chambre
CREATE OR REPLACE VIEW v_details_reservations AS
SELECT 
    r.id_reservation,
    r.date_reservation,
    r.date_arrivee,
    r.date_depart,
    DATEDIFF(r.date_depart, r.date_arrivee) AS duree_sejour_nuits,
    r.statut_reservation,
    r.montant_total,
    c.id_client,
    CONCAT(c.prenom, ' ', c.nom) AS nom_complet_client,
    c.email AS email_client,
    c.telephone AS tel_client,
    c.statut_fidelite,
    h.id_hotel,
    h.nom_hotel,
    h.ville AS ville_hotel,
    ch.numero_chambre,
    th.libelle AS type_chambre,
    p.code_promo,
    p.pourcentage_reduction
FROM reservation r
JOIN client c ON r.id_client = c.id_client
JOIN chambre ch ON r.id_chambre = ch.id_chambre
JOIN hotel h ON ch.id_hotel = h.id_hotel
JOIN type_hebergement th ON ch.id_type = th.id_type
LEFT JOIN promotion p ON r.id_promotion = p.id_promotion;

-- 2. Vue : Taux d'occupation et disponibilité actuelle des chambres
CREATE OR REPLACE VIEW v_statut_chambres_actuel AS
SELECT 
    h.nom_hotel,
    ch.numero_chambre,
    th.libelle AS type_hebergement,
    ch.prix_nuit,
    ch.vue,
    ch.statut_chambre
FROM chambre ch
JOIN hotel h ON ch.id_hotel = h.id_hotel
JOIN type_hebergement th ON ch.id_type = th.id_type
ORDER BY h.nom_hotel, ch.numero_chambre;

-- 3. Vue : Chiffre d'affaires et factures par hôtel
CREATE OR REPLACE VIEW v_revenus_par_hotel AS
SELECT 
    h.id_hotel,
    h.nom_hotel,
    COUNT(DISTINCT r.id_reservation) AS nb_reservations,
    COALESCE(SUM(p.montant), 0.00) AS total_encaisse,
    COALESCE(SUM(f.montant_ttc), 0.00) AS total_facture_ttc
FROM hotel h
LEFT JOIN chambre ch ON h.id_hotel = ch.id_hotel
LEFT JOIN reservation r ON ch.id_chambre = r.id_chambre AND r.statut_reservation != 'Annulée'
LEFT JOIN paiement p ON r.id_reservation = p.id_reservation AND p.statut_paiement = 'Validé'
LEFT JOIN facture f ON r.id_reservation = f.id_reservation
GROUP BY h.id_hotel, h.nom_hotel;
