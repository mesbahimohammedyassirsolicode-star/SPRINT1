<?php

declare(strict_types=1);

$page_title = "Services";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_service    = (int) ($_POST["id_service"] ?? 0);
        $nom_service   = trim($_POST["nom_service"] ?? "");
        $description   = trim($_POST["description"] ?? "");
        $prix_unitaire = ($_POST["prix_unitaire"] ?? "") !== "" ? (float) $_POST["prix_unitaire"] : null;

        if ($nom_service === "" || $prix_unitaire === null) {
            $error = "Le nom du service et le prix unitaire sont obligatoires.";
        } elseif ($prix_unitaire < 0) {
            $error = "Le prix unitaire ne peut pas être négatif.";
        } else {
            try {
                if ($action === "create") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO service (nom_service, description, prix_unitaire)
                         VALUES (:nom, :description, :prix)"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE service
                         SET nom_service = :nom, description = :description, prix_unitaire = :prix
                         WHERE id_service = :id"
                    );
                    $stmt->bindValue(":id", $id_service, PDO::PARAM_INT);
                }
                $stmt->execute([
                    ":nom"         => $nom_service,
                    ":description" => $description !== "" ? $description : null,
                    ":prix"        => $prix_unitaire,
                ]);

                flash_set($action === "create" ? "Service ajouté." : "Service mis à jour.");
                header("Location: services.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_service = (int) ($_POST["id_service"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM service WHERE id_service = :id")
                ->execute([":id" => $id_service]);
            flash_set("Service supprimé.");
            header("Location: services.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : ce service est associé à une ou plusieurs réservations.", "error");
            header("Location: services.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM service WHERE id_service = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
}

$services = $pdo->query(
    "SELECT * FROM service ORDER BY nom_service"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Services</h1>
        <p>Services additionnels proposés aux clients de l'hôtel.</p>
    </div>
    <a href="services.php#form" class="btn primary">+ Ajouter un service</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier le service" : "Nouveau service" ?></h3>
                <p><?= $edit ? "Mettez à jour les informations du service." : "Enregistrez un nouveau service." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="services.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_service" value="<?= (int) $edit["id_service"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom_service">Nom du service <span class="req">*</span></label>
                    <input type="text" id="nom_service" name="nom_service" required value="<?= htmlspecialchars($edit["nom_service"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" maxlength="200" value="<?= htmlspecialchars($edit["description"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="prix_unitaire">Prix unitaire (MAD) <span class="req">*</span></label>
                    <input type="number" id="prix_unitaire" name="prix_unitaire" step="0.01" min="0" required value="<?= htmlspecialchars($edit["prix_unitaire"] ?? "") ?>">
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter le service" ?></button>
                    <a href="services.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des services</h3>
                <p><?= count($services) ?> service<?= count($services) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Prix unitaire</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td class="cell-main"><?= htmlspecialchars($s["nom_service"]) ?></td>
                                <td class="cell-sub"><?= htmlspecialchars($s["description"] ?? "—") ?></td>
                                <td class="num"><?= number_format((float) $s["prix_unitaire"], 2, ",", " ") ?> MAD</td>
                                <td class="table-actions">
                                    <a href="services.php?edit=<?= (int) $s["id_service"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer le service « <?= htmlspecialchars($s["nom_service"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_service" value="<?= (int) $s["id_service"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($services)): ?>
                            <tr class="empty"><td colspan="4">Aucun service enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>