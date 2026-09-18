<?php

declare(strict_types=1);

$page_title = "Factures";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Badge helper ----
function statut_badge(string $s): string {
    $map = [
        "Émise"    => "accent",
        "Payée"    => "ok",
        "Impayée"  => "warn",
        "Annulée"  => "bad",
    ];
    return '<span class="badge ' . ($map[$s] ?? "neutral") . '">' . htmlspecialchars($s) . '</span>';
}

// ---- Traitement POST ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create") {
        $id_reservation = (int) ($_POST["id_reservation"] ?? 0);
        $taux_tva       = (float) ($_POST["taux_tva"] ?? 20);

        if ($id_reservation <= 0) {
            $error = "Veuillez sélectionner une réservation.";
        } elseif ($taux_tva < 0 || $taux_tva > 100) {
            $error = "Le taux de TVA doit être compris entre 0 et 100.";
        } else {
            try {
                // Récupérer le montant_total de la réservation
                $stmt = $pdo->prepare("SELECT montant_total FROM reservation WHERE id_reservation = :id");
                $stmt->execute([":id" => $id_reservation]);
                $res = $stmt->fetch();
                if (!$res) {
                    $error = "Réservation introuvable.";
                } else {
                    $montant_total = (float) $res["montant_total"];
                    $montant_ttc   = $montant_total;
                    $montant_ht    = round($montant_total / (1 + $taux_tva / 100), 2);

                    // Générer numéro facture : FACT-AAAA-NNNN, séquence annuelle sans collision
                    $year = date("Y");
                    $max_stmt = $pdo->prepare(
                        "SELECT MAX(CAST(RIGHT(numero_facture, 4) AS UNSIGNED))
                         FROM facture
                         WHERE numero_facture LIKE :prefix"
                    );
                    $max_stmt->execute([":prefix" => "FACT-" . $year . "-%"]);
                    $next = ((int) $max_stmt->fetchColumn()) + 1;
                    $numero = "FACT-" . $year . "-" . str_pad((string) $next, 4, "0", STR_PAD_LEFT);

                    $ins = $pdo->prepare(
                        "INSERT INTO facture (id_reservation, numero_facture, date_emission, montant_ht, taux_tva, montant_ttc, statut_facture)
                         VALUES (:res, :numero, CURDATE(), :ht, :tva, :ttc, 'Émise')"
                    );
                    $ins->execute([
                        ":res"   => $id_reservation,
                        ":numero" => $numero,
                        ":ht"    => $montant_ht,
                        ":tva"   => $taux_tva,
                        ":ttc"   => $montant_ttc,
                    ]);
                    flash_set("Facture " . $numero . " générée.");
                    header("Location: factures.php");
                    exit;
                }
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), "Duplicate") || str_contains($e->getMessage(), "unique")) {
                    $error = "Une facture existe déjà pour cette réservation.";
                } else {
                    $error = "Erreur base de données : " . $e->getMessage();
                }
            }
        }
    }

    if ($action === "regler") {
        $id_facture = (int) ($_POST["id_facture"] ?? 0);
        $pdo->prepare("UPDATE facture SET statut_facture = 'Payée' WHERE id_facture = :id")
            ->execute([":id" => $id_facture]);
        flash_set("Facture marquée comme payée.");
        header("Location: factures.php");
        exit;
    }

    if ($action === "delete") {
        $id_facture = (int) ($_POST["id_facture"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM facture WHERE id_facture = :id")
                ->execute([":id" => $id_facture]);
            flash_set("Facture supprimée.");
            header("Location: factures.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer la facture.", "error");
            header("Location: factures.php");
            exit;
        }
    }
}

// ---- Données ----
$factures = $pdo->query(
    "SELECT f.*, c.prenom, c.nom, h.nom_hotel, ch.numero_chambre
     FROM facture f
     JOIN reservation r ON f.id_reservation = r.id_reservation
     JOIN client c ON r.id_client = c.id_client
     JOIN chambre ch ON r.id_chambre = ch.id_chambre
     JOIN hotel h ON ch.id_hotel = h.id_hotel
     ORDER BY f.date_emission DESC"
)->fetchAll();

$reservations_sans_facture = $pdo->query(
    "SELECT r.id_reservation, c.prenom, c.nom, h.nom_hotel, r.montant_total
     FROM reservation r
     JOIN client c ON r.id_client = c.id_client
     JOIN chambre ch ON r.id_chambre = ch.id_chambre
     JOIN hotel h ON ch.id_hotel = h.id_hotel
     LEFT JOIN facture f ON f.id_reservation = r.id_reservation
     WHERE f.id_facture IS NULL AND r.statut_reservation <> 'Annulée'
     ORDER BY r.id_reservation DESC"
)->fetchAll();

$preselect_facture = isset($_GET["add"]) ? (int) $_GET["add"] : 0;
?>
<div class="page-head">
    <div>
        <h1>Factures</h1>
        <p>Génération et suivi des factures clients.</p>
    </div>
    <a href="factures.php#form" class="btn primary">+ Générer une facture</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3>Générer une facture</h3>
                <p>Créez une facture pour une réservation sans facture.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="id_reservation">Réservation <span class="req">*</span></label>
                    <select id="id_reservation" name="id_reservation" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($reservations_sans_facture as $rsf): ?>
                            <option value="<?= (int) $rsf["id_reservation"] ?>" <?= (int) $rsf["id_reservation"] === $preselect_facture ? "selected" : "" ?>>
                                Réservation #<?= (int) $rsf["id_reservation"] ?> — <?= htmlspecialchars($rsf["prenom"] . " " . $rsf["nom"]) ?> (<?= htmlspecialchars($rsf["nom_hotel"]) ?>) — <?= number_format((float) $rsf["montant_total"], 2, ",", " ") ?> MAD
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taux_tva">Taux de TVA (%) <span class="req">*</span></label>
                    <input type="number" id="taux_tva" name="taux_tva" step="0.01" min="0" max="100" value="20" required>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary">Générer la facture</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des factures</h3>
                <p><?= count($factures) ?> facture<?= count($factures) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Client</th>
                            <th>Établissement</th>
                            <th>Date émission</th>
                            <th>Montant HT</th>
                            <th>TVA</th>
                            <th>Montant TTC</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($factures as $f): ?>
                            <tr>
                                <td class="mono cell-main"><?= htmlspecialchars($f["numero_facture"]) ?></td>
                                <td><?= htmlspecialchars($f["prenom"] . " " . $f["nom"]) ?></td>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($f["nom_hotel"]) ?></span>
                                    <span class="cell-sub">ch. <?= htmlspecialchars($f["numero_chambre"]) ?></span>
                                </td>
                                <td class="mono"><?= htmlspecialchars($f["date_emission"]) ?></td>
                                <td class="num"><?= number_format((float) $f["montant_ht"], 2, ",", " ") ?> MAD</td>
                                <td class="num"><?= number_format((float) $f["taux_tva"], 2, ",", " ") ?> %</td>
                                <td class="num" style="font-weight:600"><?= number_format((float) $f["montant_ttc"], 2, ",", " ") ?> MAD</td>
                                <td><?= statut_badge($f["statut_facture"]) ?></td>
                                <td class="table-actions">
                                    <?php if ($f["statut_facture"] !== "Payée"): ?>
                                        <form method="POST" style="display:inline" data-confirm="Marquer la facture <?= htmlspecialchars($f["numero_facture"]) ?> comme payée ?">
                                            <input type="hidden" name="action" value="regler">
                                            <input type="hidden" name="id_facture" value="<?= (int) $f["id_facture"] ?>">
                                            <button type="submit" class="row-action" title="Marquer payée">✓</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($f["statut_facture"] === "Émise"): ?>
                                        <form method="POST" style="display:inline" data-confirm="Supprimer la facture <?= htmlspecialchars($f["numero_facture"]) ?> ?">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_facture" value="<?= (int) $f["id_facture"] ?>">
                                            <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($factures)): ?>
                            <tr class="empty"><td colspan="9">Aucune facture enregistrée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>
