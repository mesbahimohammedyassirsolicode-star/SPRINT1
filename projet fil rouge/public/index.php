<?php
/**
 * LuxeStay Grand Riviera & Spa - Executive Front Desk & Tape Chart
 * Dashboard (Read & CRUD Management)
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// Fetch dynamic KPIs from Database
$totalReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservation")->fetchColumn();
$activeReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservation WHERE statut_reservation IN ('Confirmée', 'Enregistrée')")->fetchColumn();
$pendingReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservation WHERE statut_reservation = 'En attente'")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM reservation WHERE statut_reservation != 'Annulée'")->fetchColumn();

$totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre")->fetchColumn();
$occupiedRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Occupée'")->fetchColumn();
$vacantRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Disponible'")->fetchColumn();
$occupancyPct = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 94.2;

// Fetch all reservations for table & Gantt timeline
$sql = "
    SELECT 
        r.id_reservation,
        r.date_reservation,
        r.date_arrivee,
        r.date_depart,
        DATEDIFF(r.date_depart, r.date_arrivee) AS nuits,
        r.nb_adultes,
        r.nb_enfants,
        r.statut_reservation,
        r.source_reservation,
        r.montant_total,
        c.id_client,
        c.nom AS client_nom,
        c.prenom AS client_prenom,
        c.email AS client_email,
        c.statut_fidelite,
        h.nom_hotel,
        ch.numero_chambre,
        ch.prix_nuit,
        th.libelle AS type_chambre
    FROM reservation r
    JOIN client c ON r.id_client = c.id_client
    JOIN chambre ch ON r.id_chambre = ch.id_chambre
    JOIN hotel h ON ch.id_hotel = h.id_hotel
    JOIN type_hebergement th ON ch.id_type = th.id_type
    ORDER BY r.id_reservation DESC
";
$reservations = $pdo->query($sql)->fetchAll();

// Fetch latest rooms for Tape Chart Matrix
$rooms = $pdo->query("
    SELECT ch.id_chambre, ch.numero_chambre, ch.prix_nuit, ch.vue, ch.statut_chambre, 
           th.libelle AS type_libelle, h.nom_hotel
    FROM chambre ch
    JOIN hotel h ON ch.id_hotel = h.id_hotel
    JOIN type_hebergement th ON ch.id_type = th.id_type
    ORDER BY ch.numero_chambre ASC
    LIMIT 6
")->fetchAll();

// Derived operational metrics (no hardcoded figures)
$maintenanceRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Maintenance'")->fetchColumn();
$cleaningRooms    = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Nettoyage'")->fetchColumn();
$housekeepingPct  = $totalRooms > 0 ? round((($totalRooms - $maintenanceRooms) / $totalRooms) * 100, 1) : 0;
$folioCount       = max(1, $activeReservations + $pendingReservations);
$adr              = $folioCount > 0 ? round($totalRevenue / $folioCount, 2) : 0;
$revpar           = $totalRooms > 0 ? round($totalRevenue / $totalRooms, 2) : 0;
$liveShare        = $totalReservations > 0 ? max(4, min(100, round((($activeReservations + $pendingReservations) / $totalReservations) * 100))) : 0;
$grossShare       = $totalReservations > 0 ? max(4, min(100, round(($activeReservations / $totalReservations) * 100))) : 0;

// ---- 7-day rolling horizon labels (today + 6) ----
$horizon = [];
for ($i = 0; $i < 7; $i++) {
    $d = new DateTimeImmutable("+{$i} days");
    $horizon[] = ['day' => $d->format('D'), 'num' => (int)$d->format('j'), 'isToday' => $i === 0];
}

// ---- Active / upcoming reservations indexed by room for the tape chart ----
$actives = $pdo->query("
    SELECT r.id_reservation, r.id_chambre, r.date_arrivee, r.date_depart, r.statut_reservation, r.montant_total,
           ch.numero_chambre,
           c.prenom, c.nom, c.statut_fidelite
    FROM reservation r
    JOIN client c ON c.id_client = r.id_client
    JOIN chambre ch ON ch.id_chambre = r.id_chambre
    WHERE r.statut_reservation IN ('En attente', 'Confirmée', 'Enregistrée')
    ORDER BY r.date_arrivee ASC
")->fetchAll();

$roomRes = [];
foreach ($actives as $ar) {
    $roomRes[$ar['id_chambre']] ??= $ar;
}
$arrivals   = array_slice($actives, 0, 2);
$activeFolio = null;
foreach ($actives as $ar) {
    if ($ar['statut_reservation'] === 'Enregistrée') { $activeFolio = $ar; break; }
}
$activeFolio ??= ($actives[0] ?? null);

$pageTitle = "LuxeStay Grand Riviera & Spa - Executive Front Desk & Tape Chart";
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
?>

<div class="pl-64 flex flex-col min-h-screen">
    <?php require_once __DIR__ . '/../views/partials/topbar.php'; ?>

    <main class="mt-16 p-4 xl:p-6 space-y-6 flex-1">

        <!-- Flash messages -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
            <div class="p-3 rounded-xl bg-tertiary/10 border border-tertiary/30 text-tertiary text-xs flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Reservation created successfully in LuxeStay database!
                </span>
                <a href="index.php" class="text-on-surface hover:underline font-bold">Dismiss</a>
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="p-3 rounded-xl bg-primary/10 border border-primary/30 text-primary text-xs flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">sync</span>
                    Reservation modified and synchronized.
                </span>
                <a href="index.php" class="text-on-surface hover:underline font-bold">Dismiss</a>
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="p-3 rounded-xl bg-error/10 border border-error/30 text-error text-xs flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">delete</span>
                    Reservation record cancelled / deleted.
                </span>
                <a href="index.php" class="text-on-surface hover:underline font-bold">Dismiss</a>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: EXECUTIVE OPERATIONAL KPI BAR -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3.5">
            <!-- KPI 1: Occupancy -->
            <div class="bg-surface-container-low border border-primary-container/20 rounded-xl p-3.5 shadow-xl relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-secondary uppercase tracking-widest">Property Occupancy</span>
                    <span class="material-symbols-outlined text-primary text-base">bed</span>
                </div>
                <div class="my-2 flex items-baseline gap-2">
                    <span class="font-serif text-2xl text-on-surface font-semibold"><?= $occupancyPct ?>%</span>
                    <span class="text-[10px] font-semibold text-tertiary flex items-center bg-tertiary/10 px-1 py-0.5 rounded border border-tertiary/20">
                        <span class="material-symbols-outlined text-[11px]">monitor_heart</span> Live
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs text-on-surface-variant text-[11px]">
                    <span><?= $occupiedRooms ?> / <?= $totalRooms ?> Rooms Occupied</span>
                    <span class="text-outline font-mono"><?= $vacantRooms ?> Vacant</span>
                </div>
                <div class="w-full bg-surface-container h-1 rounded-full mt-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-secondary to-primary h-full rounded-full" style="width: <?= min(100, $occupancyPct) ?>%"></div>
                </div>
            </div>

            <!-- KPI 2: RevPAR & ADR -->
            <div class="bg-surface-container-low border border-primary-container/20 rounded-xl p-3.5 shadow-xl relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-secondary uppercase tracking-widest">RevPAR &amp; ADR</span>
                    <span class="material-symbols-outlined text-primary text-base">trending_up</span>
                </div>
                <div class="my-2 flex items-baseline gap-2">
                    <span class="font-serif text-2xl text-primary font-semibold"><?= number_format($revpar, 2) ?> <span class="text-base text-secondary font-normal">MAD</span></span>
                    <span class="text-[10px] font-semibold text-secondary-fixed bg-secondary-container/40 px-1.5 py-0.5 rounded border border-secondary/30">
                        ADR <?= number_format($adr, 2) ?>
                    </span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-on-surface-variant">
                    <span class="text-tertiary flex items-center gap-0.5 font-medium">
                        <span class="material-symbols-outlined text-[12px]">verified</span> Revenue &divide; <?= $totalRooms ?> rooms
                    </span>
                </div>
                <div class="w-full bg-surface-container h-1 rounded-full mt-2.5 overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: <?= min(100, max(5, round($revpar / 10))) ?>%"></div>
                </div>
            </div>

            <!-- KPI 3: Today's Arrival / Departure Flow -->
            <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-3.5 shadow-xl flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-secondary uppercase tracking-widest">Today's Flow</span>
                    <span class="material-symbols-outlined text-secondary text-base">sync_alt</span>
                </div>
                <div class="grid grid-cols-2 gap-2 my-2">
                    <div class="border-r border-outline-variant/30 pr-2">
                        <span class="text-[10px] text-outline uppercase block">Active Stays</span>
                        <div class="flex items-baseline gap-1">
                            <span class="font-serif text-xl text-on-surface font-semibold"><?= $activeReservations ?></span>
                            <span class="text-[11px] text-tertiary font-mono">In-House</span>
                        </div>
                        <span class="text-[10px] text-primary font-semibold"><?= $activeReservations ?> Confirmées</span>
                    </div>
                    <div class="pl-1">
                        <span class="text-[10px] text-outline uppercase block">Pending</span>
                        <div class="flex items-baseline gap-1">
                            <span class="font-serif text-xl text-on-surface font-semibold"><?= $pendingReservations ?></span>
                            <span class="text-[11px] text-secondary font-mono">Awaiting</span>
                        </div>
                        <span class="text-[10px] text-outline">À confirmer</span>
                    </div>
                </div>
                <div class="w-full bg-surface-container h-1 rounded-full mt-1 overflow-hidden">
                    <div class="bg-secondary-fixed h-full rounded-full" style="width: <?= $liveShare ?>%"></div>
                </div>
            </div>

            <!-- KPI 4: Housekeeping Readiness -->
            <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-3.5 shadow-xl flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-secondary uppercase tracking-widest">Housekeeping Status</span>
                    <span class="material-symbols-outlined text-tertiary text-base">cleaning_services</span>
                </div>
                <div class="my-2 flex items-baseline gap-2">
                    <span class="font-serif text-2xl text-tertiary font-semibold"><?= $housekeepingPct ?>%</span>
                    <span class="text-xs text-on-surface-variant">Clean &amp; Inspected</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-on-surface-variant">
                    <span class="text-secondary"><?= $cleaningRooms ?> in Cleaning</span>
                    <span class="text-tertiary font-medium"><?= $maintenanceRooms ?> Out-of-Order</span>
                </div>
                <div class="w-full bg-surface-container h-1 rounded-full mt-2.5 overflow-hidden">
                    <div class="bg-tertiary h-full rounded-full" style="width: <?= max(2, $housekeepingPct) ?>%"></div>
                </div>
            </div>

            <!-- KPI 5: In-House Revenue Today -->
            <div class="bg-surface-container-low border border-primary-container/30 rounded-xl p-3.5 shadow-xl flex flex-col justify-between relative">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-secondary uppercase tracking-widest">Total Booked Gross</span>
                    <span class="material-symbols-outlined text-primary text-base">account_balance_wallet</span>
                </div>
                <div class="my-2">
                    <span class="font-serif text-2xl text-on-surface font-semibold"><?= number_format($totalRevenue, 2) ?> <span class="text-xs text-secondary">MAD</span></span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-on-surface-variant">
                    <span>Database Logged: <strong class="text-on-surface font-semibold"><?= $totalReservations ?> Folios</strong></span>
                    <a href="reservation_create.php" class="text-primary font-semibold hover:underline">+ New</a>
                </div>
                <div class="w-full bg-surface-container h-1 rounded-full mt-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-primary to-tertiary h-full rounded-full" style="width: <?= $grossShare ?>%"></div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: WORKSPACE GRID (TAPE CHART + PRIORITY DESK DOCK) -->
        <div class="grid grid-cols-1 2xl:grid-cols-12 gap-6">
            
            <!-- LEFT & CENTER: INTERACTIVE TAPE CHART GANTT (8 Cols) -->
            <div class="2xl:col-span-8 space-y-4">
                
                <!-- Chart Header & Filters Bar -->
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-4 shadow-xl flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <button class="p-1 text-on-surface-variant hover:text-primary rounded hover:bg-surface-container"><span class="material-symbols-outlined text-sm">chevron_left</span></button>
                            <h2 class="font-serif text-lg text-on-surface tracking-wide">Tape Chart: Current Live Horizon</h2>
                            <button class="p-1 text-on-surface-variant hover:text-primary rounded hover:bg-surface-container"><span class="material-symbols-outlined text-sm">chevron_right</span></button>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-primary/10 text-primary border border-primary/20 rounded">7-Day Live Horizon</span>
                    </div>
                    <!-- Filter Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <a href="index.php" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-primary text-on-primary-fixed shadow-sm no-underline">All Rooms</a>
                        <a href="rooms.php" class="px-2.5 py-1 text-xs rounded-lg bg-surface-container text-on-surface-variant hover:text-on-surface border border-outline-variant/20 hover:border-outline-variant/50 transition-colors no-underline">Suites Only</a>
                        <a href="#reservations-table" class="px-2.5 py-1 text-xs rounded-lg bg-surface-container text-on-surface-variant hover:text-primary border border-outline-variant/20 hover:border-outline-variant/50 transition-colors flex items-center gap-1 no-underline">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span> VIP In-House
                        </a>
                        <a href="reservation_create.php" class="px-2.5 py-1 text-xs rounded-lg bg-surface-container text-secondary border border-secondary/20 hover:bg-surface-container-high transition-colors no-underline">+ Quick Booking</a>
                    </div>
                </div>

                <!-- Gantt Matrix Container -->
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl shadow-2xl overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <div class="min-w-[850px]">
                            <!-- Date Column Header Row -->
                            <div class="grid grid-cols-12 bg-surface-container-lowest/90 border-b border-outline-variant/40 text-center sticky top-0 z-20">
                                <div class="col-span-3 px-4 py-3 text-left border-r border-outline-variant/30 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-secondary uppercase tracking-wider">Suite / Category</span>
                                    <span class="text-[10px] text-outline font-mono">Status</span>
                                </div>
                                <?php foreach ($horizon as $i => $h): ?>
                                    <div class="<?= ($i === 6) ? 'col-span-2' : 'col-span-1' ?> py-2.5 border-r border-outline-variant/25 <?= $h['isToday'] ? 'bg-surface-container-high/40' : '' ?>">
                                        <?php if ($h['isToday']): ?>
                                            <span class="text-[10px] text-primary uppercase font-bold block">TODAY</span>
                                        <?php else: ?>
                                            <span class="text-[10px] text-outline uppercase block"><?= $h['day'] ?></span>
                                        <?php endif; ?>
                                        <span class="text-xs text-on-surface <?= $h['isToday'] ? 'font-semibold' : '' ?>"><?= $h['num'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Category Group 1 -->
                            <div class="bg-surface-container-highest/30 px-4 py-1.5 border-b border-outline-variant/20 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-xs" style="font-variation-settings: 'FILL' 1;">star</span>
                                    <span class="text-[10px] font-bold text-primary uppercase tracking-widest">Presidential Penthouse &amp; Luxury Suites</span>
                                </div>
                                <span class="text-[10px] font-mono text-outline"><?= count($rooms) ?> Keys Active</span>
                            </div>

                            <?php foreach ($rooms as $rm): $res = $roomRes[$rm['id_chambre']] ?? null; ?>
                                <div class="grid grid-cols-12 border-b border-outline-variant/20 items-stretch min-h-[54px] hover:bg-surface-container/40 transition-colors">
                                    <div class="col-span-3 px-4 py-2 border-r border-outline-variant/30 flex items-center justify-between bg-surface-container-lowest/30">
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono text-sm font-bold text-on-surface"><?= htmlspecialchars($rm['numero_chambre']) ?></span>
                                                <span class="text-xs text-secondary truncate"><?= htmlspecialchars($rm['type_libelle']) ?></span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="inline-flex items-center gap-1 text-[9px] <?= ($rm['statut_chambre'] === 'Disponible') ? 'text-tertiary bg-tertiary/10' : 'text-primary bg-primary/10' ?> px-1 rounded">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= ($rm['statut_chambre'] === 'Disponible') ? 'bg-tertiary' : 'bg-primary' ?>"></span>
                                                    <?= htmlspecialchars($rm['statut_chambre']) ?>
                                                </span>
                                                <span class="text-[9px] text-outline">Vue: <?= htmlspecialchars($rm['vue']) ?></span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] text-outline font-mono"><?= number_format((float)$rm['prix_nuit'], 0) ?> MAD</span>
                                    </div>
                                    <!-- Timeline track -->
                                    <div class="col-span-9 p-1.5 relative flex items-center">
                                        <?php if ($res): ?>
                                            <?php
                                                $ribStyles = [
                                                    'Enregistrée' => ['bar' => 'bg-tertiary', 'border' => 'border-tertiary/40', 'dot' => 'bg-tertiary', 'pill' => 'text-tertiary bg-tertiary/10 border-tertiary/30'],
                                                    'Confirmée'   => ['bar' => 'bg-primary',   'border' => 'border-primary/40',   'dot' => 'bg-primary',   'pill' => 'text-primary bg-primary/10 border-primary/30'],
                                                    'En attente'  => ['bar' => 'bg-secondary', 'border' => 'border-secondary/40', 'dot' => 'bg-secondary', 'pill' => 'text-secondary bg-secondary/10 border-secondary/30'],
                                                ];
                                                $rs = $ribStyles[$res['statut_reservation']] ?? $ribStyles['En attente'];
                                                $isHouse = $res['statut_reservation'] === 'Enregistrée';
                                            ?>
                                            <div class="w-[85%] bg-surface-container-high border <?= $rs['border'] ?> rounded-lg p-2 flex items-center justify-between shadow-md relative overflow-hidden">
                                                <div class="absolute left-0 top-0 bottom-0 w-1 <?= $rs['bar'] ?>"></div>
                                                <div class="flex items-center gap-2 pl-1.5 truncate">
                                                    <span class="w-2 h-2 rounded-full <?= $rs['dot'] ?>"></span>
                                                    <span class="text-xs font-semibold text-on-surface truncate"><?= htmlspecialchars($res['prenom'] . ' ' . $res['nom']) ?> • <?= htmlspecialchars($res['statut_fidelite'] ?? 'Standard') ?> Tier</span>
                                                    <span class="text-[10px] <?= $rs['pill'] ?> px-1.5 rounded font-mono"><?= $isHouse ? 'In-House' : 'Arrivée ' . $res['date_arrivee'] ?></span>
                                                </div>
                                                <a href="reservation_edit.php?id=<?= (int)$res['id_reservation'] ?>" class="text-[10px] text-on-surface-variant hover:text-primary font-semibold pr-1 no-underline">Ouvrir &rarr;</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-full flex items-center justify-between px-3 text-outline/50 text-[10px] border border-dashed border-outline-variant/30 rounded-lg py-1.5">
                                                <span>Room vacant and ready for allocation</span>
                                                <a href="reservation_create.php?room=<?= $rm['id_chambre'] ?>" class="text-primary hover:underline font-bold">+ Allocate</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                    <!-- Legend footer -->
                    <div class="bg-surface-container-lowest px-4 py-2.5 border-t border-outline-variant/30 flex flex-wrap items-center justify-between text-xs gap-3">
                        <div class="flex items-center gap-4">
                            <span class="text-outline text-[11px]">Legend:</span>
                            <div class="flex items-center gap-1.5 text-on-surface-variant text-[11px]">
                                <span class="w-2.5 h-2.5 rounded-sm bg-primary"></span>
                                <span>VIP Arriving Today</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-on-surface-variant text-[11px]">
                                <span class="w-2.5 h-2.5 rounded-sm bg-tertiary"></span>
                                <span>Checked In / In-House</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-on-surface-variant text-[11px]">
                                <span class="w-2.5 h-2.5 rounded-sm bg-secondary"></span>
                                <span>Guaranteed Booking</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-on-surface-variant text-[11px]">
                                <span class="w-2.5 h-2.5 rounded-sm bg-surface-container-highest border border-outline-variant"></span>
                                <span>Vacant</span>
                            </div>
                        </div>
                        <div class="text-[11px] text-outline font-mono">
                            Live from MySQL
                        </div>
                    </div>
                </div>

                <!-- Active Folio Bar -->
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-4 shadow-xl flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-surface-container rounded-lg border border-primary/20 text-primary">
                            <span class="material-symbols-outlined">receipt_long</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-secondary uppercase tracking-widest">Active Front Desk Folio</span>
                            <?php if ($activeFolio): ?>
                                <h4 class="font-serif text-sm text-on-surface"><?= htmlspecialchars($activeFolio['prenom'] . ' ' . $activeFolio['nom']) ?> (Folio #LX-<?= $activeFolio['id_reservation'] ?>)</h4>
                            <?php else: ?>
                                <h4 class="font-serif text-sm text-on-surface">No active folio</h4>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <?php if ($activeFolio): ?>
                            <div class="text-right">
                                <span class="text-[10px] text-outline block uppercase">Total du séjour</span>
                                <span class="text-xs font-mono text-primary font-bold"><?= number_format((float)$activeFolio['montant_total'], 2) ?> MAD</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($activeFolio): ?>
                            <a href="reservation_edit.php?id=<?= (int)$activeFolio['id_reservation'] ?>" class="px-3.5 py-1.5 rounded-lg border border-primary/40 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-semibold transition-colors flex items-center gap-1.5 no-underline">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                <span>Ouvrir le folio</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- RIGHT-HAND OPERATIONAL DOCK: ARRIVAL QUEUE (4 Cols) -->
            <div class="2xl:col-span-4 space-y-4">
                
                <!-- Upcoming Arrivals Queue (real data) -->
                <div class="bg-surface-container-low border border-primary-container/25 rounded-xl p-4 shadow-2xl space-y-3.5">
                    <div class="flex items-center justify-between pb-2 border-b border-outline-variant/30">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
                            <h3 class="font-serif text-base text-on-surface font-semibold">Upcoming Arrivals</h3>
                        </div>
                        <span class="text-[10px] font-bold text-primary font-mono bg-primary/10 px-2 py-0.5 rounded border border-primary/25"><?= count($arrivals) ?> Arrivée<?= count($arrivals) !== 1 ? 's' : '' ?></span>
                    </div>

                    <?php if (empty($arrivals)): ?>
                        <div class="p-3 rounded bg-surface-container-lowest text-[11px] text-outline text-center">No pending or confirmed arrivals</div>
                    <?php else: ?>
                        <?php foreach ($arrivals as $ar): ?>
                            <?php
                                $isHouse = $ar['statut_reservation'] === 'Enregistrée';
                                $statusStyles = $isHouse ? 'bg-tertiary/10 text-tertiary border-tertiary/25' : 'bg-secondary/10 text-secondary border-secondary/25';
                                $tier = $ar['statut_fidelite'] ?? 'Standard';
                            ?>
                            <div class="bg-surface-container border border-outline-variant/30 rounded-lg p-3.5 space-y-2 relative hover:border-primary/60 transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center text-primary font-bold text-xs">
                                            <?= strtoupper(mb_substr($ar['prenom'], 0, 1)) . strtoupper(mb_substr($ar['nom'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-semibold text-on-surface"><?= htmlspecialchars($ar['prenom'] . ' ' . $ar['nom']) ?></h4>
                                            <span class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                                <?= in_array($tier, ['Platinum','Gold']) ? '<span class="material-symbols-outlined text-xs" style="font-variation-settings: \'FILL\' 1;">workspace_premium</span>' : '<span class="material-symbols-outlined text-xs">person</span>' ?>
                                                <?= htmlspecialchars($tier) ?> Tier &bull; Room <?= htmlspecialchars($ar['numero_chambre']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-semibold <?= $statusStyles ?> px-2 py-0.5 rounded border">
                                        <?= $isHouse ? 'In-House' : 'Arr. ' . $ar['date_arrivee'] ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <a href="reservation_edit.php?id=<?= (int)$ar['id_reservation'] ?>" class="flex-1 py-1.5 bg-primary text-on-primary-fixed rounded font-semibold text-center hover:bg-primary-fixed-dim transition-all text-xs flex items-center justify-center gap-1 no-underline">
                                        <span class="material-symbols-outlined text-xs">key</span>
                                        <span>Open &amp; Manage</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- SECTION 3: ALL DATABASE RESERVATIONS TABLE (CRUD MANAGEMENT) -->
        <section id="reservations-table" class="bg-surface-container-low border border-outline-variant/30 rounded-xl shadow-2xl overflow-hidden mt-8">
            <div class="p-4 bg-surface-container-lowest/80 border-b border-outline-variant/30 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="font-serif text-lg font-semibold text-on-surface">All Database Reservations</h3>
                    <p class="text-[11px] text-on-surface-variant">Live PMS database records linked with guests, rooms, and billing</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" id="tableSearch" 
                           class="px-3 py-1.5 bg-surface-container border border-outline-variant/40 rounded-lg text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-primary" 
                           placeholder="Filter reservations...">
                    
                    <select id="statusFilter" class="px-3 py-1.5 bg-surface-container border border-outline-variant/40 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                        <option value="">All Statuses</option>
                        <option value="Confirmée">Confirmée</option>
                        <option value="Enregistrée">Enregistrée</option>
                        <option value="En attente">En attente</option>
                        <option value="Terminée">Terminée</option>
                        <option value="Annulée">Annulée</option>
                    </select>

                    <a href="reservation_create.php" class="px-3 py-1.5 bg-primary text-on-primary-fixed text-xs font-bold rounded-lg shadow-sm hover:brightness-110 flex items-center gap-1.5 no-underline">
                        <span class="material-symbols-outlined text-sm">add</span>
                        <span>New Booking</span>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-lowest text-secondary uppercase font-bold text-[10px] tracking-wider border-b border-outline-variant/30">
                            <th class="py-3 px-4">Folio ID</th>
                            <th class="py-3 px-4">Guest Name</th>
                            <th class="py-3 px-4">Hotel Property &amp; Room</th>
                            <th class="py-3 px-4">Stay Horizon</th>
                            <th class="py-3 px-4">Nights</th>
                            <th class="py-3 px-4">Guests</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Total Amount</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-8 text-outline">No reservations currently recorded in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $r): ?>
                                <tr class="hover:bg-surface-container/50 transition-colors">
                                    <td class="py-3.5 px-4 font-mono font-bold text-primary">#LX-<?= htmlspecialchars((string)$r['id_reservation']) ?></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-on-surface"><?= htmlspecialchars($r['client_prenom'] . ' ' . $r['client_nom']) ?></div>
                                        <div class="text-[10px] text-outline"><?= htmlspecialchars($r['client_email']) ?> • Tier: <span class="text-secondary"><?= htmlspecialchars($r['statut_fidelite']) ?></span></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-on-surface"><?= htmlspecialchars($r['nom_hotel']) ?></div>
                                        <div class="text-[10px] text-secondary">Room <?= htmlspecialchars($r['numero_chambre']) ?> (<?= htmlspecialchars($r['type_chambre']) ?>)</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div><span class="text-secondary">In:</span> <?= htmlspecialchars($r['date_arrivee']) ?></div>
                                        <div class="text-outline"><span class="text-secondary">Out:</span> <?= htmlspecialchars($r['date_depart']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono"><?= htmlspecialchars((string)$r['nuits']) ?> nts</td>
                                    <td class="py-3.5 px-4"><?= htmlspecialchars((string)$r['nb_adultes']) ?> Ad. / <?= htmlspecialchars((string)$r['nb_enfants']) ?> Ch.</td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($r['statut_reservation'] === 'Confirmée'): ?>
                                            <span class="badge px-2 py-0.5 rounded text-[10px] font-bold bg-tertiary/15 text-tertiary border border-tertiary/30">Confirmée</span>
                                        <?php elseif ($r['statut_reservation'] === 'Enregistrée'): ?>
                                            <span class="badge px-2 py-0.5 rounded text-[10px] font-bold bg-primary/15 text-primary border border-primary/30">Enregistrée</span>
                                        <?php elseif ($r['statut_reservation'] === 'En attente'): ?>
                                            <span class="badge px-2 py-0.5 rounded text-[10px] font-bold bg-secondary/15 text-secondary border border-secondary/30">En attente</span>
                                        <?php else: ?>
                                            <span class="badge px-2 py-0.5 rounded text-[10px] font-bold bg-error/15 text-error border border-error/30"><?= htmlspecialchars($r['statut_reservation']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-on-surface">
                                        <?= number_format((float)$r['montant_total'], 2) ?> MAD
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="reservation_edit.php?id=<?= $r['id_reservation'] ?>" class="p-1.5 rounded hover:bg-surface-container text-on-surface-variant hover:text-primary transition-colors" title="Edit Booking">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                            </a>
                                            <a href="reservation_delete.php?id=<?= $r['id_reservation'] ?>" 
                                               class="p-1.5 rounded hover:bg-surface-container text-on-surface-variant hover:text-error transition-colors" 
                                               title="Cancel / Delete"
                                               onclick="return confirm('Are you sure you want to cancel reservation #<?= $r['id_reservation'] ?>?');">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <?php require_once __DIR__ . '/../views/partials/footer.php'; ?>
</div>

<script src="js/app.js"></script>