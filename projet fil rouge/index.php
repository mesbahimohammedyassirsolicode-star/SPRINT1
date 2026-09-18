<?php

declare(strict_types=1);

require_once __DIR__ . "/includes/header.php";

$nb_hotels    = (int) $pdo->query("SELECT COUNT(*) FROM hotel")->fetchColumn();
$nb_clients   = (int) $pdo->query("SELECT COUNT(*) FROM client")->fetchColumn();
$nb_chambres  = (int) $pdo->query("SELECT COUNT(*) FROM chambre")->fetchColumn();
$nb_occupees  = (int) $pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Occupée'")->fetchColumn();

$ca_paye = (float) $pdo->query(
    "SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE statut_paiement = 'Validé'"
)->fetchColumn();

$statuts = $pdo->query(
    "SELECT statut_reservation, COUNT(*) AS nb
     FROM reservation GROUP BY statut_reservation"
)->fetchAll();
$statut_total = 0;
$statut_map = [];
foreach ($statuts as $s) {
    $statut_map[$s["statut_reservation"]] = (int) $s["nb"];
    $statut_total += (int) $s["nb"];
}

$dernieres = $pdo->query(
    "SELECT r.id_reservation, c.prenom, c.nom, h.nom_hotel, ch.numero_chambre,
            th.libelle AS type_libelle, r.date_arrivee, r.date_depart,
            r.statut_reservation, r.montant_total
     FROM reservation r
     JOIN client c        ON r.id_client  = c.id_client
     JOIN chambre ch      ON r.id_chambre = ch.id_chambre
     JOIN hotel h         ON ch.id_hotel  = h.id_hotel
     JOIN type_hebergement th ON ch.id_type = th.id_type
     ORDER BY r.date_reservation DESC
     LIMIT 6"
)->fetchAll();

$taux_occupation = $nb_chambres > 0
    ? round(($nb_occupees / $nb_chambres) * 100)
    : 0;

function statut_badge(string $statut): string
{
    return match ($statut) {
        "Confirmée"  => "<span class=\"badge ok\">Confirmée</span>",
        "Enregistrée" => "<span class=\"badge info\">Enregistrée</span>",
        "En attente" => "<span class=\"badge warn\">En attente</span>",
        "Terminée"   => "<span class=\"badge neutral\">Terminée</span>",
        "Annulée"    => "<span class=\"badge bad\">Annulée</span>",
        default      => "<span class=\"badge neutral\">" . htmlspecialchars($statut) . "</span>",
    };
}
?>
<div class="page-head">
    <div>
        <h1>Tableau de bord</h1>
        <p>Vue d'ensemble de l'activité des réservations.</p>
    </div>
    <a href="reservation_create.php" class="btn primary">+ Nouvelle réservation</a>
</div>

<div class="kpis">
    <div class="kpi">
        <div class="kpi-label">Hôtels</div>
        <div class="kpi-value"><?= $nb_hotels ?></div>
        <div class="kpi-note">propriétés en portefeuille</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Clients</div>
        <div class="kpi-value"><?= $nb_clients ?></div>
        <div class="kpi-note">profils enregistrés</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Réservations</div>
        <div class="kpi-value"><?= $statut_total ?></div>
        <div class="kpi-note">tous statuts confondus</div>
    </div>
    <div class="kpi green">
        <div class="kpi-label">Chiffre d'affaires payé</div>
        <div class="kpi-value"><?= number_format($ca_paye, 0, ",", " ") ?><small> MAD</small></div>
        <div class="kpi-note">paiements validés uniquement</div>
    </div>
    <div class="kpi gold">
        <div class="kpi-label">Taux d'occupation</div>
        <div class="kpi-value"><?= $taux_occupation ?><small> %</small></div>
        <div class="kpi-note"><?= $nb_occupees ?> / <?= $nb_chambres ?> chambres occupées</div>
    </div>
</div>

<div class="stack">
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Statuts des réservations</h3>
                <p>Répartition du parc de réservations.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="kpis" style="margin-bottom:0">
                <?php foreach (["En attente" => "warn", "Confirmée" => "ok", "Enregistrée" => "info", "Terminée" => "sand", "Annulée" => "red"] as $st => $class): ?>
                    <div class="kpi <?= $class ?>">
                        <div class="kpi-label"><?= htmlspecialchars($st) ?></div>
                        <div class="kpi-value"><?= $statut_map[$st] ?? 0 ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Dernières réservations</h3>
                <p>Les six derniers dossiers créés.</p>
            </div>
            <a href="reservations.php" class="btn secondary sm">Tout voir</a>
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
                            <th>Montant</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieres as $r): ?>
                            <tr>
                                <td class="num">#<?= (int) $r["id_reservation"] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($r["prenom"] . " " . $r["nom"]) ?></td>
                                <td><?= htmlspecialchars($r["nom_hotel"]) ?></td>
                                <td>
                                    <span class="cell-main"><?= htmlspecialchars($r["numero_chambre"]) ?></span>
                                    <span class="cell-sub"> <?= htmlspecialchars($r["type_libelle"]) ?></span>
                                </td>
                                <td>
                                    <span class="mono"><?= htmlspecialchars($r["date_arrivee"]) ?></span>
                                    <span class="cell-sub"> → <?= htmlspecialchars($r["date_depart"]) ?></span>
                                </td>
                                <td class="num"><?= number_format((float) $r["montant_total"], 2, ",", " ") ?> MAD</td>
                                <td><?= statut_badge($r["statut_reservation"]) ?></td>
                                <td class="table-actions">
                                    <a href="reservation_detail.php?id=<?= (int) $r["id_reservation"] ?>" title="Consulter">›</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($dernieres)): ?>
                            <tr class="empty"><td colspan="8">Aucune réservation pour le moment.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . "/includes/footer.php"; ?>