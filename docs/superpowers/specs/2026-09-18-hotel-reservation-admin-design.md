# Design — Back-office de réservation hôtelière (PHP + MySQL)

**Date :** 2026-09-18
**Projet :** Fil rouge — Réservation d'Hôtels (Sprint 1)
**Source :** `MCD_Reservation_Hotels.puml` + `dictionnaire_donnees_reservation_hotels.xlsx`

## 1. Objectif

Construire une application web de **back-office admin** pour gérer le système de réservation
hôtelière défini par le MCD et le dictionnaire de données (15 tables). Interface **en français**.

Mode de réalisation : PHP 8 procédural + MySQL 8 (PDO), même style que le prototype
« Cooking App » : chaque page gère son POST puis son rendu, joies par `JOIN` pour afficher
des libellés lisibles plutôt que des identifiants bruts.

## 2. Portée (v1 — reste serré)

- Back-office admin uniquement (pas de boutique client).
- Authentification admin minimale (session, 1 compte).
- CRUD / gestion de l'ensemble des tables du dictionnaire.
- Dashboard avec indicateurs clés.
- Ne pas faire : front-client, inscription public, panier multi-chambres.

## 3. Base de données

- Fichiers : `database.sql` (schéma) et `seed.sql` (données de démonstration),
  hérités et restaurés depuis la version précédente (commit `de32588`), conformes au dictionnaire :
  - 15 tables InnoDB, `utf8mb4`, cf. dictionnaire.
  - Contraintes documentées : `UNIQUE(id_hotel, numero_chambre)`, email / cin_passeport uniques,
    code_promo unique, une facture / une annulation / un avis par réservation, `date_depart > date_arrivee`,
    note 1–5, étoiles 1–5.
  - Index de recherche (`reservation(date_arrivee, date_depart)`, `statut`, etc.).
- Base : `reservation_hotels`. Connexion : `config.php` (PDO, `root`, port 3306 — identique au prototype Cooking App).

## 4. Architecture applicative

- `config.php` — connexion PDO (`ATTR_ERRMODE = EXCEPTION`).
- `auth.php` — démarrage session + garde (`require_admin()`), redirection vers `login.php`.
- `includes/header.php`, `includes/sidebar.php`, `includes/footer.php` — gabarit commun.
- `assets/admin.css`, `assets/app.js` — styles et petit script (filtre tableau / horloge).
- Pages (nom `kebab_case.php`) :
  - `login.php`, `logout.php`
  - `index.php` — dashboard (KPIs vidéos : hôtels, réservations par statut, CA payé,
    taux d'occupation, dernières réservations).
  - `hotels.php`, `types_hebergement.php`, `equipements.php`, `employes.php`,
    `promotions.php`, `services.php`, `clients.php`
  - `chambres.php` — liste + CRUD + répartition des équipements via cases à cocher.
  - `reservations.php` — liste filtrable (statut, source) + détail.
  - `reservation_create.php` / `reservation_edit.php` — formulaire réservation avec
    sélecteurs client / chambre / promotion / employé alimentés par requêtes `JOIN`.
  - `reservation_detail.php` — dossier complet : services consommés, paiements,
    facture, annulation, avis ; actions de changement de statut et d'annulation.
  - `paiements.php` — liste + ajout d'un paiement lié à une réservation.
  - `factures.php` — liste + génération de facture pour une réservation (TVA 20 %).
  - `avis.php` — liste des avis par client / chambre.

## 5. Requêtes SQL (hypothèse « README »)

Les listes affichent les libellés via `JOIN`, ex. :

```sql
SELECT r.id_reservation, c.prenom, c.nom, h.nom_hotel, ch.numero_chambre,
       th.libelle, r.date_arrivee, r.date_depart, r.statut_reservation, r.montant_total
FROM reservation r
JOIN client c        ON r.id_client   = c.id_client
JOIN chambre ch      ON r.id_chambre  = ch.id_chambre
JOIN hotel h         ON ch.id_hotel   = h.id_hotel
JOIN type_hebergement th ON ch.id_type = th.id_type
ORDER BY r.date_arrivee DESC;
```

## 6. Design (OpenDesign)

- Système de design `hotel-admin` dans `./opendesign/design-systems/hotel-admin/`
  (tokens dans `colors_and_type.css`, compilés dans `assets/admin.css`).
- Identité : PMS hôtelier sobre et chaleureux — palette neutre sable/crème,
  encre profonde, accent terracotta unique ; titres en sérif (Fraunces), UI en sans
  (Manrope). Toujours lisible, pas de rose/violet par défaut.
- Aperçu statique `./opendesign/mockups/apercu_admin.html` reproduisant le dashboard
  pour un rendu sans PHP.

## 7. Vérification

- `php -l` sur chaque fichier.
- Contrôle manuel des requêtes SQL et diffusion des instructions d'import (phpMyAdmin)
  + variables d'environnement XAMPP.
- Le serveur MySQL local n'étant pas démarré, la validation runtime est à la charge
  de l'utilisateur (procédure fournie).

## 8. Hors périmètre (v2 possibles)

Front public, authentification client, paiement en ligne, multi-utilisateurs par rôles,
API/export CSV, module statistique avancé.