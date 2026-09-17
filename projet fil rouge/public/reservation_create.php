<?php
/**
 * LuxeStay Grand Riviera & Spa
 * Create New Reservation (Matching Stitch Mockup #2 - VIP Priority Flow)
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$errors = [];

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_client = (int)($_POST['id_client'] ?? 0);
    $id_chambre = (int)($_POST['id_chambre'] ?? 0);
    $id_promotion = !empty($_POST['id_promotion']) ? (int)$_POST['id_promotion'] : null;
    $date_arrivee = trim($_POST['date_arrivee'] ?? '');
    $date_depart = trim($_POST['date_depart'] ?? '');
    $nb_adultes = (int)($_POST['nb_adultes'] ?? 2);
    $nb_enfants = (int)($_POST['nb_enfants'] ?? 0);
    $statut_reservation = $_POST['statut_reservation'] ?? 'Confirmée';
    $source_reservation = $_POST['source_reservation'] ?? 'Site web';
    $montant_total = (float)($_POST['montant_total'] ?? 0);

    if ($id_client <= 0) $errors[] = "Please select a registered guest profile.";
    if ($id_chambre <= 0) $errors[] = "Please select a suite or residence.";
    if (empty($date_arrivee) || empty($date_depart)) $errors[] = "Stay dates are required.";
    if ($date_depart <= $date_arrivee) $errors[] = "Check-out date must be after check-in.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reservation (
                    id_client, id_chambre, id_promotion, 
                    date_arrivee, date_depart, nb_adultes, nb_enfants, 
                    statut_reservation, source_reservation, montant_total
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_client, $id_chambre, $id_promotion,
                $date_arrivee, $date_depart, $nb_adultes, $nb_enfants,
                $statut_reservation, $source_reservation, $montant_total
            ]);

            header("Location: index.php?msg=created");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch clients
$clients = $pdo->query("SELECT id_client, nom, prenom, email, telephone, statut_fidelite FROM client ORDER BY nom ASC")->fetchAll();

// Fetch suites & residences
$rooms = $pdo->query("
    SELECT 
        ch.id_chambre, 
        ch.numero_chambre, 
        ch.prix_nuit, 
        ch.vue, 
        ch.etage,
        ch.statut_chambre,
        h.nom_hotel, 
        th.libelle AS type_libelle,
        th.superficie_m2
    FROM chambre ch
    JOIN hotel h ON ch.id_hotel = h.id_hotel
    JOIN type_hebergement th ON ch.id_type = th.id_type
    ORDER BY ch.prix_nuit DESC
")->fetchAll();

// Active promotions
$promotions = $pdo->query("SELECT id_promotion, code_promo, pourcentage_reduction FROM promotion WHERE actif = 1")->fetchAll();

$pageTitle = "Create New Reservation - LuxeStay";
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
?>

<div class="pl-64 flex flex-col min-h-screen">
    <?php require_once __DIR__ . '/../views/partials/topbar.php'; ?>

    <main class="mt-16 p-4 xl:p-8 space-y-6 flex-1 max-w-6xl mx-auto w-full">

        <!-- Top Header Breadcrumb & Controls -->
        <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 shadow-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/30 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">star</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-serif text-xl font-bold text-on-surface">Create New Reservation</h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/20 text-primary border border-primary/30 uppercase tracking-wider">New Booking</span>
                    </div>
                    <span class="text-[11px] text-secondary font-medium">LuxeStay Grand Riviera &amp; Spa</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="index.php" class="p-1.5 rounded-lg border border-outline-variant/40 hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors" title="Cancel">
                    <span class="material-symbols-outlined text-sm">close</span>
                </a>
            </div>
        </div>

        <!-- Progress Multi-Step Bar -->
        <div class="grid grid-cols-4 gap-3 text-xs">
            <div class="p-3 bg-surface-container-low border border-tertiary/40 rounded-xl flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-full bg-tertiary/20 text-tertiary flex items-center justify-center font-bold text-[10px]">✓</div>
                <div>
                    <div class="text-[9px] text-outline uppercase font-bold">Step 01 • Verified</div>
                    <div class="font-semibold text-on-surface text-[11px]">Guest Profile &amp; VIP Status</div>
                </div>
            </div>

            <div class="p-3 bg-surface-container-low border border-primary rounded-xl flex items-center gap-2.5 shadow-lg">
                <div class="w-6 h-6 rounded-full bg-primary text-on-primary-fixed flex items-center justify-center font-bold text-[10px]">2</div>
                <div>
                    <div class="text-[9px] text-primary uppercase font-bold">Step 02 • Active</div>
                    <div class="font-semibold text-on-surface text-[11px]">Room &amp; Suite Selection</div>
                </div>
            </div>

            <div class="p-3 bg-surface-container-low border border-outline-variant/20 rounded-xl flex items-center gap-2.5 opacity-60">
                <div class="w-6 h-6 rounded-full bg-surface-container-highest text-outline flex items-center justify-center font-bold text-[10px]">3</div>
                <div>
                    <div class="text-[9px] text-outline uppercase font-bold">Step 03</div>
                    <div class="font-semibold text-on-surface text-[11px]">Curated Preferences</div>
                </div>
            </div>

            <div class="p-3 bg-surface-container-low border border-outline-variant/20 rounded-xl flex items-center gap-2.5 opacity-60">
                <div class="w-6 h-6 rounded-full bg-surface-container-highest text-outline flex items-center justify-center font-bold text-[10px]">4</div>
                <div>
                    <div class="text-[9px] text-outline uppercase font-bold">Step 04</div>
                    <div class="font-semibold text-on-surface text-[11px]">Payment &amp; Guarantee</div>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="p-3 rounded-xl bg-error/15 border border-error/40 text-error text-xs">
                <strong>Please resolve the following:</strong>
                <ul class="list-disc pl-5 mt-1">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form Body -->
        <form method="POST" action="reservation_create.php" id="reservationForm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- Left Column (8 cols): Guest, Dates, Suites Selection -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- Guest Profile Dossier Card -->
                    <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 shadow-xl">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-surface-container-high border border-primary/40 flex items-center justify-center text-primary font-bold text-sm">
                                    VIP
                                </div>
                                <div>
                                    <label class="text-[10px] text-secondary font-bold uppercase tracking-wider block">Assigned Guest Profile *</label>
                                    <select name="id_client" id="id_client" class="bg-transparent text-sm font-semibold text-on-surface border-b border-primary/50 focus:outline-none focus:border-primary py-1 pr-6" required>
                                        <?php foreach ($clients as $c): ?>
                                            <option value="<?= $c['id_client'] ?>" data-tier="<?= htmlspecialchars($c['statut_fidelite']) ?>" class="bg-surface-container text-on-surface">
                                                <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom'] . ' (' . $c['email'] . ') — ' . $c['statut_fidelite'] . ' Tier') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span id="client-tier-chip" class="px-2.5 py-1 bg-primary/10 text-primary border border-primary/30 rounded-lg text-xs font-bold"><?= htmlspecialchars($clients[0]['statut_fidelite'] ?? 'Standard') ?> Tier</span>
                                <a href="clients.php" class="px-2.5 py-1 rounded-lg border border-outline-variant/40 hover:bg-surface-container text-outline text-xs no-underline font-medium">Guest Dossier</a>
                            </div>
                        </div>
                    </div>

                    <!-- Stay Parameters & Booking Horizon -->
                    <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-5 shadow-xl space-y-4">
                        <h3 class="text-xs font-serif font-bold text-secondary uppercase tracking-widest flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-primary">calendar_month</span>
                            Stay Parameters &amp; Booking Horizon
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <!-- Check-in -->
                            <div class="p-3 bg-surface-container rounded-xl border border-outline-variant/30 space-y-1">
                                <label class="text-[10px] font-bold text-secondary uppercase block">Check-In *</label>
                                <input type="date" name="date_arrivee" id="date_arrivee" 
                                       class="w-full bg-transparent text-xs font-semibold text-on-surface focus:outline-none" 
                                       value="<?= date('Y-m-d') ?>" required>
                                <span class="text-[9px] text-tertiary block font-mono">15:00 Guaranteed</span>
                            </div>

                            <!-- Check-out -->
                            <div class="p-3 bg-surface-container rounded-xl border border-outline-variant/30 space-y-1">
                                <label class="text-[10px] font-bold text-secondary uppercase block">Check-Out *</label>
                                <input type="date" name="date_depart" id="date_depart" 
                                       class="w-full bg-transparent text-xs font-semibold text-on-surface focus:outline-none" 
                                       value="<?= date('Y-m-d', strtotime('+4 days')) ?>" required>
                                <span class="text-[9px] text-secondary block font-mono">Late Checkout 14:00</span>
                            </div>

                            <!-- Guests -->
                            <div class="p-3 bg-surface-container rounded-xl border border-outline-variant/30 space-y-1">
                                <label class="text-[10px] font-bold text-secondary uppercase block">Guests &amp; Suites</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="nb_adultes" id="nb_adultes" min="1" max="6" value="2" 
                                           class="w-12 bg-transparent text-xs font-bold text-on-surface focus:outline-none border-b border-outline-variant">
                                    <span class="text-xs text-outline">Ad.</span>
                                    <input type="number" name="nb_enfants" id="nb_enfants" min="0" max="6" value="0" 
                                           class="w-12 bg-transparent text-xs font-bold text-on-surface focus:outline-none border-b border-outline-variant">
                                    <span class="text-xs text-outline">Ch.</span>
                                </div>
                                <span class="text-[9px] text-outline block">Max 6 Guests per Suite</span>
                            </div>

                            <!-- Channel / Rate -->
                            <div class="p-3 bg-surface-container rounded-xl border border-outline-variant/30 space-y-1">
                                <label class="text-[10px] font-bold text-secondary uppercase block">Rate &amp; Channel</label>
                                <select name="source_reservation" class="w-full bg-transparent text-xs font-semibold text-primary focus:outline-none">
                                    <option value="Site web" class="bg-surface-container">Palace Signature (Direct)</option>
                                    <option value="Téléphone" class="bg-surface-container">Concierge Telephone</option>
                                    <option value="Sur place" class="bg-surface-container">Front Desk Walk-In</option>
                                    <option value="Agence" class="bg-surface-container">Luxury Travel Advisor</option>
                                </select>
                                <span class="text-[9px] text-primary block font-mono">Rate confirmed at Front Desk</span>
                            </div>
                        </div>
                    </div>

                    <!-- Curated Suites & Residences Available Selection -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-serif text-base font-bold text-on-surface">Curated Suites &amp; Residences Available</h3>
                            <span class="text-[11px] text-secondary font-mono"><?= count($rooms) ?> Suites Live in Inventory</span>
                        </div>

                        <!-- Suite Cards List with Radio Selection -->
                        <div class="space-y-3">
                            <?php foreach ($rooms as $idx => $rm): ?>
                                <label class="block cursor-pointer">
                                    <input type="radio" name="id_chambre" value="<?= $rm['id_chambre'] ?>" 
                                           data-price="<?= $rm['prix_nuit'] ?>"
                                           class="hidden peer room-radio" 
                                           <?= (isset($_GET['room']) && (int)$_GET['room'] === (int)$rm['id_chambre']) ? 'checked' : (($idx === 0) ? 'checked' : '') ?> required>
                                    <div class="bg-surface-container-low border border-outline-variant/30 peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary rounded-2xl p-4 shadow-xl transition-all relative flex flex-col md:flex-row items-center gap-4 hover:border-primary/50">
                                        <!-- Suite Badge Image placeholder with luxury tone -->
                                        <div class="w-full md:w-44 h-28 rounded-xl bg-gradient-to-tr from-surface-container-lowest to-surface-container-high border border-outline-variant/30 p-2 flex flex-col justify-between flex-shrink-0 relative overflow-hidden">
                                            <span class="text-[10px] text-secondary uppercase font-bold tracking-wider">Floor <?= $rm['etage'] ?> • <?= $rm['superficie_m2'] ?> m²</span>
                                            <div class="text-xs font-bold text-on-surface">Panoramic <?= htmlspecialchars($rm['vue']) ?></div>
                                        </div>

                                        <div class="flex-1 min-w-0 space-y-1.5 w-full">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-serif text-base font-bold text-on-surface"><?= htmlspecialchars($rm['type_libelle']) ?></h4>
                                                    <span class="text-xs text-outline font-mono">(Suite <?= htmlspecialchars($rm['numero_chambre']) ?>)</span>
                                                </div>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($rm['statut_chambre'] === 'Disponible') ? 'bg-tertiary/10 text-tertiary border border-tertiary/25' : (($rm['statut_chambre'] === 'Occupée') ? 'bg-primary/10 text-primary border border-primary/25' : 'bg-secondary/10 text-secondary border border-secondary/25') ?>"><?= strtoupper(htmlspecialchars($rm['statut_chambre'])) ?></span>
                                            </div>

                                            <p class="text-xs text-on-surface-variant truncate">Top-tier residence at <?= htmlspecialchars($rm['nom_hotel']) ?> with dedicated butler services.</p>

                                            <div class="flex items-center gap-4 text-[11px] text-secondary pt-1 flex-wrap">
                                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">floor</span> Floor <?= $rm['etage'] ?></span>
                                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">straighten</span> <?= $rm['superficie_m2'] ?> m²</span>
                                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">landscape</span> <?= htmlspecialchars($rm['vue']) ?> View</span>
                                            </div>

                                            <div class="pt-2 text-primary font-serif font-bold text-base">
                                                <?= number_format((float)$rm['prix_nuit'], 2) ?> MAD <span class="text-xs text-outline font-normal font-sans">/ night • Excl. Riviera Reserve Levy</span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Column (4 cols): Folio Estimate & Guarantee Method -->
                <div class="lg:col-span-4 space-y-4">
                    
                    <!-- Folio Estimate Card -->
                    <div class="bg-surface-container-low border border-primary-container/30 rounded-2xl p-5 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/30">
                            <div>
                                <h3 class="font-serif text-base font-bold text-on-surface">Folio Estimate</h3>
                                <span class="text-[10px] text-outline font-mono">Real-Time Currency: MAD</span>
                            </div>
                            <span id="calculated-nights" class="px-2 py-0.5 rounded bg-surface-container text-secondary text-[10px] font-mono border border-outline-variant/30 font-bold">4 Nights Total</span>
                        </div>

                        <!-- Line Items -->
                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between text-on-surface-variant">
                                <span>Suite Accommodation</span>
                                <span id="line-room-rate" class="font-mono text-on-surface font-semibold">0.00 MAD</span>
                            </div>
                            <div class="flex items-center justify-between text-on-surface-variant">
                                <span>Resort &amp; Reserve Surcharge (5%)</span>
                                <span id="line-surcharge" class="font-mono text-on-surface font-semibold">0.00 MAD</span>
                            </div>
                            <div class="flex items-center justify-between text-on-surface-variant">
                                <span>Helicopter Transfer (NCE &harr; LuxeStay)</span>
                                <span class="font-mono text-on-surface font-semibold">1,200.00 MAD</span>
                            </div>
                            <div class="flex items-center justify-between text-on-surface-variant">
                                <span>Tourism &amp; City Municipal Levy</span>
                                <span class="font-mono text-on-surface font-semibold">180.00 MAD</span>
                            </div>

                            <!-- Promo Selection -->
                            <div class="pt-2 border-t border-outline-variant/20 flex items-center justify-between">
                                <span class="text-secondary text-[11px]">VIP Promo Privilege</span>
                                <select name="id_promotion" id="id_promotion" class="bg-surface-container text-xs text-primary border border-primary/30 rounded px-2 py-1 focus:outline-none">
                                    <option value="" data-discount="0">None</option>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>" data-discount="<?= $promo['pourcentage_reduction'] ?>">
                                            <?= htmlspecialchars($promo['code_promo'] . ' (-' . $promo['pourcentage_reduction'] . '%)') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Grand Total Banner -->
                        <div class="pt-4 border-t border-outline-variant/30 text-right">
                            <span class="text-[10px] text-outline uppercase block tracking-wider">Estimated Stay Folio</span>
                            <div id="calculated-price" class="font-serif text-3xl font-bold text-primary my-1">0.00 MAD</div>
                            <span class="text-[10px] text-outline block">Includes all VAT &amp; mandatory charges</span>
                            <input type="hidden" name="montant_total" id="montant_total" value="0.00">
                            <input type="hidden" name="statut_reservation" id="statut_reservation_input" value="Confirmée">
                        </div>

                        <!-- Deposit & Guarantee Method -->
                        <div class="pt-4 border-t border-outline-variant/30 space-y-2">
                            <span class="text-[10px] font-bold text-secondary uppercase tracking-wider block">Guarantee &amp; Payment</span>
                            <div class="p-2.5 rounded-xl bg-surface-container border border-outline-variant/30 flex items-center justify-between text-xs">
                                <span class="flex items-center gap-2 text-on-surface font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-secondary"></span>
                                    Payment method collected at check-in
                                </span>
                                <span class="text-[10px] text-secondary font-bold">Pending</span>
                            </div>
                        </div>

                        <!-- Pre-Auth Amount -->
                        <div class="p-3 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[9px] text-outline uppercase block">Garantie de réservation</span>
                                <span class="font-mono text-tertiary font-bold">Guarantee needed at check-in</span>
                            </div>
                            <span class="px-2 py-1 rounded bg-secondary/10 text-secondary text-[10px] font-bold font-mono">Pending</span>
                        </div>
                    </div>

                    <!-- Palace Reservation Policies Card -->
                    <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 shadow-xl space-y-2 text-xs">
                        <div class="flex items-center gap-2 text-secondary font-semibold font-serif">
                            <span class="material-symbols-outlined text-sm">verified_user</span>
                            <span>Palace Reservation Policies</span>
                        </div>
                        <p class="text-[11px] text-outline leading-relaxed">
                            Penalty-free cancellation permitted up to 72 hours prior to arrival. VIP Tier 1 guests enjoy 24/7 dedicated concierge protection.
                        </p>
                    </div>

                </div>

            </div>

            <!-- Sticky Bottom Action Dock matching Stitch Mockup -->
            <div class="sticky bottom-4 z-40 bg-surface-container-low/95 backdrop-blur-md border border-outline-variant/40 rounded-2xl p-3 shadow-2xl flex flex-wrap items-center justify-between gap-3 mt-8">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="px-3 py-2 rounded-xl border border-outline-variant/40 hover:bg-surface-container text-error text-xs font-semibold flex items-center gap-1.5 no-underline transition-colors">
                        <span class="material-symbols-outlined text-sm">delete</span>
                        <span>Cancel &amp; Discard Draft</span>
                    </a>
                    <button type="button" id="holdButton" class="px-3 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-xs font-semibold flex items-center gap-1.5 transition-colors">
                        <span class="material-symbols-outlined text-sm">lock_clock</span>
                        <span>Save as Unconfirmed Hold</span>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <a href="index.php" class="px-4 py-2 text-xs text-on-surface-variant hover:text-on-surface font-semibold no-underline">
                        &larr; Back to Front Desk
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary-container via-primary to-primary-fixed-dim text-on-primary-fixed font-bold text-xs shadow-lg hover:opacity-95 transition-all flex items-center gap-2">
                        <span>Continue to Payment &amp; Folio Guarantee</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
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
    const roomRadios = document.querySelectorAll('.room-radio');
    const promoSelect = document.getElementById('id_promotion');
    const nightsSpan = document.getElementById('calculated-nights');
    const priceDisplay = document.getElementById('calculated-price');
    const lineRoomRate = document.getElementById('line-room-rate');
    const lineSurcharge = document.getElementById('line-surcharge');
    const totalAmountInput = document.getElementById('montant_total');

    function updateCalculations() {
        if (!arrivalInput || !departureInput) return;
        const d1 = new Date(arrivalInput.value);
        const d2 = new Date(departureInput.value);
        const diffTime = d2 - d1;
        const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (nights > 0) {
            nightsSpan.textContent = `${nights} Night${nights > 1 ? 's' : ''} Total`;
            
            let nightlyPrice = 0;
            roomRadios.forEach(radio => {
                if (radio.checked) {
                    nightlyPrice = parseFloat(radio.dataset.price || 0);
                }
            });

            const baseRoomTotal = nights * nightlyPrice;
            const surcharge = baseRoomTotal * 0.05; // 5% resort fee
            const helicopter = 1200.00;
            const levy = 180.00;

            let subtotal = baseRoomTotal + surcharge + helicopter + levy;

            // Promo discount
            if (promoSelect && promoSelect.selectedIndex > 0) {
                const discount = parseFloat(promoSelect.options[promoSelect.selectedIndex].dataset.discount || 0);
                if (discount > 0) {
                    subtotal = subtotal * ((100 - discount) / 100);
                }
            }

            if (lineRoomRate) lineRoomRate.textContent = `${baseRoomTotal.toFixed(2)} MAD`;
            if (lineSurcharge) lineSurcharge.textContent = `${surcharge.toFixed(2)} MAD`;
            if (priceDisplay) priceDisplay.textContent = `${subtotal.toFixed(2)} MAD`;
            if (totalAmountInput) totalAmountInput.value = subtotal.toFixed(2);
        } else {
            nightsSpan.textContent = 'Invalid dates';
        }
    }

    arrivalInput.addEventListener('change', updateCalculations);
    departureInput.addEventListener('change', updateCalculations);
    roomRadios.forEach(r => r.addEventListener('change', updateCalculations));
    if (promoSelect) promoSelect.addEventListener('change', updateCalculations);

    const clientSelect = document.getElementById('id_client');
    const tierChip = document.getElementById('client-tier-chip');
    if (clientSelect && tierChip) {
        const syncTier = () => {
            const opt = clientSelect.options[clientSelect.selectedIndex];
            tierChip.textContent = (opt && opt.dataset.tier ? opt.dataset.tier : 'Standard') + ' Tier';
        };
        clientSelect.addEventListener('change', syncTier);
        syncTier();
    }

    const form = document.getElementById('reservationForm');
    const statusInput = document.getElementById('statut_reservation_input');
    const holdButton = document.getElementById('holdButton');
    if (holdButton && statusInput && form) {
        holdButton.addEventListener('click', () => {
            statusInput.value = 'En attente';
            form.submit();
        });
    }

    updateCalculations();
});
</script>