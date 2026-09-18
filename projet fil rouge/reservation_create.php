<?php

declare(strict_types=1);

$page_title = "Nouvelle réservation";

require_once __DIR__ . "/includes/header.php";

$errors = [];

// ---- Serveurs de référence (sélecteurs) ----
$clients = $pdo->query(
    "SELECT id_client, prenom, nom, email, statut_fidelite
     FROM client ORDER BY nom, prenom"
)->fetchAll();

$chambres = $pdo->query(
    "SELECT ch.id_chambre, ch.numero_chambre, ch.prix_nuit, ch.vue, ch.etage, ch.statut_chambre,
            h.nom_hotel, t.libelle AS type_libelle
     FROM chambre ch
     JOIN hotel h ON h.id_hotel = ch.id_hotel
     JOIN type_hebergement t ON t.id_type = ch.id_type
     ORDER BY h.nom_hotel, ch.numero_chambre"
)->fetchAll();

$promotions = $pdo->query(
    "SELECT id_promotion, code_promo, pourcentage_reduction
     FROM promotion
     WHERE actif = 1 AND date_debut <= CURDATE() AND date_fin >= CURDATE()
     ORDER BY code_promo"
)->fetchAll();

$employes = $pdo->query(
    "SELECT e.id_employe, e.prenom, e.nom, e.poste, h.nom_hotel
     FROM employe e
     JOIN hotel h ON h.id_hotel = e.id_hotel
     ORDER BY e.prenom, e.nom"
)->fetchAll();

// ---- Traitement ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_client     = (int) ($_POST["id_client"] ?? 0);
    $id_chambre    = (int) ($_POST["id_chambre"] ?? 0);
    $id_promotion  = ($_POST["id_promotion"] ?? "") !== "" ? (int) $_POST["id_promotion"] : null;
    $id_employe    = ($_POST["id_employe"] ?? "") !== "" ? (int) $_POST["id_employe"] : null;
    $date_arrivee  = trim($_POST["date_arrivee"] ?? "");
    $date_depart   = trim($_POST["date_depart"] ?? "");
    $nb_adultes    = (int) ($_POST["nb_adultes"] ?? 0);
    $nb_enfants    = (int) ($_POST["nb_enfants"] ?? 0);
    $source        = $_POST["source_reservation"] ?? "Site web";
    $statut        = $_POST["statut_reservation"] ?? "En attente";

    if ($id_client <= 0)               $errors[] = "Sélectionnez un client.";
    if ($id_chambre <= 0)              $errors[] = "Sélectionnez une chambre.";
    if ($date_arrivee === "" || $date_depart === "") $errors[] = "Les dates d'arrivée et de départ sont obligatoires.";
    elseif ($date_depart <= $date_arrivee)           $errors[] = "La date de départ doit être postérieure à l'arrivée.";
    if ($nb_adultes <= 0)              $errors[] = "Le nombre d'adultes doit être supérieur à 0.";

    if (empty($errors)) {
        // Calcul du montant : nuits × prix, puis remise promo
        $nuits = (new DateTime($date_arrivee))->diff(new DateTime($date_depart))->days;
        $chambre_info = $pdo->prepare("SELECT prix_nuit FROM chambre WHERE id_chambre = :id");
        $chambre_info->execute([":id" => $id_chambre]);
        $prix_nuit = (float) ($chambre_info->fetchColumn() ?: 0);

        $montant = $prix_nuit * $nuits;

        if ($id_promotion !== null) {
            $promo_stmt = $pdo->prepare("SELECT pourcentage_reduction FROM promotion WHERE id_promotion = :id");
            $promo_stmt->execute([":id" => $id_promotion]);
            $pct = (float) ($promo_stmt->fetchColumn() ?: 0);
            if ($pct > 0) {
                $montant *= (1 - $pct / 100);
            }
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO reservation
                    (id_client, id_chambre, id_employe, id_promotion, date_arrivee, date_depart,
                     nb_adultes, nb_enfants, statut_reservation, source_reservation, montant_total)
                 VALUES
                    (:client, :chambre, :employe, :promo, :arrivee, :depart,
                     :adultes, :enfants, :statut, :source, :montant)"
            );
            $stmt->execute([
                ":client"  => $id_client,
                ":chambre" => $id_chambre,
                ":employe" => $id_employe,
                ":promo"   => $id_promotion,
                ":arrivee" => $date_arrivee,
                ":depart"  => $date_depart,
                ":adultes" => $nb_adultes,
                ":enfants" => $nb_enfants,
                ":statut"  => $statut,
                ":source"  => $source,
                ":montant" => round($montant, 2),
            ]);

            flash_set("Réservation #" . (int) $pdo->lastInsertId() . " créée (" . number_format($montant, 2, ",", " ") . " MAD).");
            header("Location: reservations.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur base de données : " . $e->getMessage();
        }
    }
}
?>
<div class="page-head">
    <div>
        <h1>Nouvelle réservation</h1>
        <p>Créez un dossier de réservation à partir du client, de la chambre et des dates.</p>
    </div>
    <a href="reservations.php" class="btn secondary">← Retour aux réservations</a>
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

<form method="POST" class="res-grid">
    <div class="stack">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Client &amp; chambre</h3>
                    <p>Le couple de base du dossier.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_client">Client <span class="req">*</span></label>
                        <select id="id_client" name="id_client" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= (int) $c["id_client"] ?>">
                                    <?= htmlspecialchars($c["prenom"] . " " . $c["nom"] . " (" . $c["email"] . ")") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_chambre">Chambre <span class="req">*</span></label>
                        <select id="id_chambre" name="id_chambre" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($chambres as $ch): ?>
                                <option value="<?= (int) $ch["id_chambre"] ?>" data-prix="<?= (float) $ch["prix_nuit"] ?>">
                                    <?= htmlspecialchars($ch["nom_hotel"] . " · n°" . $ch["numero_chambre"] . " — " . $ch["type_libelle"] . " (" . number_format((float) $ch["prix_nuit"], 0, ",", " ") . " MAD)") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint">Sélectionnez la chambre ; la vue et l'étage restent visibles dans le détail.</span>
                    </div>
                    <div class="form-group">
                        <label for="id_promotion">Code promo</label>
                        <select id="id_promotion" name="id_promotion">
                            <option value="">Aucune</option>
                            <?php foreach ($promotions as $p): ?>
                                <option value="<?= (int) $p["id_promotion"] ?>" data-remise="<?= (float) $p["pourcentage_reduction"] ?>">
                                    <?= htmlspecialchars($p["code_promo"] . " (−" . number_format((float) $p["pourcentage_reduction"], 1, ",", " ") . " %)") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_employe">Traité par</label>
                        <select id="id_employe" name="id_employe">
                            <option value="">—</option>
                            <?php foreach ($employes as $em): ?>
                                <option value="<?= (int) $em["id_employe"] ?>">
                                    <?= htmlspecialchars($em["prenom"] . " " . $em["nom"] . " — " . $em["poste"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Séjour &amp; passagers</h3>
                    <p>Dates, composition du voyage et canaux.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="date_arrivee">Arrivée <span class="req">*</span></label>
                        <input type="date" id="date_arrivee" name="date_arrivee" required value="<?= htmlspecialchars(date("Y-m-d")) ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_depart">Départ <span class="req">*</span></label>
                        <input type="date" id="date_depart" name="date_depart" required value="<?= htmlspecialchars(date("Y-m-d", strtotime("+3 days"))) ?>">
                    </div>
                    <div class="form-group">
                        <label for="nb_adultes">Adultes <span class="req">*</span></label>
                        <input type="number" id="nb_adultes" name="nb_adultes" min="1" required value="2">
                    </div>
                    <div class="form-group">
                        <label for="nb_enfants">Enfants</label>
                        <input type="number" id="nb_enfants" name="nb_enfants" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="source_reservation">Source</label>
                        <select id="source_reservation" name="source_reservation">
                            <?php foreach (["Site web", "Téléphone", "Agence", "Sur place"] as $src): ?>
                                <option value="<?= $src ?>"><?= $src ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statut_reservation">Statut</label>
                        <select id="statut_reservation" name="statut_reservation">
                            <option value="En attente">En attente</option>
                            <option value="Confirmée" selected>Confirmée</option>
                        </select>
                    </div>
                </div>
                <hr class="form-divider">
                <div class="form-actions" style="margin-top:0">
                    <button type="submit" class="btn primary">Créer la réservation</button>
                    <a href="reservations.php" class="btn ghost">Annuler</a>
                </div>
            </div>
        </div>
    </div>

    <aside class="stack">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Devis estimatif</h3>
                    <p>Calcul automatique du séjour.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="stay-header">
                    <div>
                        <div class="cell-sub" style="display:block">Séjour</div>
                        <span id="sum-nuits" class="nights-chip">0 nuit</span>
                    </div>
                </div>
                <div class="sum-line">
                    <span>Prix / nuit</span>
                    <b id="sum-prix">0,00 MAD</b>
                </div>
                <div class="sum-line">
                    <span>Nuits</span>
                    <b id="sum-nb">0</b>
                </div>
                <div class="sum-line">
                    <span>Sous-total</span>
                    <b id="sum-base">0,00 MAD</b>
                </div>
                <div class="sum-line">
                    <span>Remise promo</span>
                    <b id="sum-remise">—</b>
                </div>
                <div class="sum-total">
                    <span>Total</span>
                    <span class="amount" id="sum-total">0,00 MAD</span>
                </div>
                <input type="hidden" name="montant_total" id="montant_total" value="0">
            </div>
        </div>
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Règles</h3>
                </div>
            </div>
            <div class="card-body" style="font-size:var(--text-sm);color:var(--ink-soft)">
                <p>Le montant est calculé sur la base du prix de la chambre, des nuits et de la remise
                promotionnelle. La facturation et le paiement se traitent depuis le dossier de réservation.</p>
            </div>
        </div>
    </aside>
</form>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const chambre  = document.getElementById("id_chambre");
    const promo    = document.getElementById("id_promotion");
    const arrivee  = document.getElementById("date_arrivee");
    const depart   = document.getElementById("date_depart");

    const out = {
        nuits:  document.getElementById("sum-nuits"),
        prix:   document.getElementById("sum-prix"),
        nb:     document.getElementById("sum-nb"),
        base:   document.getElementById("sum-base"),
        remise: document.getElementById("sum-remise"),
        total:  document.getElementById("sum-total"),
        hidden: document.getElementById("montant_total"),
    };

    const fmt = (n) => n.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD";

    function recalc() {
        const opt = chambre.options[chambre.selectedIndex];
        const prix = opt && opt.dataset.prix ? parseFloat(opt.dataset.prix) : 0;

        const d1 = arrivee.value ? new Date(arrivee.value) : null;
        const d2 = depart.value ? new Date(depart.value) : null;
        let nuits = 0;
        if (d1 && d2) {
            nuits = Math.max(0, Math.round((d2 - d1) / 86400000));
        }

        const base = prix * nuits;

        let pct = 0;
        const popt = promo.options[promo.selectedIndex];
        if (popt && popt.dataset.remise) { pct = parseFloat(popt.dataset.remise); }

        let total = base;
        if (pct > 0) { total = base * (1 - pct / 100); }

        out.nuits.textContent = (nuits > 1 ? nuits + " nuits" : nuits + " nuit");
        out.prix.textContent  = fmt(prix);
        out.nb.textContent    = String(nuits);
        out.base.textContent  = fmt(base);
        out.remise.textContent = pct > 0 ? "−" + pct.toLocaleString("fr-FR") + " %" : "—";
        out.total.textContent = fmt(total);
        out.hidden.value      = total.toFixed(2);
    }

    [chambre, promo, arrivee, depart].forEach((el) => el.addEventListener("change", recalc));
    recalc();
});
</script>
<?php require __DIR__ . "/includes/footer.php"; ?>