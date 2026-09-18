<?php

declare(strict_types=1);

function initiales(string $prenom, string $nom): string
{
    $p = "";
    if (preg_match('/./u', $prenom, $m)) { $p = $m[0]; }
    $n = "";
    if (preg_match('/./u', $nom, $m)) { $n = $m[0]; }
    return strtoupper($p . $n);
}

$page_title = "Clients";

require_once __DIR__ . "/includes/header.php";

$error = "";

// ---- Traitement des actions ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {
        $id_client        = (int) ($_POST["id_client"] ?? 0);
        $prenom           = trim($_POST["prenom"] ?? "");
        $nom              = trim($_POST["nom"] ?? "");
        $email            = trim($_POST["email"] ?? "");
        $mot_de_passe     = trim($_POST["mot_de_passe"] ?? "");
        $telephone        = trim($_POST["telephone"] ?? "");
        $adresse          = trim($_POST["adresse"] ?? "");
        $ville            = trim($_POST["ville"] ?? "");
        $pays             = trim($_POST["pays"] ?? "");
        $cin_passeport    = trim($_POST["cin_passeport"] ?? "");
        $date_naissance   = $_POST["date_naissance"] !== "" ? $_POST["date_naissance"] : null;
        $statut_fidelite  = $_POST["statut_fidelite"] ?? "Standard";

        if ($prenom === "" || $nom === "" || $email === "") {
            $error = "Prénom, nom et email sont obligatoires.";
        } else {
            try {
                if ($action === "create") {
                    $password = $mot_de_passe !== "" ? $mot_de_passe : "changer-moi";
                    $stmt = $pdo->prepare(
                        "INSERT INTO client (prenom, nom, email, mot_de_passe, telephone, adresse, ville, pays, cin_passeport, date_naissance, statut_fidelite)
                         VALUES (:prenom, :nom, :email, :mdp, :tel, :adresse, :ville, :pays, :cin, :date_naiss, :statut)"
                    );
                    $stmt->execute([
                        ":prenom"     => $prenom,
                        ":nom"        => $nom,
                        ":email"      => $email,
                        ":mdp"        => $password,
                        ":tel"        => $telephone !== "" ? $telephone : null,
                        ":adresse"    => $adresse !== "" ? $adresse : null,
                        ":ville"      => $ville !== "" ? $ville : null,
                        ":pays"       => $pays !== "" ? $pays : null,
                        ":cin"        => $cin_passeport !== "" ? $cin_passeport : null,
                        ":date_naiss" => $date_naissance,
                        ":statut"     => $statut_fidelite,
                    ]);
                } else {
                    $sql = "UPDATE client SET prenom = :prenom, nom = :nom, email = :email,
                                telephone = :tel, adresse = :adresse, ville = :ville, pays = :pays,
                                cin_passeport = :cin, date_naissance = :date_naiss, statut_fidelite = :statut";
                    $params = [
                        ":prenom"     => $prenom,
                        ":nom"        => $nom,
                        ":email"      => $email,
                        ":tel"        => $telephone !== "" ? $telephone : null,
                        ":adresse"    => $adresse !== "" ? $adresse : null,
                        ":ville"      => $ville !== "" ? $ville : null,
                        ":pays"       => $pays !== "" ? $pays : null,
                        ":cin"        => $cin_passeport !== "" ? $cin_passeport : null,
                        ":date_naiss" => $date_naissance,
                        ":statut"     => $statut_fidelite,
                        ":id"         => $id_client,
                    ];
                    if ($mot_de_passe !== "") {
                        $sql .= ", mot_de_passe = :mdp";
                        $params[":mdp"] = $mot_de_passe;
                    }
                    $sql .= " WHERE id_client = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }

                flash_set($action === "create" ? "Client ajouté." : "Client mis à jour.");
                header("Location: clients.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id_client = (int) ($_POST["id_client"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM client WHERE id_client = :id")
                ->execute([":id" => $id_client]);
            flash_set("Client supprimé.");
            header("Location: clients.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer : le client possède des réservations.", "error");
            header("Location: clients.php");
            exit;
        }
    }
}

// ---- Mode édition ? ----
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = :id");
    $stmt->execute([":id" => (int) $_GET["edit"]]);
    $edit = $stmt->fetch() ?: null;
}

$clients = $pdo->query(
    "SELECT c.*,
            (SELECT COUNT(*) FROM reservation r WHERE r.id_client = c.id_client) AS nb_reservation
     FROM client c
     ORDER BY c.nom, c.prenom"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Clients</h1>
        <p>Gestion des clients et programmes de fidélité.</p>
    </div>
    <a href="clients.php#form" class="btn primary">+ Ajouter un client</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stack">
    <div class="card" id="form">
        <div class="card-head">
            <div>
                <h3><?= $edit ? "Modifier le client" : "Nouveau client" ?></h3>
                <p><?= $edit ? "Mettez à jour les informations du client." : "Enregistrez un nouveau client." ?></p>
            </div>
            <?php if ($edit): ?>
                <a href="clients.php" class="btn ghost sm">Annuler l'édition</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="<?= $edit ? "update" : "create" ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_client" value="<?= (int) $edit["id_client"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="prenom">Prénom <span class="req">*</span></label>
                    <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($edit["prenom"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="nom">Nom <span class="req">*</span></label>
                    <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($edit["nom"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($edit["email"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe<?= $edit ? " (laisser vide pour conserver)" : "" ?></label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" <?= $edit ? "" : "required" ?> value="">
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($edit["telephone"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($edit["adresse"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="ville">Ville</label>
                    <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($edit["ville"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="pays">Pays</label>
                    <input type="text" id="pays" name="pays" value="<?= htmlspecialchars($edit["pays"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="cin_passeport">CIN / Passeport</label>
                    <input type="text" id="cin_passeport" name="cin_passeport" value="<?= htmlspecialchars($edit["cin_passeport"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="date_naissance">Date de naissance</label>
                    <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($edit["date_naissance"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="statut_fidelite">Statut fidélité</label>
                    <select id="statut_fidelite" name="statut_fidelite">
                        <?php foreach (["Standard", "Silver", "Gold", "Platinum"] as $s): ?>
                            <option value="<?= $s ?>" <?= ($edit["statut_fidelite"] ?? "Standard") === $s ? "selected" : "" ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1; margin-top:0">
                    <button type="submit" class="btn primary"><?= $edit ? "Enregistrer" : "Ajouter le client" ?></button>
                    <a href="clients.php" class="btn ghost">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des clients</h3>
                <p><?= count($clients) ?> client<?= count($clients) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Localisation</th>
                            <th>Fidélité</th>
                            <th>Réservations</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $c): ?>
                            <tr>
                                <td>
                                    <span class="avatar-chip">
                                        <span class="av"><?= initiales($c["prenom"], $c["nom"]) ?></span>
                                        <span>
                                            <span class="cell-main"><?= htmlspecialchars($c["prenom"] . " " . $c["nom"]) ?></span>
                                            <span class="cell-sub mono"><?= htmlspecialchars($c["email"]) ?></span>
                                        </span>
                                    </span>
                                </td>
                                <td class="mono"><?= htmlspecialchars($c["telephone"] ?? "—") ?></td>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($c["ville"] ?? "—") ?></span>
                                    <span class="cell-sub"><?= htmlspecialchars($c["pays"] ?? "") ?></span>
                                </td>
                                <td><span class="badge <?= $c["statut_fidelite"] === "Platinum" ? "ok" : ($c["statut_fidelite"] === "Gold" ? "warn" : ($c["statut_fidelite"] === "Silver" ? "accent" : "neutral")) ?>"><?= htmlspecialchars($c["statut_fidelite"]) ?></span></td>
                                <td class="num"><?= (int) $c["nb_reservation"] ?></td>
                                <td class="table-actions">
                                    <a href="clients.php?edit=<?= (int) $c["id_client"] ?>" title="Modifier">✎</a>
                                    <form method="POST" style="display:inline" data-confirm="Supprimer le client « <?= htmlspecialchars($c["prenom"] . " " . $c["nom"]) ?> » ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_client" value="<?= (int) $c["id_client"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                            <tr class="empty"><td colspan="6">Aucun client enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>
