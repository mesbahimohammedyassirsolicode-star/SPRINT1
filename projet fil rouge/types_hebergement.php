<?php

declare(strict_types=1);

$page_title = "Types d'hébergement";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_type     = (int) ($_POST["id_type"] ?? 0);
        $libelle     = trim($_POST["libelle"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $capacite    = ($_POST["capacite_max"] ?? "") !== "" ? (int) $_POST["capacite_max"] : null;
        $superficie  = ($_POST["superficie_m2"] ?? "") !== "" ? (float) $_POST["superficie_m2"] : null;
        $prix_base   = ($_POST["prix_base_nuit"] ?? "") !== "" ? (float) $_POST["prix_base_nuit"] : null;

        if ($libelle === "" || $capacite === null || $prix_base === null) {
            $error = "Libellé, capacité maximale et prix de base sont obligatoires.";
        } elseif ($capacite < 1) {
            $error = "La capacité maximale doit être au moins de 1 personne.";
        } elseif ($prix_base < 0) {
            $error = "Le prix de base ne peut pas être négatif.";
        } else {
            try {
                if ($action === "create") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO type_hebergement (libelle, description, capacite_max, superficie_m2, prix_base_nuit)
                         VALUES (:libelle, :description, :capacite, :superficie, :prix)"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE type_hebergement
                         SET libelle = :libelle, description = :description, capacite_max = :capacite,
                             superficie_m2 = :superficie, prix_base_nuit = :prix
                         WHERE id_type = :id"
                    );
                    $stmt->bindValue(":id", $id_type, PDO::PARAM_INT);
                }
                $stmt->execute([
                    ":libelle"     => $libelle,
                    ":description" => $description !== "" ? $description : null,
                    ":capacite"    => $capacite,
                    ":superficie"  => $superficie,
                    ":prix"        => $prix_base,
                ]);

                flash_set($action === "create" ? "Type d'hébergement ajouté." : "Type d'hébergement mis à jour.");
                header("Location: types_hebergement.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_type = (int) ($_POST["id_type"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM type_hebergement WHERE id_type = :id")
                ->execute([":id" => $id_type]);
            flash_set("Type d'hébergement supprimé.");
            header("Location: types_hebergement.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : ce type d'hébergement est utilisé par une ou plusieurs chambres.", "error");
            header("Location: types_hebergement.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM type_hebergement WHERE id_type = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
}

$types = $pdo->query(
    "SELECT * FROM type_hebergement ORDER BY libelle"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Types d'hébergement</h1>
        <p>Catégories de chambres et tarifs de base du parc hôtelier.</p>
    </div>
    <a href="types_hebergement.php#form" class="btn primary">+ Ajouter un type</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier le type d'hébergement" : "Nouveau type d'hébergement" ?></h3>
                <p><?= $edit ? "Mettez à jour les caractéristiques du type." : "Enregistrez un nouveau type d'hébergement." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="types_hebergement.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_type" value="<?= (int) $edit["id_type"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="libelle">Libellé <span class="req">*</span></label>
                    <input type="text" id="libelle" name="libelle" required value="<?= htmlspecialchars($edit["libelle"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="capacite_max">Capacité maximale (personnes) <span class="req">*</span></label>
                    <input type="number" id="capacite_max" name="capacite_max" min="1" required value="<?= htmlspecialchars($edit["capacite_max"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="prix_base_nuit">Prix de base / nuit (MAD) <span class="req">*</span></label>
                    <input type="number" id="prix_base_nuit" name="prix_base_nuit" step="0.01" min="0" required value="<?= htmlspecialchars($edit["prix_base_nuit"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="superficie_m2">Superficie (m²)</label>
                    <input type="number" id="superficie_m2" name="superficie_m2" step="0.01" min="0" value="<?= htmlspecialchars($edit["superficie_m2"] ?? "") ?>">
                </div>
                <div class="form-group" style="grid-column: 1 / -1">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($edit["description"] ?? "") ?></textarea>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter le type" ?></button>
                    <a href="types_hebergement.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des types d'hébergement</h3>
                <p><?= count($types) ?> type<?= count($types) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th>Description</th>
                            <th>Capacité max</th>
                            <th>Superficie</th>
                            <th>Prix de base / nuit</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $t): ?>
                            <tr>
                                <td class="cell-main"><?= htmlspecialchars($t["libelle"]) ?></td>
                                <td class="cell-sub"><?= htmlspecialchars($t["description"] ?? "—") ?></td>
                                <td class="num"><?= (int) $t["capacite_max"] ?></td>
                                <td class="num"><?= $t["superficie_m2"] !== null ? number_format((float) $t["superficie_m2"], 2, ",", " ") . " m²" : "—" ?></td>
                                <td class="num"><?= number_format((float) $t["prix_base_nuit"], 2, ",", " ") ?> MAD</td>
                                <td class="table-actions">
                                    <a href="types_hebergement.php?edit=<?= (int) $t["id_type"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer le type « <?= htmlspecialchars($t["libelle"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_type" value="<?= (int) $t["id_type"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($types)): ?>
                            <tr class="empty"><td colspan="6">Aucun type d'hébergement enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>