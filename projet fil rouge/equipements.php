<?php

declare(strict_types=1);

$page_title = "Équipements";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_equipement  = (int) ($_POST["id_equipement"] ?? 0);
        $nom_equipement = trim($_POST["nom_equipement"] ?? "");
        $description    = trim($_POST["description"] ?? "");

        if ($nom_equipement === "") {
            $error = "Le nom de l'équipement est obligatoire.";
        } else {
            try {
                if ($action === "create") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO equipement (nom_equipement, description)
                         VALUES (:nom, :description)"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE equipement
                         SET nom_equipement = :nom, description = :description
                         WHERE id_equipement = :id"
                    );
                    $stmt->bindValue(":id", $id_equipement, PDO::PARAM_INT);
                }
                $stmt->execute([
                    ":nom"         => $nom_equipement,
                    ":description" => $description !== "" ? $description : null,
                ]);

                flash_set($action === "create" ? "Équipement ajouté." : "Équipement mis à jour.");
                header("Location: equipements.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_equipement = (int) ($_POST["id_equipement"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM equipement WHERE id_equipement = :id")
                ->execute([":id" => $id_equipement]);
            flash_set("Équipement supprimé.");
            header("Location: equipements.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : cet équipement est rattaché à une ou plusieurs chambres.", "error");
            header("Location: equipements.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM equipement WHERE id_equipement = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
}

$equipements = $pdo->query(
    "SELECT * FROM equipement ORDER BY nom_equipement"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Équipements</h1>
        <p>Équipements disponibles dans les chambres de l'hôtel.</p>
    </div>
    <a href="equipements.php#form" class="btn primary">+ Ajouter un équipement</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier l'équipement" : "Nouvel équipement" ?></h3>
                <p><?= $edit ? "Mettez à jour les informations de l'équipement." : "Enregistrez un nouvel équipement." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="equipements.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_equipement" value="<?= (int) $edit["id_equipement"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom_equipement">Nom de l'équipement <span class="req">*</span></label>
                    <input type="text" id="nom_equipement" name="nom_equipement" required value="<?= htmlspecialchars($edit["nom_equipement"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" maxlength="200" value="<?= htmlspecialchars($edit["description"] ?? "") ?>">
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter l'équipement" ?></button>
                    <a href="equipements.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des équipements</h3>
                <p><?= count($equipements) ?> équipement<?= count($equipements) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipements as $e): ?>
                            <tr>
                                <td class="cell-main"><?= htmlspecialchars($e["nom_equipement"]) ?></td>
                                <td class="cell-sub"><?= htmlspecialchars($e["description"] ?? "—") ?></td>
                                <td class="table-actions">
                                    <a href="equipements.php?edit=<?= (int) $e["id_equipement"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer l'équipement « <?= htmlspecialchars($e["nom_equipement"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_equipement" value="<?= (int) $e["id_equipement"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($equipements)): ?>
                            <tr class="empty"><td colspan="3">Aucun équipement enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>