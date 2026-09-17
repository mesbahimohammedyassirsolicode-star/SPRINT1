<?php
/**
 * LuxeStay Grand Riviera & Spa
 * Edit Reservation (Matching Stitch Luxury Theme)
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$errors = [];

$id_reservation = (int)($_GET['id'] ?? 0);
if ($id_reservation <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch existing
$stmt = $pdo->prepare("SELECT * FROM reservation WHERE id_reservation = ?");
$stmt->execute([$id_reservation]);
$reservation = $stmt->fetch();

if (!$reservation) {
    die("Reservation not found.");
}

// Update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_client = (int)($_POST['id_client'] ?? 0);
    $id_chambre = (int)($_POST['id_chambre'] ?? 0);
    $id_promotion = !empty($_POST['id_promotion']) ? (int)$_POST['id_promotion'] : null;
    $date_arrivee = trim($_POST['date_arrivee'] ?? '');
    $date_depart = trim($_POST['date_depart'] ?? '');
    $nb_adultes = (int)($_POST['nb_adultes'] ?? 1);
    $nb_enfants = (int)($_POST['nb_enfants'] ?? 0);
    $statut_reservation = $_POST['statut_reservation'] ?? 'Confirmée';
    $source_reservation = $_POST['source_reservation'] ?? 'Site web';
    $montant_total = (float)($_POST['montant_total'] ?? 0);

    if ($id_client <= 0) $errors[] = "Please select a client.";
    if ($id_chambre <= 0) $errors[] = "Please select a room.";
    if (empty($date_arrivee) || empty($date_depart)) $errors[] = "Dates are required.";
    if ($date_depart <= $date_arrivee) $errors[] = "Check-out must be after check-in.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE reservation SET 
                    id_client = ?, id_chambre = ?, id_promotion = ?, 
                    date_arrivee = ?, date_depart = ?, nb_adultes = ?, nb_enfants = ?, 
                    statut_reservation = ?, source_reservation = ?, montant_total = ?
                WHERE id_reservation = ?
            ");
            $stmt->execute([
                $id_client, $id_chambre, $id_promotion,
                $date_arrivee, $date_depart, $nb_adultes, $nb_enfants,
                $statut_reservation, $source_reservation, $montant_total,
                $id_reservation
            ]);

            header("Location: index.php?msg=updated");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Data sources
$clients = $pdo->query("SELECT id_client, nom, prenom, email, statut_fidelite FROM client ORDER BY nom ASC")->fetchAll();
$rooms = $pdo->query("
    SELECT ch.id_chambre, ch.numero_chambre, ch.prix_nuit, ch.vue, h.nom_hotel, th.libelle AS type_libelle
    FROM chambre ch
    JOIN hotel h ON ch.id_hotel = h.id_hotel
    JOIN type_hebergement th ON ch.id_type = th.id_type
    ORDER BY ch.prix_nuit DESC
")->fetchAll();
$promotions = $pdo->query("SELECT id_promotion, code_promo, pourcentage_reduction FROM promotion WHERE actif = 1")->fetchAll();

$pageTitle = "Edit Folio #LX-{$id_reservation} - LuxeStay";
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
?>

<div class="pl-64 flex flex-col min-h-screen">
    <?php require_once __DIR__ . '/../views/partials/topbar.php'; ?>

    <main class="mt-16 p-4 xl:p-8 space-y-6 flex-1 max-w-4xl mx-auto w-full">

        <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 shadow-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/30 flex items-center justify-center text-primary font-bold">
                    #<?= $id_reservation ?>
                </div>
                <div>
                    <h1 class="font-serif text-xl font-bold text-on-surface">Edit Reservation Folio</h1>
                    <span class="text-[11px] text-secondary">Modify dates, room tier, or stay status in live database</span>
                </div>
            </div>
            <a href="index.php" class="text-xs text-on-surface-variant hover:text-on-surface font-semibold no-underline">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="p-3 rounded-xl bg-error/15 border border-error/40 text-error text-xs">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="reservation_edit.php?id=<?= $id_reservation ?>" class="space-y-6">
            <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-6 shadow-2xl space-y-4 text-xs">
                
                <!-- Guest Select -->
                <div class="space-y-1.5">
                    <label class="font-bold text-secondary uppercase text-[10px]">Registered Guest Profile *</label>
                    <select name="id_client" class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface font-semibold focus:outline-none focus:border-primary">
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id_client'] ?>" <?= ($reservation['id_client'] == $c['id_client']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom'] . ' (' . $c['email'] . ') — ' . $c['statut_fidelite']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Room Select -->
                <div class="space-y-1.5">
                    <label class="font-bold text-secondary uppercase text-[10px]">Assigned Suite / Room *</label>
                    <select name="id_chambre" id="id_chambre" class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface font-semibold focus:outline-none focus:border-primary">
                        <?php foreach ($rooms as $rm): ?>
                            <option value="<?= $rm['id_chambre'] ?>" 
                                    data-price="<?= $rm['prix_nuit'] ?>"
                                    <?= ($reservation['id_chambre'] == $rm['id_chambre']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rm['nom_hotel'] . ' — Suite ' . $rm['numero_chambre'] . ' (' . $rm['type_libelle'] . ', ' . $rm['vue'] . ') — ' . $rm['prix_nuit'] . ' MAD/night') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-bold text-secondary uppercase text-[10px]">Check-In Date *</label>
                        <input type="date" name="date_arrivee" id="date_arrivee" 
                               value="<?= htmlspecialchars($reservation['date_arrivee']) ?>" 
                               class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface focus:outline-none focus:border-primary" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-bold text-secondary uppercase text-[10px]">Check-Out Date *</label>
                        <input type="date" name="date_depart" id="date_depart" 
                               value="<?= htmlspecialchars($reservation['date_depart']) ?>" 
                               class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface focus:outline-none focus:border-primary" required>
                    </div>
                </div>

                <!-- Guests & Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-bold text-secondary uppercase text-[10px]">Adults *</label>
                        <input type="number" name="nb_adultes" min="1" max="10" 
                               value="<?= htmlspecialchars((string)$reservation['nb_adultes']) ?>" 
                               class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface focus:outline-none" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-bold text-secondary uppercase text-[10px]">Children</label>
                        <input type="number" name="nb_enfants" min="0" max="10" 
                               value="<?= htmlspecialchars((string)$reservation['nb_enfants']) ?>" 
                               class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface focus:outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-bold text-secondary uppercase text-[10px]">Reservation Status</label>
                        <select name="statut_reservation" class="w-full p-2.5 rounded-xl bg-surface-container border border-primary/40 text-primary font-bold focus:outline-none">
                            <?php foreach (['En attente', 'Confirmée', 'Enregistrée', 'Terminée', 'Annulée'] as $st): ?>
                                <option value="<?= $st ?>" <?= ($reservation['statut_reservation'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Promo & Source -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-bold text-secondary uppercase text-[10px]">Promo Code</label>
                        <select name="id_promotion" id="id_promotion" class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface focus:outline-none">
                            <option value="" data-discount="0">None</option>
                            <?php foreach ($promotions as $promo): ?>
                                <option value="<?= $promo['id_promotion'] ?>" 
                                        data-discount="<?= $promo['pourcentage_reduction'] ?>"
                                        <?= ($reservation['id_promotion'] == $promo['id_promotion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($promo['code_promo'] . ' (-' . $promo['pourcentage_reduction'] . '%)') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-bold text-secondary uppercase text-[10px]">Booking Channel</label>
                        <select name="source_reservation" class="w-full p-2.5 rounded-xl bg-surface-container border border-outline-variant/40 text-on-surface focus:outline-none">
                            <?php foreach (['Site web', 'Téléphone', 'Agence', 'Sur place'] as $src): ?>
                                <option value="<?= $src ?>" <?= ($reservation['source_reservation'] === $src) ? 'selected' : '' ?>><?= $src ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="montant_total" id="montant_total" value="<?= htmlspecialchars((string)$reservation['montant_total']) ?>">

                <!-- Summary Total Preview -->
                <div class="p-4 rounded-xl bg-surface-container border border-primary/30 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-outline uppercase block">Recalculated Folio Total</span>
                        <span id="calculated-nights" class="text-xs text-secondary font-mono">Calculated stay</span>
                    </div>
                    <div id="calculated-price" class="font-serif text-2xl font-bold text-primary">
                        <?= number_format((float)$reservation['montant_total'], 2) ?> MAD
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <a href="index.php" class="px-4 py-2.5 rounded-xl border border-outline-variant/40 text-on-surface-variant hover:text-on-surface text-xs font-semibold no-underline">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary-container via-primary to-primary-fixed-dim text-on-primary-fixed font-bold text-xs shadow-lg hover:opacity-95">
                        Update &amp; Save Changes
                    </button>
                </div>

            </div>
        </form>

    </main>

    <?php require_once __DIR__ . '/../views/partials/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const arrivalInput = document.getElementById('date_arrivee');
    const departureInput = document.getElementById('date_depart');
    const roomSelect = document.getElementById('id_chambre');
    const promoSelect = document.getElementById('id_promotion');
    const nightsSpan = document.getElementById('calculated-nights');
    const priceDisplay = document.getElementById('calculated-price');
    const totalInput = document.getElementById('montant_total');

function update() {
            const d1 = new Date(arrivalInput.value);
            const d2 = new Date(departureInput.value);
            const diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));

            if (diff > 0) {
                nightsSpan.textContent = `${diff} nights stay`;
                const price = parseFloat(roomSelect.options[roomSelect.selectedIndex].dataset.price || 0);
                const baseRoomTotal = diff * price;
                const surcharge = baseRoomTotal * 0.05;
                const helicopter = 1200.00;
                const levy = 180.00;

                let total = baseRoomTotal + surcharge + helicopter + levy;

                if (promoSelect && promoSelect.selectedIndex > 0) {
                    const discount = parseFloat(promoSelect.options[promoSelect.selectedIndex].dataset.discount || 0);
                    if (discount > 0) {
                        total = total * ((100 - discount) / 100);
                    }
                }

                priceDisplay.textContent = `${total.toFixed(2)} MAD`;
                totalInput.value = total.toFixed(2);
            }
        }

    arrivalInput.addEventListener('change', update);
    departureInput.addEventListener('change', update);
    roomSelect.addEventListener('change', update);
    if (promoSelect) promoSelect.addEventListener('change', update);
});
</script>