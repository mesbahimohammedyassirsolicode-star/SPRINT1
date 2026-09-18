<?php

declare(strict_types=1);

$page_title = "Employés";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_employe   = (int) ($_POST["id_employe"] ?? 0);
        $id_hotel     = (int) ($_POST["id_hotel"] ?? 0);
        $nom          = trim($_POST["nom"] ?? "");
        $prenom       = trim($_POST["prenom"] ?? "");
        $poste        = trim($_POST["poste"] ?? "");
        $telephone    = trim($_POST["telephone"] ?? "");
        $email        = trim($_POST["email"] ?? "");
        $date_embauche = $_POST["date_embauche"] !== "" ? $_POST["date_embauche"] : null;

        if ($nom === "" || $prenom === "" || $poste === "" || $id_hotel === 0) {
            $error = "Nom, prénom, poste et hôtel sont obligatoires.";
        } else {
            try {
                if ($action === "create") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO employe (id_hotel, nom, prenom, poste, telephone, email, date_embauche)
                         VALUES (:hotel, :nom, :prenom, :poste, :tel, :email, :date)"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE employe
                         SET id_hotel = :hotel, nom = :nom, prenom = :prenom, poste = :poste,
                             telephone = :tel, email = :email, date_embauche = :date
                         WHERE id_employe = :id"
                    );
                    $stmt->bindValue(":id", $id_employe, PDO::PARAM_INT);
                }
                $stmt->execute([
                    ":hotel" => $id_hotel,
                    ":nom"   => $nom,
                    ":prenom"=> $prenom,
                    ":poste" => $poste,
                    ":tel"   => $telephone !== "" ? $telephone : null,
                    ":email" => $email !== "" ? $email : null,
                    ":date"  => $date_embauche,
                ]);

                flash_set($action === "create" ? "Employé ajouté." : "Employé mis à jour.");
                header("Location: employes.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_employe = (int) ($_POST["id_employe"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM employe WHERE id_employe = :id")
                ->execute([":id" => $id_employe]);
            flash_set("Employé supprimé.");
            header("Location: employes.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : l'employé est lié à des réservations.", "error");
            header("Location: employes.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM employe WHERE id_employe = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
}

$hotels_list = $pdo->query("SELECT id_hotel, nom_hotel FROM hotel ORDER BY nom_hotel")->fetchAll();

$employes = $pdo->query(
    "SELECT e.*, h.nom_hotel
     FROM employe e
     JOIN hotel h ON h.id_hotel = e.id_hotel
     ORDER BY h.nom_hotel, e.nom, e.prenom"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Employés</h1>
        <p>Personnel des établissements hôteliers.</p>
    </div>
    <a href="employes.php#form" class="btn primary">+ Ajouter un employé</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier l'employé" : "Nouvel employé" ?></h3>
                <p><?= $edit ? "Mettez à jour les informations de l'employé." : "Enregistrez un nouvel employé." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="employes.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_employe" value="<?= (int) $edit["id_employe"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom">Nom <span class="req">*</span></label>
                    <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($edit["nom"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom <span class="req">*</span></label>
                    <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($edit["prenom"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="poste">Poste <span class="req">*</span></label>
                    <input type="text" id="poste" name="poste" required value="<?= htmlspecialchars($edit["poste"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="id_hotel">Hôtel <span class="req">*</span></label>
                    <select id="id_hotel" name="id_hotel" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($hotels_list as $hl): ?>
                            <option value="<?= (int) $hl["id_hotel"] ?>" <?= (int) ($edit["id_hotel"] ?? 0) === (int) $hl["id_hotel"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($hl["nom_hotel"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <label for="date_embauche">Date d'embauche</label>
                    <input type="date" id="date_embauche" name="date_embauche" value="<?= htmlspecialchars($edit["date_embauche"] ?? "") ?>">
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter l'employé" ?></button>
                    <a href="employes.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des employés</h3>
                <p><?= count($employes) ?> employé<?= count($employes) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Poste</th>
                            <th>Contact</th>
                            <th>Embauche</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employes as $e): ?>
                            <tr>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($e["prenom"] . " " . $e["nom"]) ?></span>
                                    <span class="cell-sub"><?= htmlspecialchars($e["nom_hotel"]) ?></span>
                                </td>
                                <td><?= htmlspecialchars($e["poste"]) ?></td>
                                <td>
                                    <span class="mono"><?= htmlspecialchars($e["telephone"] ?? "—") ?></span>
                                    <span class="cell-sub"><?= htmlspecialchars($e["email"] ?? "") ?></span>
                                </td>
                                <td class="mono"><?= htmlspecialchars($e["date_embauche"] ?? "—") ?></td>
                                <td class="table-actions">
                                    <a href="employes.php?edit=<?= (int) $e["id_employe"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer l'employé « <?= htmlspecialchars($e["prenom"] . " " . $e["nom"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_employe" value="<?= (int) $e["id_employe"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($employes)): ?>
                            <tr class="empty"><td colspan="5">Aucun employé enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>
