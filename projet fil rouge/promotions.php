<?php

declare(strict_types=1);

$page_title = "Promotions";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_promotion = (int) ($_POST["id_promotion"] ?? 0);
        $code_promo   = trim($_POST["code_promo"] ?? "");
        $description  = trim($_POST["description"] ?? "");
        $pourcentage  = ($_POST["pourcentage_reduction"] ?? "") !== "" ? (float) $_POST["pourcentage_reduction"] : null;
        $date_debut   = $_POST["date_debut"] ?? "";
        $date_fin     = $_POST["date_fin"] ?? "";
        $actif        = isset($_POST["actif"]) ? 1 : 0;

        if ($code_promo === "" || $pourcentage === null || $date_debut === "" || $date_fin === "") {
            $error = "Code promo, pourcentage, date de début et date de fin sont obligatoires.";
        } elseif ($pourcentage < 0 || $pourcentage > 100) {
            $error = "Le pourcentage de réduction doit être compris entre 0 et 100.";
        } elseif ($date_fin < $date_debut) {
            $error = "La date de fin doit être postérieure à la date de début.";
        } else {
            try {
                if ($action === "create") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO promotion (code_promo, description, pourcentage_reduction, date_debut, date_fin, actif)
                         VALUES (:code, :description, :pourcentage, :date_debut, :date_fin, :actif)"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE promotion
                         SET code_promo = :code, description = :description, pourcentage_reduction = :pourcentage,
                             date_debut = :date_debut, date_fin = :date_fin, actif = :actif
                         WHERE id_promotion = :id"
                    );
                    $stmt->bindValue(":id", $id_promotion, PDO::PARAM_INT);
                }
                $stmt->execute([
                    ":code"        => $code_promo,
                    ":description" => $description !== "" ? $description : null,
                    ":pourcentage" => $pourcentage,
                    ":date_debut"  => $date_debut,
                    ":date_fin"    => $date_fin,
                    ":actif"       => $actif,
                ]);

                flash_set($action === "create" ? "Promotion ajoutée." : "Promotion mise à jour.");
                header("Location: promotions.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_promotion = (int) ($_POST["id_promotion"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM promotion WHERE id_promotion = :id")
                ->execute([":id" => $id_promotion]);
            flash_set("Promotion supprimée.");
            header("Location: promotions.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : cette promotion est utilisée dans une ou plusieurs réservations.", "error");
            header("Location: promotions.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM promotion WHERE id_promotion = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
}

$promotions = $pdo->query(
    "SELECT * FROM promotion ORDER BY date_debut DESC, code_promo"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Promotions</h1>
        <p>Codes promo et réductions proposés aux clients.</p>
    </div>
    <a href="promotions.php#form" class="btn primary">+ Ajouter une promotion</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier la promotion" : "Nouvelle promotion" ?></h3>
                <p><?= $edit ? "Mettez à jour les détails de la promotion." : "Enregistrez une nouvelle promotion." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="promotions.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_promotion" value="<?= (int) $edit["id_promotion"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="code_promo">Code promo <span class="req">*</span></label>
                    <input type="text" id="code_promo" name="code_promo" maxlength="30" required value="<?= htmlspecialchars($edit["code_promo"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="pourcentage_reduction">Réduction (%) <span class="req">*</span></label>
                    <input type="number" id="pourcentage_reduction" name="pourcentage_reduction" step="0.01" min="0" max="100" required value="<?= htmlspecialchars($edit["pourcentage_reduction"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="date_debut">Date de début <span class="req">*</span></label>
                    <input type="date" id="date_debut" name="date_debut" required value="<?= htmlspecialchars($edit["date_debut"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="date_fin">Date de fin <span class="req">*</span></label>
                    <input type="date" id="date_fin" name="date_fin" required value="<?= htmlspecialchars($edit["date_fin"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" maxlength="200" value="<?= htmlspecialchars($edit["description"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="actif">Actif</label>
                    <input type="checkbox" id="actif" name="actif" value="1" <?= ($edit === null || (int) ($edit["actif"] ?? 0) === 1) ? "checked" : "" ?> style="width:auto">
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter la promotion" ?></button>
                    <a href="promotions.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des promotions</h3>
                <p><?= count($promotions) ?> promotion<?= count($promotions) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code promo</th>
                            <th>Description</th>
                            <th>Réduction</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promotions as $p): ?>
                            <tr>
                                <td class="mono"><?= htmlspecialchars($p["code_promo"]) ?></td>
                                <td class="cell-sub"><?= htmlspecialchars($p["description"] ?? "—") ?></td>
                                <td class="num"><?= number_format((float) $p["pourcentage_reduction"], 2, ",", " ") ?> %</td>
                                <td class="cell-sub"><?= htmlspecialchars($p["date_debut"]) ?> → <?= htmlspecialchars($p["date_fin"]) ?></td>
                                <td>
                                    <?php if ((int) $p["actif"] === 1): ?>
                                        <span class="badge ok">Actif</span>
                                    <?php else: ?>
                                        <span class="badge neutral">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="promotions.php?edit=<?= (int) $p["id_promotion"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer la promotion « <?= htmlspecialchars($p["code_promo"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_promotion" value="<?= (int) $p["id_promotion"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($promotions)): ?>
                            <tr class="empty"><td colspan="6">Aucune promotion enregistrée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>