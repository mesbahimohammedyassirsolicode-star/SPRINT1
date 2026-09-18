<?php

declare(strict_types=1);

$page_title = "Dossier de réservation";

require_once __DIR__ . "/includes/header.php";

$id = (int) ($_GET["id"] ?? 0);

// ---- Chargement de la réservation (JOINs : noms lisibles) ----
$res = null;
if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT r.*, c.prenom AS client_prenom, c.nom AS client_nom, c.email AS client_email,
                c.telephone AS client_tel, c.statut_fidelite,
                h.nom_hotel, h.ville, ch.numero_chambre, ch.etage, ch.vue,
                t.libelle AS type_libelle, t.superficie_m2, ch.prix_nuit,
                em.prenom AS emp_prenom, em.nom AS emp_nom,
                pr.code_promo, pr.pourcentage_reduction
         FROM reservation r
         JOIN client c   ON r.id_client   = c.id_client
         JOIN chambre ch ON r.id_chambre  = ch.id_chambre
         JOIN hotel h    ON ch.id_hotel   = h.id_hotel
         JOIN type_hebergement t ON ch.id_type = t.id_type
         LEFT JOIN employe em   ON r.id_employe  = em.id_employe
         LEFT JOIN promotion pr ON r.id_promotion = pr.id_promotion
         WHERE r.id_reservation = :id"
    );
    $stmt->execute([":id" => $id]);
    $res = $stmt->fetch() ?: null;
}

if (!$res) {
    flash_set("Réservation introuvable.", "error");
    header("Location: reservations.php");
    exit;
}

$errors = [];

// Données liées
$services = $pdo->prepare(
    "SELECT rs.id_reservation_service, rs.quantite, rs.date_utilisation, rs.prix_unitaire_applique,
            s.nom_service, s.id_service
     FROM reservation_service rs
     JOIN service s ON s.id_service = rs.id_service
     WHERE rs.id_reservation = :id"
);
$services->execute([":id" => $id]);
$services = $services->fetchAll();

$paiements = $pdo->prepare(
    "SELECT * FROM paiement WHERE id_reservation = :id ORDER BY date_paiement DESC"
);
$paiements->execute([":id" => $id]);
$paiements = $paiements->fetchAll();

$facture = $pdo->prepare("SELECT * FROM facture WHERE id_reservation = :id");
$facture->execute([":id" => $id]);
$facture = $facture->fetch() ?: null;

$annulation = $pdo->prepare("SELECT * FROM annulation WHERE id_reservation = :id");
$annulation->execute([":id" => $id]);
$annulation = $annulation->fetch() ?: null;

$avis = $pdo->prepare("SELECT * FROM avis WHERE id_reservation = :id");
$avis->execute([":id" => $id]);
$avis = $avis->fetch() ?: null;

$catalogue_services = $pdo->query("SELECT id_service, nom_service, prix_unitaire FROM service ORDER BY nom_service")->fetchAll();

// ---- Traitement POST ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "statut") {
        $new_statut = $_POST["statut_reservation"] ?? "";
        $valides = ["En attente", "Confirmée", "Enregistrée", "Terminée"];
        if (!in_array($new_statut, $valides, true)) {
            $errors[] = "Statut invalide.";
        } else {
            $pdo->prepare("UPDATE reservation SET statut_reservation = :s WHERE id_reservation = :id")
                ->execute([":s" => $new_statut, ":id" => $id]);
            flash_set("Statut passé à « " . $new_statut . " ».");
            header("Location: reservation_detail.php?id=" . $id);
            exit;
        }
    }

    if ($action === "annuler") {
        $motif = trim($_POST["motif"] ?? "");
        $montant_rembourse = (float) str_replace(",", ".", $_POST["montant_rembourse"] ?? "0");
        if ($motif === "") {
            $errors[] = "Le motif d'annulation est obligatoire.";
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare(
                    "INSERT INTO annulation (id_reservation, motif, montant_rembourse)
                     VALUES (:id, :motif, :montant)"
                )->execute([":id" => $id, ":motif" => $motif, ":montant" => $montant_rembourse]);
                $pdo->prepare("UPDATE reservation SET statut_reservation = 'Annulée' WHERE id_reservation = :id")
                    ->execute([":id" => $id]);
                $pdo->commit();
                flash_set("Réservation annulée.");
                header("Location: reservation_detail.php?id=" . $id);
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errors[] = "Erreur : " . $e->getMessage();
            }
        }
    }

    if ($action === "service_add") {
        $id_service = (int) ($_POST["id_service"] ?? 0);
        $quantite   = max(1, (int) ($_POST["quantite"] ?? 1));
        $date_util  = ($_POST["date_utilisation"] ?? "") !== "" ? $_POST["date_utilisation"] : null;
        $prix_applique = (float) str_replace(",", ".", $_POST["prix_unitaire_applique"] ?? "0");

        if ($id_service <= 0 || $prix_applique < 0) {
            $errors[] = "Service et prix invalides.";
        } else {
            $pdo->prepare(
                "INSERT INTO reservation_service (id_reservation, id_service, quantite, date_utilisation, prix_unitaire_applique)
                 VALUES (:res, :serv, :qte, :date, :prix)"
            )->execute([
                ":res" => $id, ":serv" => $id_service, ":qte" => $quantite,
                ":date" => $date_util, ":prix" => $prix_applique,
            ]);
            flash_set("Service ajouté au dossier.");
            header("Location: reservation_detail.php?id=" . $id);
            exit;
        }
    }

    if ($action === "service_delete") {
        $id_rs = (int) ($_POST["id_reservation_service"] ?? 0);
        $pdo->prepare("DELETE FROM reservation_service WHERE id_reservation_service = :id AND id_reservation = :res")
            ->execute([":id" => $id_rs, ":res" => $id]);
        flash_set("Service retiré du dossier.");
        header("Location: reservation_detail.php?id=" . $id);
        exit;
    }

    if ($action === "paiement_add") {
        $montant_p = (float) str_replace(",", ".", $_POST["montant"] ?? "0");
        $mode_p    = $_POST["mode_paiement"] ?? "Carte bancaire";
        $statut_p  = $_POST["statut_paiement"] ?? "En attente";
        $reference = trim($_POST["reference_transaction"] ?? "");

        if ($montant_p <= 0) {
            $errors[] = "Le montant du paiement doit être supérieur à 0.";
        } else {
            $pdo->prepare(
                "INSERT INTO paiement (id_reservation, montant, mode_paiement, statut_paiement, reference_transaction)
                 VALUES (:res, :montant, :mode, :statut, :ref)"
            )->execute([
                ":res" => $id, ":montant" => $montant_p, ":mode" => $mode_p,
                ":statut" => $statut_p, ":ref" => $reference !== "" ? $reference : null,
            ]);
            flash_set("Paiement enregistré.");
            header("Location: reservation_detail.php?id=" . $id);
            exit;
        }
    }
}

$nuits = (new DateTime($res["date_arrivee"]))->diff(new DateTime($res["date_depart"]))->days;
$total_services = array_sum(array_map(
    fn(array $s): float => (float) $s["prix_unitaire_applique"] * (int) $s["quantite"],
    $services
));
$total_paye = array_sum(array_map(
    fn(array $p): float => $p["statut_paiement"] === "Validé" ? (float) $p["montant"] : 0.0,
    $paiements
));

function badge_statut_res(string $statut): string
{
    $map = [
        "En attente"  => "warn",
        "Confirmée"   => "ok",
        "Enregistrée" => "info",
        "Terminée"    => "neutral",
        "Annulée"     => "bad",
    ];
    return '<span class="badge ' . ($map[$statut] ?? "neutral") . '">' . htmlspecialchars($statut) . '</span>';
}

function badge_paiement(string $statut): string
{
    $map = ["En attente" => "warn", "Validé" => "ok", "Refusé" => "bad", "Remboursé" => "info"];
    return '<span class="badge ' . ($map[$statut] ?? "neutral") . '">' . htmlspecialchars($statut) . '</span>';
}

function badge_facture(string $statut): string
{
    $map = ["Émise" => "accent", "Payée" => "ok", "Impayée" => "warn", "Annulée" => "bad"];
    return '<span class="badge ' . ($map[$statut] ?? "neutral") . '">' . htmlspecialchars($statut) . '</span>';
}
?>
<div class="page-head">
    <div>
        <h1>Dossier <span class="mono">#<?= $id ?></span></h1>
        <p>
            <?= htmlspecialchars($res["client_prenom"] . " " . $res["client_nom"]) ?> &middot;
            <?= htmlspecialchars($res["nom_hotel"] . " — " . $res["ville"]) ?>,
            chambre <?= htmlspecialchars($res["numero_chambre"]) ?>
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?= badge_statut_res($res["statut_reservation"]) ?>
        <?php if ($res["statut_reservation"] !== "Annulée"): ?>
            <a href="reservation_edit.php?id=<?= $id ?>" class="btn secondary sm">✎ Modifier</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert error">
        <strong>Veuillez corriger :</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="stack">
    <!-- 1. INFORMATIONS -->
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Informations du séjour</h3>
                <p>Données principales du dossier.</p>
            </div>
            <div class="stay-header" style="margin:0">
                <span class="nights-chip"><?= $nuits ?> nuit<?= $nuits > 1 ? "s" : "" ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <dt>Client</dt>
                    <dd><?= htmlspecialchars($res["client_prenom"] . " " . $res["client_nom"]) ?></dd>
                    <dd class="cell-sub"><?= htmlspecialchars($res["client_email"]) ?> · <?= htmlspecialchars($res["client_tel"] ?? "—") ?></dd>
                </div>
                <div class="detail-item">
                    <dt>Fidélité</dt>
                    <dd>
                        <span class="badge <?= $res["statut_fidelite"] === "Platinum" ? "ok" : ($res["statut_fidelite"] === "Gold" ? "warn" : ($res["statut_fidelite"] === "Silver" ? "accent" : "neutral")) ?>">
                            <?= htmlspecialchars($res["statut_fidelite"]) ?>
                        </span>
                    </dd>
                </div>
                <div class="detail-item">
                    <dt>Chambre</dt>
                    <dd><?= htmlspecialchars($res["numero_chambre"]) ?> · <?= htmlspecialchars($res["type_libelle"]) ?></dd>
                    <dd class="cell-sub">Étage <?= htmlspecialchars((string) ($res["etage"] ?? "—")) ?> · Vue <?= htmlspecialchars($res["vue"]) ?> · <?= htmlspecialchars($res["superficie_m2"] ?? "—") ?> m²</dd>
                </div>
                <div class="detail-item">
                    <dt>Séjour</dt>
                    <dd class="mono"><?= htmlspecialchars($res["date_arrivee"]) ?> → <?= htmlspecialchars($res["date_depart"]) ?></dd>
                </div>
                <div class="detail-item">
                    <dt>Passagers</dt>
                    <dd><?= (int) $res["nb_adultes"] ?> adulte(s) / <?= (int) $res["nb_enfants"] ?> enfant(s)</dd>
                </div>
                <div class="detail-item">
                    <dt>Source</dt>
                    <dd><?= htmlspecialchars($res["source_reservation"]) ?></dd>
                </div>
                <div class="detail-item">
                    <dt>Montant du séjour</dt>
                    <dd><?= number_format((float) $res["montant_total"], 2, ",", " ") ?> MAD</dd>
                    <dd class="cell-sub">
                        <?php if ($res["code_promo"]): ?>
                            Promo <?= htmlspecialchars($res["code_promo"]) ?> (−<?= number_format((float) $res["pourcentage_reduction"], 1, ",", " ") ?> %)
                        <?php else: ?>
                            Sans code promo
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="detail-item">
                    <dt>Créée le</dt>
                    <dd class="mono"><?= htmlspecialchars($res["date_reservation"]) ?></dd>
                </div>
                <?php if ($res["emp_prenom"]): ?>
                    <div class="detail-item">
                        <dt>Traitée par</dt>
                        <dd><?= htmlspecialchars($res["emp_prenom"] . " " . $res["emp_nom"]) ?></dd>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="res-grid">
        <!-- 2. STATUT ET ANNULATION -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Cycle de vie</h3>
                    <p>Faire évoluer le statut ou annuler le dossier.</p>
                </div>
            </div>
            <div class="card-body">
                <?php if ($res["statut_reservation"] !== "Annulée"): ?>
                    <form method="POST" class="form-grid">
                        <input type="hidden" name="action" value="statut">
                        <div class="form-group">
                            <label for="statut_reservation">Nouveau statut</label>
                            <select id="statut_reservation" name="statut_reservation">
                                <?php foreach (["En attente", "Confirmée", "Enregistrée", "Terminée"] as $st): ?>
                                    <option value="<?= $st ?>" <?= $res["statut_reservation"] === $st ? "selected" : "" ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-actions" style="grid-column:1/-1;margin-top:0">
                            <button type="submit" class="btn secondary sm">Appliquer</button>
                        </div>
                    </form>
                    <hr class="form-divider">
                    <form method="POST" class="form-grid" data-confirm="Confirmer l'annulation de la réservation #<?= $id ?> ?">
                        <input type="hidden" name="action" value="annuler">
                        <div class="form-group">
                            <label for="motif">Motif d'annulation <span class="req">*</span></label>
                            <input type="text" id="motif" name="motif" required placeholder="Ex : changement de plan">
                        </div>
                        <div class="form-group">
                            <label for="montant_rembourse">Montant remboursé (MAD)</label>
                            <input type="number" id="montant_rembourse" name="montant_rembourse" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-actions" style="grid-column:1/-1;margin-top:0">
                            <button type="submit" class="btn danger">Annuler la réservation</button>
                        </div>
                    </form>
                <?php elseif ($annulation): ?>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <dt>Annulée le</dt>
                            <dd class="mono"><?= htmlspecialchars($annulation["date_annulation"]) ?></dd>
                        </div>
                        <div class="detail-item">
                            <dt>Motif</dt>
                            <dd><?= htmlspecialchars($annulation["motif"] ?? "—") ?></dd>
                        </div>
                        <div class="detail-item">
                            <dt>Remboursement</dt>
                            <dd><?= number_format((float) $annulation["montant_rembourse"], 2, ",", " ") ?> MAD</dd>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. FACTURE -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Facture</h3>
                    <p>Une facture par réservation.</p>
                </div>
            </div>
            <div class="card-body">
                <?php if ($facture): ?>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <dt>Numéro</dt>
                            <dd class="mono cell-main"><?= htmlspecialchars($facture["numero_facture"]) ?></dd>
                        </div>
                        <div class="detail-item">
                            <dt>Émise le</dt>
                            <dd class="mono"><?= htmlspecialchars($facture["date_emission"]) ?></dd>
                        </div>
                        <div class="detail-item">
                            <dt>Montants</dt>
                            <dd><?= number_format((float) $facture["montant_ht"], 2, ",", " ") ?> HT</dd>
                            <dd class="cell-sub">+ TVA <?= number_format((float) $facture["taux_tva"], 2, ",", " ") ?> % → <?= number_format((float) $facture["montant_ttc"], 2, ",", " ") ?> TTC</dd>
                        </div>
                        <div class="detail-item">
                            <dt>Statut</dt>
                            <dd><?= badge_facture($facture["statut_facture"]) ?></dd>
                        </div>
                    </div>
                <?php elseif ($res["statut_reservation"] !== "Annulée"): ?>
                    <p style="font-size:var(--text-sm);color:var(--ink-soft);margin-bottom:var(--space-4)">
                        Aucune facture pour ce dossier. Vous pouvez la générer depuis le module Factures.
                    </p>
                    <a href="factures.php?add=<?= $id ?>" class="btn primary sm">Générer la facture</a>
                <?php else: ?>
                    <p style="font-size:var(--text-sm);color:var(--ink-soft)">Réservation annulée : pas de facture.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 4. SERVICES CONSOMMÉS -->
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Services consommés</h3>
                <p>Total : <?= number_format($total_services, 2, ",", " ") ?> MAD.</p>
            </div>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr><th>Service</th><th>Qté</th><th>Prix appliqué</th><th>Date d'utilisation</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td class="cell-main"><?= htmlspecialchars($s["nom_service"]) ?></td>
                            <td class="num"><?= (int) $s["quantite"] ?></td>
                            <td class="num"><?= number_format((float) $s["prix_unitaire_applique"], 2, ",", " ") ?> MAD</td>
                            <td class="mono"><?= htmlspecialchars($s["date_utilisation"] ?? "—") ?></td>
                            <td class="table-actions">
                                <form method="POST" style="display:inline" data-confirm="Retirer ce service du dossier ?">
                                    <input type="hidden" name="action" value="service_delete">
                                    <input type="hidden" name="id_reservation_service" value="<?= (int) $s["id_reservation_service"] ?>">
                                    <button type="submit" class="row-action danger" title="Retirer">✕</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($services)): ?>
                        <tr class="empty"><td colspan="5">Aucun service consommé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($res["statut_reservation"] !== "Annulée" && $res["statut_reservation"] !== "Terminée"): ?>
                <form method="POST" class="form-grid mt-4">
                    <input type="hidden" name="action" value="service_add">
                    <div class="form-group">
                        <label for="id_service">Service</label>
                        <select id="id_service" name="id_service">
                            <?php foreach ($catalogue_services as $cs): ?>
                                <option value="<?= (int) $cs["id_service"] ?>" data-prix="<?= (float) $cs["prix_unitaire"] ?>">
                                    <?= htmlspecialchars($cs["nom_service"] . " — " . number_format((float) $cs["prix_unitaire"], 2, ",", " ") . " MAD") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantite">Quantité</label>
                        <input type="number" id="quantite" name="quantite" min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label for="prix_unitaire_applique">Prix appliqué (MAD)</label>
                        <input type="number" id="prix_unitaire_applique" name="prix_unitaire_applique" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="date_utilisation">Date d'utilisation</label>
                        <input type="date" id="date_utilisation" name="date_utilisation" value="<?= htmlspecialchars($res["date_arrivee"]) ?>">
                    </div>
                    <div class="form-actions" style="margin-top:0">
                        <button type="submit" class="btn secondary sm">Ajouter le service</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- 5. PAIEMENTS -->
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Paiements</h3>
                <p>Total validé : <?= number_format($total_paye, 2, ",", " ") ?> MAD sur <?= number_format((float) $res["montant_total"], 2, ",", " ") ?> MAD.</p>
            </div>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Montant</th><th>Mode</th><th>Statut</th><th>Référence</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($paiements as $p): ?>
                        <tr>
                            <td class="mono"><?= htmlspecialchars($p["date_paiement"]) ?></td>
                            <td class="num"><?= number_format((float) $p["montant"], 2, ",", " ") ?> MAD</td>
                            <td><?= htmlspecialchars($p["mode_paiement"]) ?></td>
                            <td><?= badge_paiement($p["statut_paiement"]) ?></td>
                            <td class="mono cell-sub"><?= htmlspecialchars($p["reference_transaction"] ?? "—") ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paiements)): ?>
                        <tr class="empty"><td colspan="5">Aucun paiement enregistré.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($res["statut_reservation"] !== "Annulée"): ?>
                <form method="POST" class="form-grid mt-4">
                    <input type="hidden" name="action" value="paiement_add">
                    <div class="form-group">
                        <label for="montant_p">Montant (MAD) <span class="req">*</span></label>
                        <input type="number" id="montant_p" name="montant" step="0.01" min="0.01" required
                               value="<?= number_format((float) $res["montant_total"] - $total_paye, 2, ".", "") ?>">
                    </div>
                    <div class="form-group">
                        <label for="mode_paiement">Mode</label>
                        <select id="mode_paiement" name="mode_paiement">
                            <?php foreach (["Carte bancaire", "Espèces", "Virement", "Chèque", "PayPal"] as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statut_paiement">Statut</label>
                        <select id="statut_paiement" name="statut_paiement">
                            <?php foreach (["En attente", "Validé", "Refusé", "Remboursé"] as $sp): ?>
                                <option value="<?= $sp ?>" <?= $sp === "Validé" ? "selected" : "" ?>><?= $sp ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reference_transaction">Référence</label>
                        <input type="text" id="reference_transaction" name="reference_transaction">
                    </div>
                    <div class="form-actions" style="margin-top:0">
                        <button type="submit" class="btn secondary sm">Enregistrer le paiement</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- 6. AVIS -->
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Avis client</h3>
                <p>Un avis par séjour, note de 1 à 5.</p>
            </div>
        </div>
        <div class="card-body">
            <?php if ($avis): ?>
                <div class="detail-grid">
                    <div class="detail-item">
                        <dt>Note</dt>
                        <dd><span class="stars"><?= str_repeat("★", (int) $avis["note"]) . str_repeat("☆", 5 - (int) $avis["note"]) ?></span></dd>
                    </div>
                    <div class="detail-item">
                        <dt>Publié le</dt>
                        <dd class="mono"><?= htmlspecialchars($avis["date_avis"]) ?></dd>
                    </div>
                    <div class="detail-item" style="grid-column:1/-1">
                        <dt>Commentaire</dt>
                        <dd style="font-weight:400;line-height:1.6"><?= nl2br(htmlspecialchars($avis["commentaire"] ?? "—")) ?></dd>
                    </div>
                </div>
            <?php else: ?>
                <p style="font-size:var(--text-sm);color:var(--ink-soft)">Aucun avis laissé pour ce séjour. Les avis sont gérés depuis la page « Avis clients ».</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const serviceSelect = document.getElementById("id_service");
    const prixInput = document.getElementById("prix_unitaire_applique");
    if (serviceSelect && prixInput) {
        const fill = () => {
            const opt = serviceSelect.options[serviceSelect.selectedIndex];
            if (opt && opt.dataset.prix) { prixInput.value = opt.dataset.prix; }
        };
        serviceSelect.addEventListener("change", fill);
        fill();
    }
});
</script>
<?php require __DIR__ . "/includes/footer.php"; ?>