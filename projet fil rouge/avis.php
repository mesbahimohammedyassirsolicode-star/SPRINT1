<?php

declare(strict_types=1);

$page_title = "Avis";

require_once __DIR__ . "/includes/header.php";

function initiale(string $s): string {
    if (preg_match("/./u", $s, $m)) {
        return strtoupper($m[0]);
    }
    return "";
}

// ---- Traitement POST ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "delete") {
        $id_avis = (int) ($_POST["id_avis"] ?? 0);
        try {
            $pdo->prepare("DELETE FROM avis WHERE id_avis = :id")
                ->execute([":id" => $id_avis]);
            flash_set("Avis supprimé.");
            header("Location: avis.php");
            exit;
        } catch (PDOException $e) {
            flash_set("Impossible de supprimer l'avis.", "error");
            header("Location: avis.php");
            exit;
        }
    }
}

// ---- Données ----
$avis_list = $pdo->query(
    "SELECT a.*, c.prenom, c.nom, h.nom_hotel, ch.numero_chambre
     FROM avis a
     JOIN client c ON a.id_client = c.id_client
     JOIN reservation r ON a.id_reservation = r.id_reservation
     JOIN chambre ch ON r.id_chambre = ch.id_chambre
     JOIN hotel h ON ch.id_hotel = h.id_hotel
     ORDER BY a.date_avis DESC"
)->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Avis clients</h1>
        <p>Avis et évaluations laissés par les clients.</p>
    </div>
</div>

<div class="stack">
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des avis</h3>
                <p><?= count($avis_list) ?> avis.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Date</th>
                            <th>Établissement</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avis_list as $a): ?>
                            <tr>
                                <td>
                                    <span class="avatar-chip av"><?= htmlspecialchars(initiale($a["prenom"]) . initiale($a["nom"])) ?></span>
                                    <?= htmlspecialchars($a["prenom"] . " " . $a["nom"]) ?>
                                </td>
                                <td>
                                    <span class="stars"><?= str_repeat("★", (int) $a["note"]) . str_repeat("☆", 5 - (int) $a["note"]) ?></span>
                                </td>
                                <td><?= htmlspecialchars($a["commentaire"] ?? "—") ?></td>
                                <td class="mono"><?= htmlspecialchars($a["date_avis"]) ?></td>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($a["nom_hotel"]) ?></span>
                                    <span class="cell-sub">ch. <?= htmlspecialchars($a["numero_chambre"]) ?></span>
                                </td>
                                <td class="table-actions">
                                    <form method="POST" style="display:inline" data-confirm="Supprimer cet avis de <?= htmlspecialchars($a["prenom"] . " " . $a["nom"]) ?> ?">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_avis" value="<?= (int) $a["id_avis"] ?>">
                                        <button type="submit" class="row-action danger" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($avis_list)): ?>
                            <tr class="empty"><td colspan="6">Aucun avis enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>
