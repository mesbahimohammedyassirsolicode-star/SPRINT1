<?php

declare(strict_types=1);

$page_title = "Hôtels";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_hotel      = (int) ($_POST["id_hotel"] ?? 0);
        $nom_hotel     = trim($_POST["nom_hotel"] ?? "");
        $adresse       = trim($_POST["adresse"] ?? "");
        $ville         = trim($_POST["ville"] ?? "");
        $pays          = trim($_POST["pays"] ?? "");
        $telephone     = trim($_POST["telephone"] ?? "");
        $email         = trim($_POST["email"] ?? "");
        $categorie     = $_POST["categorie_etoiles"] !== "" ? (int) $_POST["categorie_etoiles"] : null;
        $date_ouverture = $_POST["date_ouverture"] !== "" ? $_POST["date_ouverture"] : null;

        if ($nom_hotel === "" || $adresse === "" || $ville === "" || $pays === "") {
            $error = "Nom, adresse, ville et pays sont obligatoires.";
        } elseif ($categorie !== null && ($categorie < 1 || $categorie > 5)) {
            $error = "La catégorie doit être comprise entre 1 et 5 étoiles.";
        } else {
            try {
                if ($action === "create") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO hotel (nom_hotel, adresse, ville, pays, telephone, email, categorie_etoiles, date_ouverture)
                         VALUES (:nom, :adresse, :ville, :pays, :tel, :email, :cat, :date)"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE hotel
                         SET nom_hotel = :nom, adresse = :adresse, ville = :ville, pays = :pays,
                             telephone = :tel, email = :email, categorie_etoiles = :cat, date_ouverture = :date
                         WHERE id_hotel = :id"
                    );
                    $stmt->bindValue(":id", $id_hotel, PDO::PARAM_INT);
                }
                $stmt->execute([
                    ":nom"     => $nom_hotel,
                    ":adresse" => $adresse,
                    ":ville"   => $ville,
                    ":pays"    => $pays,
                    ":tel"     => $telephone !== "" ? $telephone : null,
                    ":email"   => $email !== "" ? $email : null,
                    ":cat"     => $categorie,
                    ":date"    => $date_ouverture,
                ]);

                flash_set($action === "create" ? "Hôtel ajouté." : "Hôtel mis à jour.");
                header("Location: hotels.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_hotel = (int) ($_POST["id_hotel"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM hotel WHERE id_hotel = :id")
                ->execute([":id" => $id_hotel]);
            flash_set("Hôtel supprimé.");
            header("Location: hotels.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : l'hôtel possède des chambres ou employés liés.", "error");
            header("Location: hotels.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM hotel WHERE id_hotel = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
}

$hotels = $pdo->query(
    "SELECT h.*,
            (SELECT COUNT(*) FROM chambre ch WHERE ch.id_hotel = h.id_hotel) AS nb_chambres,
            (SELECT COUNT(*) FROM employe em WHERE em.id_hotel = h.id_hotel) AS nb_employes
     FROM hotel h
     ORDER BY h.nom_hotel"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Hôtels</h1>
        <p>Établissements du parc hôtelier et leur effectif.</p>
    </div>
    <a href="hotels.php#form" class="btn primary">+ Ajouter un hôtel</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier l'hôtel" : "Nouvel hôtel" ?></h3>
                <p><?= $edit ? "Mettez à jour les informations de l'établissement." : "Enregistrez un nouvel établissement." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="hotels.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_hotel" value="<?= (int) $edit["id_hotel"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom_hotel">Nom de l'hôtel <span class="req">*</span></label>
                    <input type="text" id="nom_hotel" name="nom_hotel" required value="<?= htmlspecialchars($edit["nom_hotel"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse <span class="req">*</span></label>
                    <input type="text" id="adresse" name="adresse" required value="<?= htmlspecialchars($edit["adresse"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="ville">Ville <span class="req">*</span></label>
                    <input type="text" id="ville" name="ville" required value="<?= htmlspecialchars($edit["ville"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="pays">Pays <span class="req">*</span></label>
                    <input type="text" id="pays" name="pays" required value="<?= htmlspecialchars($edit["pays"] ?? "Maroc") ?>">
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($edit["telephone"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($edit["email"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="categorie_etoiles">Catégorie (étoiles)</label>
                    <select id="categorie_etoiles" name="categorie_etoiles">
                        <option value="">—</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= isset($edit["categorie_etoiles"]) && (int) $edit["categorie_etoiles"] === $i ? "selected" : "" ?>>
                                <?= $i ?> étoile<?= $i > 1 ? "s" : "" ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_ouverture">Date d'ouverture</label>
                    <input type="date" id="date_ouverture" name="date_ouverture" value="<?= htmlspecialchars($edit["date_ouverture"] ?? "") ?>">
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter l'hôtel" ?></button>
                    <a href="hotels.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des hôtels</h3>
                <p><?= count($hotels) ?> établissement<?= count($hotels) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Établissement</th>
                            <th>Localisation</th>
                            <th>Contact</th>
                            <th>Catégorie</th>
                            <th>Ouverture</th>
                            <th>Chambres</th>
                            <th>Employés</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotels as $h): ?>
                            <tr>
                                <td class="cell-main"><?= htmlspecialchars($h["nom_hotel"]) ?></td>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($h["ville"]) ?></span>
                                    <span class="cell-sub"> <?= htmlspecialchars($h["pays"]) ?> · <?= htmlspecialchars($h["adresse"]) ?></span>
                                </td>
                                <td>
                                    <span class="cell-sub"><?= htmlspecialchars($h["telephone"] ?? "—") ?></span>
                                    <span class="cell-sub"> <?= htmlspecialchars($h["email"] ?? "") ?></span>
                                </td>
                                <td>
                                    <?php if ($h["categorie_etoiles"]): ?>
                                        <span class="stars"><?= str_repeat("★", (int) $h["categorie_etoiles"]) ?></span>
                                    <?php else: ?>
                                        <span class="badge neutral">Non classé</span>
                                    <?php endif; ?>
                                </td>
                                <td class="mono"><?= htmlspecialchars($h["date_ouverture"] ?? "—") ?></td>
                                <td class="num"><?= (int) $h["nb_chambres"] ?></td>
                                <td class="num"><?= (int) $h["nb_employes"] ?></td>
                                <td class="table-actions">
                                    <a href="hotels.php?edit=<?= (int) $h["id_hotel"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer l'hôtel « <?= htmlspecialchars($h["nom_hotel"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_hotel" value="<?= (int) $h["id_hotel"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($hotels)): ?>
                            <tr class="empty"><td colspan="8">Aucun hôtel enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>