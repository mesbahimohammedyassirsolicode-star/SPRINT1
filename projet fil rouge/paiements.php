<?php

declare(strict_types=1);

$page_title = "Paiements";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Badge helpers ----
function statut_badge(string $s): string {
    $map = [
        "En attente" => "warn",
        "Validé"     => "ok",
        "Refusé"     => "bad",
        "Remboursé"  => "info",
    ];
    return '<span class="badge ' . ($map[$s] ?? "neutral") . '">' . htmlspecialchars($s) . '</span>';
}

function mode_badge(string $m): string {
    return '<span class="badge neutral">' . htmlspecialchars($m) . '</span>';
}

// ---- Traitement POST ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create") {
        $id_reservation   = (int) ($_POST["id_reservation"] ?? 0);
        $montant          = (float) ($_POST["montant"] ?? 0);
        $mode_paiement    = $_POST["mode_paiement"] ?? "";
        $statut_paiement  = $_POST["statut_paiement"] ?? "En attente";
        $ref              = trim($_POST["reference_transaction"] ?? "");

        $valid_modes  = ["Carte bancaire","Espèces","Virement","Chèque","PayPal"];
        $valid_statuts = ["En attente","Validé","Refusé","Remboursé"];

        if ($id_reservation <= 0) {
            $error = "Veuillez sélectionner une réservation.";
        } elseif ($montant <= 0) {
            $error = "Le montant doit être supérieur à 0.";
        } elseif (!in_array($mode_paiement, $valid_modes, true)) {
            $error = "Mode de paiement invalide.";
        } elseif (!in_array($statut_paiement, $valid_statuts, true)) {
            $error = "Statut de paiement invalide.";
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO paiement (id_reservation, montant, mode_paiement, statut_paiement, reference_transaction)
                     VALUES (:res, :montant, :mode, :statut, :ref)"
                );
                $stmt->execute([
                    ":res"    => $id_reservation,
                    ":montant" => $montant,
                    ":mode"   => $mode_paiement,
                    ":statut" => $statut_paiement,
                    ":ref"    => $ref !== "" ? $ref : null,
                ]);
                flash_set("Paiement enregistré.");
                header("Location: paiements.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_paiement = (int) ($_POST["id_paiement"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM paiement WHERE id_paiement = :id")
                ->execute([":id" => $id_paiement]);
            flash_set("Paiement supprimé.");
            header("Location: paiements.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer le paiement.", "error");
            header("Location: paiements.php");
            exit;
        }
    }
}

// ---- Données ----
$paiements = $pdo->query(
    "SELECT p.*, c.prenom, c.nom, h.nom_hotel, ch.numero_chambre, r.date_arrivee
     FROM paiement p
     JOIN reservation r ON p.id_reservation = r.id_reservation
     JOIN client c ON r.id_client = c.id_client
     JOIN chambre ch ON r.id_chambre = ch.id_chambre
     JOIN hotel h ON ch.id_hotel = h.id_hotel
     ORDER BY p.date_paiement DESC"
)->fetchAll();

$reservations_list = $pdo->query(
    "SELECT r.id_reservation, c.prenom, c.nom, h.nom_hotel, ch.numero_chambre, r.date_arrivee
     FROM reservation r
     JOIN client c ON r.id_client = c.id_client
     JOIN chambre ch ON r.id_chambre = ch.id_chambre
     JOIN hotel h ON ch.id_hotel = h.id_hotel
     ORDER BY r.id_reservation DESC"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Paiements</h1>
        <p>Gestion des paiements liés aux réservations.</p>
    </div>
    <a href="paiements.php#form" class="btn primary">+ Nouveau paiement</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3>Enregistrer un paiement</h3>
                <p>Ajoutez un paiement pour une réservation existante.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="id_reservation">Réservation <span class="req">*</span></label>
                    <select id="id_reservation" name="id_reservation" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($reservations_list as $rl): ?>
                            <option value="<?= (int) $rl["id_reservation"] ?>">
                                Réservation #<?= (int) $rl["id_reservation"] ?> — <?= htmlspecialchars($rl["prenom"] . " " . $rl["nom"]) ?> (<?= htmlspecialchars($rl["nom_hotel"]) ?>, ch. <?= htmlspecialchars($rl["numero_chambre"]) ?>, arrivée <?= htmlspecialchars($rl["date_arrivee"]) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="montant">Montant (MAD) <span class="req">*</span></label>
                    <input type="number" id="montant" name="montant" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="mode_paiement">Mode de paiement <span class="req">*</span></label>
                    <select id="mode_paiement" name="mode_paiement" required>
                        <?php foreach (["Carte bancaire","Espèces","Virement","Chèque","PayPal"] as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="statut_paiement">Statut <span class="req">*</span></label>
                    <select id="statut_paiement" name="statut_paiement" required>
                        <?php foreach (["En attente","Validé","Refusé","Remboursé"] as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reference_transaction">Référence transaction</label>
                    <input type="text" id="reference_transaction" name="reference_transaction">
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary">Enregistrer le paiement</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des paiements</h3>
                <p><?= count($paiements) ?> paiement<?= count($paiements) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th>Statut</th>
                            <th>Référence</th>
                            <th>Réservation</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paiements as $p): ?>
                            <tr>
                                <td class="mono">#<?= (int) $p["id_paiement"] ?></td>
                                <td class="num"><?= number_format((float) $p["montant"], 2, ",", " ") ?> MAD</td>
                                <td class="mono"><?= htmlspecialchars($p["date_paiement"]) ?></td>
                                <td><?= mode_badge($p["mode_paiement"]) ?></td>
                                <td><?= statut_badge($p["statut_paiement"]) ?></td>
                                <td class="cell-sub mono"><?= htmlspecialchars($p["reference_transaction"] ?? "—") ?></td>
                                <td>
                                    <span class="cell-main">Cl. <?= htmlspecialchars($p["prenom"] . " " . $p["nom"]) ?></span>
                                    <span class="cell-sub"><?= htmlspecialchars($p["nom_hotel"]) ?>, ch. <?= htmlspecialchars($p["numero_chambre"]) ?></span>
                                </td>
                                <td class="table-actions">
                                    <a href="reservation_detail.php?id=<?= (int) $p["id_reservation"] ?>" title="Dossier réservation" class="row-action">›</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer ce paiement de <?= number_format((float) $p["montant"], 2, ",", " ") ?> MAD ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_paiement" value="<?= (int) $p["id_paiement"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($paiements)): ?>
                            <tr class="empty"><td colspan="8">Aucun paiement enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>
