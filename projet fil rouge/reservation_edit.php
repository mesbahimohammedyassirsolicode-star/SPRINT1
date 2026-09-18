<?php

declare(strict_types=1);

$page_title = "Modifier la réservation";

require_once __DIR__ . "/includes/header.php";

$id = (int) ($_GET["id"] ?? 0);

$row = null;
if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT r.*, c.prenom, c.nom, h.nom_hotel, ch.numero_chambre, ch.prix_nuit
         FROM reservation r
         JOIN client c  ON r.id_client  = c.id_client
         JOIN chambre ch ON r.id_chambre = ch.id_chambre
         JOIN hotel h   ON ch.id_hotel  = h.id_hotel
         WHERE r.id_reservation = :id"
    );
    $stmt->execute([":id" => $id]);
    $row = $stmt->fetch() ?: null;
}

if (!$row) {
    flash_set("Réservation introuvable.", "error");
    header("Location: reservations.php");
    exit;
}

$errors = [];

$chambres = $pdo->query(
    "SELECT ch.id_chambre, ch.numero_chambre, ch.prix_nuit, h.nom_hotel, t.libelle AS type_libelle
     FROM chambre ch
     JOIN hotel h ON h.id_hotel = ch.id_hotel
     JOIN type_hebergement t ON t.id_type = ch.id_type
     ORDER BY h.nom_hotel, ch.numero_chambre"
)->fetchAll();

$promotions = $pdo->query(
    "SELECT id_promotion, code_promo, pourcentage_reduction FROM promotion ORDER BY code_promo"
)->fetchAll();

$employes = $pdo->query(
    "SELECT e.id_employe, e.prenom, e.nom, e.poste FROM employe e ORDER BY e.prenom, e.nom"
)->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_chambre    = (int) ($_POST["id_chambre"] ?? 0);
    $id_promotion  = ($_POST["id_promotion"] ?? "") !== "" ? (int) $_POST["id_promotion"] : null;
    $id_employe    = ($_POST["id_employe"] ?? "") !== "" ? (int) $_POST["id_employe"] : null;
    $date_arrivee  = trim($_POST["date_arrivee"] ?? "");
    $date_depart   = trim($_POST["date_depart"] ?? "");
    $nb_adultes    = (int) ($_POST["nb_adultes"] ?? 0);
    $nb_enfants    = (int) ($_POST["nb_enfants"] ?? 0);
    $source        = $_POST["source_reservation"] ?? "Site web";
    $statut        = $_POST["statut_reservation"] ?? "En attente";
    $montant       = (float) str_replace(",", ".", $_POST["montant_total"] ?? "0");

    if ($id_chambre <= 0)                      $errors[] = "Sélectionnez une chambre.";
    if ($date_arrivee === "" || $date_depart === "") $errors[] = "Les dates sont obligatoires.";
    elseif ($date_depart <= $date_arrivee)           $errors[] = "La date de départ doit être postérieure à l'arrivée.";
    if ($nb_adultes <= 0)                      $errors[] = "Le nombre d'adultes doit être supérieur à 0.";
    if ($montant < 0)                          $errors[] = "Le montant total ne peut pas être négatif.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE reservation
                 SET id_chambre = :chambre, id_employe = :employe, id_promotion = :promo,
                     date_arrivee = :arrivee, date_depart = :depart, nb_adultes = :adultes,
                     nb_enfants = :enfants, statut_reservation = :statut,
                     source_reservation = :source, montant_total = :montant
                 WHERE id_reservation = :id"
            );
            $stmt->execute([
                ":chambre" => $id_chambre,
                ":employe" => $id_employe,
                ":promo"   => $id_promotion,
                ":arrivee" => $date_arrivee,
                ":depart"  => $date_depart,
                ":adultes" => $nb_adultes,
                ":enfants" => $nb_enfants,
                ":statut"  => $statut,
                ":source"  => $source,
                ":montant" => $montant,
                ":id"      => $id,
            ]);

            flash_set("Réservation #" . $id . " mise à jour.");
            header("Location: reservation_detail.php?id=" . $id);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur base de données : " . $e->getMessage();
        }
    }
}
?>
<div class="page-head">
    <div>
        <h1>Réservation #<?= $id ?></h1>
        <p>Client : <?= htmlspecialchars($row["prenom"] . " " . $row["nom"]) ?> &middot; <?= htmlspecialchars($row["nom_hotel"]) ?>, chambre <?= htmlspecialchars($row["numero_chambre"]) ?></p>
    </div>
    <a href="reservation_detail.php?id=<?= $id ?>" class="btn secondary">← Retour au dossier</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert error">
        <strong>Veuillez corriger :</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" class="res-grid">
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Modifier le dossier</h3>
                <p>Mettez à jour la chambre, les dates ou le montant.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_chambre">Chambre <span class="req">*</span></label>
                    <select id="id_chambre" name="id_chambre" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($chambres as $ch): ?>
                            <option value="<?= (int) $ch["id_chambre"] ?>" <?= (int) $row["id_chambre"] === (int) $ch["id_chambre"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($ch["nom_hotel"] . " · n°" . $ch["numero_chambre"] . " — " . $ch["type_libelle"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_promotion">Code promo</label>
                    <select id="id_promotion" name="id_promotion">
                        <option value="">Aucune</option>
                        <?php foreach ($promotions as $p): ?>
                            <option value="<?= (int) $p["id_promotion"] ?>" <?= (int) ($row["id_promotion"] ?? 0) === (int) $p["id_promotion"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($p["code_promo"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_employe">Traité par</label>
                    <select id="id_employe" name="id_employe">
                        <option value="">—</option>
                        <?php foreach ($employes as $em): ?>
                            <option value="<?= (int) $em["id_employe"] ?>" <?= (int) ($row["id_employe"] ?? 0) === (int) $em["id_employe"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($em["prenom"] . " " . $em["nom"] . " — " . $em["poste"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_arrivee">Arrivée <span class="req">*</span></label>
                    <input type="date" id="date_arrivee" name="date_arrivee" required value="<?= htmlspecialchars($row["date_arrivee"]) ?>">
                </div>
                <div class="form-group">
                    <label for="date_depart">Départ <span class="req">*</span></label>
                    <input type="date" id="date_depart" name="date_depart" required value="<?= htmlspecialchars($row["date_depart"]) ?>">
                </div>
                <div class="form-group">
                    <label for="nb_adultes">Adultes <span class="req">*</span></label>
                    <input type="number" id="nb_adultes" name="nb_adultes" min="1" required value="<?= (int) $row["nb_adultes"] ?>">
                </div>
                <div class="form-group">
                    <label for="nb_enfants">Enfants</label>
                    <input type="number" id="nb_enfants" name="nb_enfants" min="0" value="<?= (int) $row["nb_enfants"] ?>">
                </div>
                <div class="form-group">
                    <label for="source_reservation">Source</label>
                    <select id="source_reservation" name="source_reservation">
                        <?php foreach (["Site web", "Téléphone", "Agence", "Sur place"] as $src): ?>
                            <option value="<?= $src ?>" <?= $row["source_reservation"] === $src ? "selected" : "" ?>><?= $src ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="statut_reservation">Statut</label>
                    <select id="statut_reservation" name="statut_reservation">
                        <?php foreach (["En attente", "Confirmée", "Enregistrée", "Terminée", "Annulée"] as $st): ?>
                            <option value="<?= $st ?>" <?= $row["statut_reservation"] === $st ? "selected" : "" ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="montant_total">Montant total (MAD)</label>
                    <input type="number" id="montant_total" name="montant_total" step="0.01" min="0" required value="<?= number_format((float) $row["montant_total"], 2, ".", "") ?>">
                    <span class="hint">Modifiable manuellement.</span>
                </div>
            </div>
            <hr class="form-divider">
            <div class="form-actions" style="margin-top:0">
                <button type="submit" class="btn primary">Enregistrer</button>
                <a href="reservation_detail.php?id=<?= $id ?>" class="btn ghost">Annuler</a>
            </div>
        </div>
    </div>

    <aside class="card">
        <div class="card-head">
            <div>
                <h3>Rappel</h3>
            </div>
        </div>
        <div class="card-body" style="font-size:var(--text-sm);color:var(--ink-soft)">
            <p>Créée le <b><?= htmlspecialchars($row["date_reservation"]) ?></b> via « <?= htmlspecialchars($row["source_reservation"]) ?> ».
            Le paiement et la facture se traitent depuis le dossier (<a href="reservation_detail.php?id=<?= $id ?>">voir le dossier</a>).</p>
        </div>
    </aside>
</form>
<?php require __DIR__ . "/includes/footer.php"; ?>