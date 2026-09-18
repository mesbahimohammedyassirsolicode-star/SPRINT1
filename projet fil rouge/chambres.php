<?php

declare(strict_types=1);

$page_title = "Chambres";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_chambre      = (int) ($_POST["id_chambre"] ?? 0);
        $id_hotel        = (int) ($_POST["id_hotel"] ?? 0);
        $id_type         = (int) ($_POST["id_type"] ?? 0);
        $numero_chambre  = trim($_POST["numero_chambre"] ?? "");
        $etage           = $_POST["etage"] !== "" ? (int) $_POST["etage"] : null;
        $prix_nuit       = $_POST["prix_nuit"] !== "" ? (float) $_POST["prix_nuit"] : null;
        $vue             = $_POST["vue"] ?? "Aucune";
        $fumeur          = isset($_POST["fumeur"]) ? 1 : 0;
        $statut_chambre  = $_POST["statut_chambre"] ?? "Disponible";
        $equipements     = $_POST["equipements"] ?? [];

        if ($numero_chambre === "" || $id_hotel === 0 || $id_type === 0 || $prix_nuit === null) {
            $error = "Numéro, hôtel, type et prix par nuit sont obligatoires.";
        } else {
            try {
                $pdo->beginTransaction();

                if ($action === "create") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO chambre (id_hotel, id_type, numero_chambre, etage, prix_nuit, vue, fumeur, statut_chambre)
                         VALUES (:hotel, :type, :numero, :etage, :prix, :vue, :fumeur, :statut)"
                    );
                    $stmt->execute([
                        ":hotel"  => $id_hotel,
                        ":type"   => $id_type,
                        ":numero" => $numero_chambre,
                        ":etage"  => $etage,
                        ":prix"   => $prix_nuit,
                        ":vue"    => $vue,
                        ":fumeur" => $fumeur,
                        ":statut" => $statut_chambre,
                    ]);
                    $new_id = (int) $pdo->lastInsertId();
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE chambre
                         SET id_hotel = :hotel, id_type = :type, numero_chambre = :numero, etage = :etage,
                             prix_nuit = :prix, vue = :vue, fumeur = :fumeur, statut_chambre = :statut
                         WHERE id_chambre = :id"
                    );
                    $stmt->execute([
                        ":hotel"  => $id_hotel,
                        ":type"   => $id_type,
                        ":numero" => $numero_chambre,
                        ":etage"  => $etage,
                        ":prix"   => $prix_nuit,
                        ":vue"    => $vue,
                        ":fumeur" => $fumeur,
                        ":statut" => $statut_chambre,
                        ":id"     => $id_chambre,
                    ]);
                    $new_id = $id_chambre;
                }

                // Gestion des équipements
                $pdo->prepare("DELETE FROM chambre_equipement WHERE id_chambre = :id")
                    ->execute([":id" => $new_id]);
                if (!empty($equipements)) {
                    $ins = $pdo->prepare(
                        "INSERT INTO chambre_equipement (id_chambre, id_equipement) VALUES (:ch, :eq)"
                    );
                    foreach ($equipements as $eq_id) {
                        $ins->execute([":ch" => $new_id, ":eq" => (int) $eq_id]);
                    }
                }
                $pdo->commit();

                flash_set($action === "create" ? "Chambre ajoutée." : "Chambre mise à jour.");
                header("Location: chambres.php");
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if (str_contains($e->getMessage(), "uq_chambre_hotel_numero")) {
                    $error = "Une chambre avec ce numéro existe déjà dans cet hôtel.";
                } else {
                    $error = "Erreur base de données : " . $e->getMessage();
                }
            }
        }
    }

    if ($action === "delete") {
        $id_chambre = (int) ($_POST["id_chambre"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM chambre WHERE id_chambre = :id")
                ->execute([":id" => $id_chambre]);
            flash_set("Chambre supprimée.");
            header("Location: chambres.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : la chambre possède des réservations.", "error");
            header("Location: chambres.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
$edit_equipements = [];
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM chambre WHERE id_chambre = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
    if ($edit) {
        $eq_stmt = $pdo->prepare("SELECT id_equipement FROM chambre_equipement WHERE id_chambre = :id");
        $eq_stmt->execute([":id" => (int) $edit["id_chambre"]]);
        $edit_equipements = array_column($eq_stmt->fetchAll(), "id_equipement");
        $edit_equipements = array_map("intval", $edit_equipements);
    }
}

$hotels_list = $pdo->query("SELECT id_hotel, nom_hotel, ville FROM hotel ORDER BY nom_hotel")->fetchAll();
$types_list  = $pdo->query("SELECT id_type, libelle FROM type_hebergement ORDER BY libelle")->fetchAll();
$equipements_list = $pdo->query("SELECT id_equipement, nom_equipement FROM equipement ORDER BY nom_equipement")->fetchAll();

$chambres = $pdo->query(
    "SELECT ch.*, h.nom_hotel, h.ville, t.libelle AS type_libelle,
            (SELECT GROUP_CONCAT(e.nom_equipement ORDER BY e.nom_equipement SEPARATOR ', ')
             FROM chambre_equipement ce
             JOIN equipement e ON e.id_equipement = ce.id_equipement
             WHERE ce.id_chambre = ch.id_chambre) AS equipements_text
     FROM chambre ch
     JOIN hotel h ON h.id_hotel = ch.id_hotel
     JOIN type_hebergement t ON t.id_type = ch.id_type
     ORDER BY h.nom_hotel, ch.numero_chambre"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Chambres</h1>
        <p>Gestion des chambres et équipements.</p>
    </div>
    <a href="chambres.php#form" class="btn primary">+ Ajouter une chambre</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier la chambre" : "Nouvelle chambre" ?></h3>
                <p><?= $edit ? "Mettez à jour les informations de la chambre." : "Enregistrez une nouvelle chambre." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="chambres.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_chambre" value="<?= (int) $edit["id_chambre"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="numero_chambre">Numéro <span class="req">*</span></label>
                    <input type="text" id="numero_chambre" name="numero_chambre" required value="<?= htmlspecialchars($edit["numero_chambre"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="id_hotel">Hôtel <span class="req">*</span></label>
                    <select id="id_hotel" name="id_hotel" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($hotels_list as $hl): ?>
                            <option value="<?= (int) $hl["id_hotel"] ?>" <?= (int) ($edit["id_hotel"] ?? 0) === (int) $hl["id_hotel"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($hl["nom_hotel"] . " — " . $hl["ville"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_type">Type <span class="req">*</span></label>
                    <select id="id_type" name="id_type" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($types_list as $tp): ?>
                            <option value="<?= (int) $tp["id_type"] ?>" <?= (int) ($edit["id_type"] ?? 0) === (int) $tp["id_type"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($tp["libelle"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="etage">Étage</label>
                    <input type="number" id="etage" name="etage" value="<?= htmlspecialchars((string) ($edit["etage"] ?? "")) ?>">
                </div>
                <div class="form-group">
                    <label for="prix_nuit">Prix / nuit <span class="req">*</span></label>
                    <input type="number" id="prix_nuit" name="prix_nuit" step="0.01" required value="<?= htmlspecialchars($edit["prix_nuit"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="vue">Vue</label>
                    <select id="vue" name="vue">
                        <?php foreach (["Aucune", "Mer", "Jardin", "Ville", "Piscine"] as $v): ?>
                            <option value="<?= $v ?>" <?= ($edit["vue"] ?? "Aucune") === $v ? "selected" : "" ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fumeur">Fumeur</label>
                    <input type="checkbox" id="fumeur" name="fumeur" value="1" <?= !empty($edit["fumeur"]) ? "checked" : "" ?>>
                </div>
                <div class="form-group">
                    <label for="statut_chambre">Statut</label>
                    <select id="statut_chambre" name="statut_chambre">
                        <?php foreach (["Disponible", "Occupée", "Maintenance", "Nettoyage"] as $s): ?>
                            <option value="<?= $s ?>" <?= ($edit["statut_chambre"] ?? "Disponible") === $s ? "selected" : "" ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!empty($equipements_list)): ?>
                <div class="form-group" style="grid-column: 1 / -1">
                    <label>Équipements</label>
                    <div class="check-list">
                        <?php foreach ($equipements_list as $eq): ?>
                            <label class="check-item">
                                <input type="checkbox" name="equipements[]" value="<?= (int) $eq["id_equipement"] ?>" <?= in_array((int) $eq["id_equipement"], $edit_equipements, true) ? "checked" : "" ?>>
                                <?= htmlspecialchars($eq["nom_equipement"]) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter la chambre" ?></button>
                    <a href="chambres.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des chambres</h3>
                <p><?= count($chambres) ?> chambre<?= count($chambres) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Chambre</th>
                            <th>Hôtel</th>
                            <th>Type</th>
                            <th>Prix / nuit</th>
                            <th>Vue</th>
                            <th>Statut</th>
                            <th>Équipements</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chambres as $ch): ?>
                            <tr>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($ch["numero_chambre"]) ?></span>
                                    <span class="cell-sub">Étage <?= $ch["etage"] !== null ? (int) $ch["etage"] : "—" ?></span>
                                </td>
                                <td class="cell-main"><?= htmlspecialchars($ch["nom_hotel"]) ?></td>
                                <td><?= htmlspecialchars($ch["type_libelle"]) ?></td>
                                <td class="num"><?= number_format((float) $ch["prix_nuit"], 2, ",", " ") ?> MAD</td>
                                <td><span class="badge accent"><?= htmlspecialchars($ch["vue"]) ?></span></td>
                                <td>
                                    <?php
                                        $statut_cls = match($ch["statut_chambre"]) {
                                            "Disponible" => "ok",
                                            "Occupée"    => "info",
                                            "Maintenance"=> "warn",
                                            "Nettoyage"  => "neutral",
                                            default      => "neutral",
                                        };
                                    ?>
                                    <span class="badge <?= $statut_cls ?>"><?= htmlspecialchars($ch["statut_chambre"]) ?></span>
                                </td>
                                <td>
                                    <?php
                                        $eqs = $ch["equipements_text"] ? explode(", ", $ch["equipements_text"]) : [];
                                        foreach ($eqs as $eq_name):
                                    ?>
                                        <span class="badge neutral"><?= htmlspecialchars($eq_name) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="chambres.php?edit=<?= (int) $ch["id_chambre"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer la chambre « <?= htmlspecialchars($ch["numero_chambre"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_chambre" value="<?= (int) $ch["id_chambre"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($chambres)): ?>
                            <tr class="empty"><td colspan="8">Aucune chambre enregistrée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>
