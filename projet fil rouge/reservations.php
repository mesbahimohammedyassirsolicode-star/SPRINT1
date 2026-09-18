<?php

declare(strict_types=1);

$page_title = "Réservations";

require_once __DIR__ . "/includes/header.php";

// ---- Filtres ----
$filter_statut = $_GET["statut"] ?? "";
$filter_source = $_GET["source"] ?? "";
$filter_q     = trim($_GET["q"] ?? "");
$filter_client = isset($_GET["client"]) ? (int) $_GET["client"] : 0;

$has_filters = $filter_statut !== "" || $filter_source !== "" || $filter_q !== "" || $filter_client > 0;

// ---- Badge helpers ----
function statut_badge(string $s): string {
    $map = [
        "En attente"  => "warn",
        "Confirmée"   => "ok",
        "Enregistrée" => "info",
        "Terminée"    => "neutral",
        "Annulée"     => "bad",
    ];
    return '<span class="badge ' . ($map[$s] ?? "neutral") . '">' . htmlspecialchars($s) . '</span>';
}

function source_badge(string $s): string {
    $map = [
        "Site web"  => "accent",
        "Téléphone" => "neutral",
        "Agence"    => "info",
        "Sur place"  => "warn",
    ];
    return '<span class="badge ' . ($map[$s] ?? "neutral") . '">' . htmlspecialchars($s) . '</span>';
}

// ---- Requête ----
$sql = "SELECT r.id_reservation, c.prenom, c.nom, c.email, h.nom_hotel, ch.numero_chambre, th.libelle AS type_libelle,
               r.date_arrivee, r.date_depart, r.nb_adultes, r.nb_enfants,
               r.source_reservation, r.statut_reservation, r.montant_total
        FROM reservation r
        JOIN client c ON r.id_client = c.id_client
        JOIN chambre ch ON r.id_chambre = ch.id_chambre
        JOIN hotel h ON ch.id_hotel = h.id_hotel
        JOIN type_hebergement th ON ch.id_type = th.id_type";

$conditions = [];
$params = [];

if ($filter_statut !== "" && $filter_statut !== "Tous") {
    $conditions[] = "r.statut_reservation = :statut";
    $params[":statut"] = $filter_statut;
}
if ($filter_source !== "" && $filter_source !== "Toutes") {
    $conditions[] = "r.source_reservation = :source";
    $params[":source"] = $filter_source;
}
if ($filter_q !== "") {
    $conditions[] = "(c.nom LIKE :q OR c.prenom LIKE :q2 OR c.email LIKE :q3)";
    $params[":q"]  = "%" . $filter_q . "%";
    $params[":q2"] = "%" . $filter_q . "%";
    $params[":q3"] = "%" . $filter_q . "%";
}
if ($filter_client > 0) {
    $conditions[] = "r.id_client = :client";
    $params[":client"] = $filter_client;
}

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY r.date_reservation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();
?>
<div class="page-head">
    <div>
        <h1>Réservations</h1>
        <p>Liste des réservations du parc hôtelier.</p>
    </div>
</div>

<div class="stack">
    <div class="card">
        <div class="card-head">
            <h3>Filtres</h3>
        </div>
        <div class="card-body" style="padding:0">
            <form method="GET" class="filter-bar">
                <select name="statut">
                    <option value="">Tous les statuts</option>
                    <?php foreach (["En attente","Confirmée","Enregistrée","Terminée","Annulée"] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $filter_statut === $s ? "selected" : "" ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="source">
                    <option value="">Toutes les sources</option>
                    <?php foreach (["Site web","Téléphone","Agence","Sur place"] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $filter_source === $s ? "selected" : "" ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="q" placeholder="Rechercher un client…" value="<?= htmlspecialchars($filter_q) ?>">
                <button type="submit" class="btn secondary sm">Filtrer</button>
                <?php if ($has_filters): ?>
                    <a href="reservations.php" class="btn ghost sm">Effacer</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Liste des réservations</h3>
                <p><?= count($reservations) ?> réservation<?= count($reservations) > 1 ? "s" : "" ?>.</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Établissement</th>
                            <th>Chambre</th>
                            <th>Séjour</th>
                            <th>Personnes</th>
                            <th>Source</th>
                            <th>Statut</th>
                            <th>Montant</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td class="mono">#<?= (int) $r["id_reservation"] ?></td>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($r["prenom"] . " " . $r["nom"]) ?></span>
                                    <span class="cell-sub"><?= htmlspecialchars($r["email"]) ?></span>
                                </td>
                                <td class="cell-main"><?= htmlspecialchars($r["nom_hotel"]) ?></td>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($r["numero_chambre"]) ?></span>
                                    <span class="cell-sub"><?= htmlspecialchars($r["type_libelle"]) ?></span>
                                </td>
                                <td class="mono"><?= htmlspecialchars($r["date_arrivee"]) ?> → <?= htmlspecialchars($r["date_depart"]) ?></td>
                                <td><?= (int) $r["nb_adultes"] ?> A. / <?= (int) $r["nb_enfants"] ?> E.</td>
                                <td><?= source_badge($r["source_reservation"]) ?></td>
                                <td><?= statut_badge($r["statut_reservation"]) ?></td>
                                <td class="num"><?= number_format((float) $r["montant_total"], 2, ",", " ") ?> MAD</td>
                                <td class="table-actions">
                                    <a href="reservation_detail.php?id=<?= (int) $r["id_reservation"] ?>" title="Ouvrir le dossier" class="row-action">›</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reservations)): ?>
                            <tr class="empty"><td colspan="10">Aucune réservation trouvée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>
