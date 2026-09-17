<?php
/**
 * LuxeStay Grand Riviera & Spa
 * Room Inventory & Live Availability Matrix (Matching Stitch Mockup #3)
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// Quick room status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id_chambre = (int)($_POST['id_chambre'] ?? 0);
    $new_status = $_POST['statut_chambre'] ?? '';

    if ($id_chambre > 0 && in_array($new_status, ['Disponible', 'Occupée', 'Maintenance', 'Nettoyage'])) {
        $stmt = $pdo->prepare("UPDATE chambre SET statut_chambre = ? WHERE id_chambre = ?");
        $stmt->execute([$new_status, $id_chambre]);
        header("Location: rooms.php?msg=updated");
        exit;
    }
}

// Fetch KPIs
$totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre")->fetchColumn();
$availableRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Disponible'")->fetchColumn();
$occupiedRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Occupée'")->fetchColumn();
$maintenanceRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Maintenance'")->fetchColumn();
$cleaningRooms = (int)$pdo->query("SELECT COUNT(*) FROM chambre WHERE statut_chambre = 'Nettoyage'")->fetchColumn();
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

// Fetch all rooms
$sql = "
    SELECT 
        ch.id_chambre,
        ch.numero_chambre,
        ch.etage,
        ch.prix_nuit,
        ch.vue,
        ch.fumeur,
        ch.statut_chambre,
        h.nom_hotel,
        h.ville,
        th.libelle AS type_libelle,
        th.capacite_max,
        th.superficie_m2,
        GROUP_CONCAT(eq.nom_equipement SEPARATOR ', ') AS equipements
    FROM chambre ch
    JOIN hotel h ON ch.id_hotel = h.id_hotel
    JOIN type_hebergement th ON ch.id_type = th.id_type
    LEFT JOIN chambre_equipement ce ON ch.id_chambre = ce.id_chambre
    LEFT JOIN equipement eq ON ce.id_equipement = eq.id_equipement
    GROUP BY ch.id_chambre
    ORDER BY ch.prix_nuit DESC
";
$rooms = $pdo->query($sql)->fetchAll();

$pageTitle = "Room Inventory & Live Availability Matrix - LuxeStay";
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
?>

<div class="pl-64 flex flex-col min-h-screen">
    <?php require_once __DIR__ . '/../views/partials/topbar.php'; ?>

    <main class="mt-16 p-4 xl:p-6 space-y-6 flex-1">

        <!-- Top Header & Property Switcher -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-bold text-tertiary uppercase tracking-widest flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-tertiary animate-pulse"></span>
                    Palace Property Management System • Live from MySQL
                </span>
                <h1 class="font-serif text-2xl font-bold text-on-surface">Room Inventory &amp; Live Availability Matrix</h1>
                <p class="text-xs text-on-surface-variant">Real-time housekeeping telematics, room status orchestration, and allocation grid</p>
            </div>

            <!-- Active Property Branch Dropdown -->
            <div class="flex items-center gap-3">
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs">
                    <span class="text-[10px] text-outline block uppercase">Active Property Branch</span>
                    <span class="font-semibold text-primary">LuxeStay Grand Riviera &amp; Spa (Main Property)</span>
                </div>
            </div>
        </div>

        <!-- SECTION 1: 6 STATUS KPI METRIC CARDS -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            
            <!-- 1. Total Inventory -->
            <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-3 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-[10px] text-secondary font-bold uppercase">
                    <span>Total Inventory</span>
                    <span class="material-symbols-outlined text-xs">hotel</span>
                </div>
                <div class="font-serif text-2xl font-bold text-on-surface"><?= $totalRooms ?></div>
                <div class="text-[10px] text-outline">Rooms &amp; Villas Tracked</div>
            </div>

            <!-- 2. Available & Inspected -->
            <div class="bg-surface-container-low border border-tertiary/40 rounded-xl p-3 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-[10px] text-tertiary font-bold uppercase">
                    <span>Available / Ready</span>
                    <span class="material-symbols-outlined text-xs">check_circle</span>
                </div>
                <div class="font-serif text-2xl font-bold text-tertiary"><?= $availableRooms ?></div>
                <div class="text-[10px] text-on-surface-variant">Ready for Check-In</div>
            </div>

            <!-- 3. Occupied -->
            <div class="bg-surface-container-low border border-primary-container/40 rounded-xl p-3 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-[10px] text-primary font-bold uppercase">
                    <span>Occupied / Stays</span>
                    <span class="material-symbols-outlined text-xs">key</span>
                </div>
                <div class="font-serif text-2xl font-bold text-primary"><?= $occupiedRooms ?></div>
                <div class="text-[10px] text-on-surface-variant">Active Guest Folios</div>
            </div>

            <!-- 4. Housekeeping -->
            <div class="bg-surface-container-low border border-secondary/40 rounded-xl p-3 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-[10px] text-secondary font-bold uppercase">
                    <span>Housekeeping</span>
                    <span class="material-symbols-outlined text-xs">cleaning_services</span>
                </div>
                <div class="font-serif text-2xl font-bold text-secondary"><?= $cleaningRooms ?></div>
                <div class="text-[10px] text-outline">Cleaning in Progress</div>
            </div>

            <!-- 5. Maintenance -->
            <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-3 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-[10px] text-outline font-bold uppercase">
                    <span>Out of Order</span>
                    <span class="material-symbols-outlined text-xs">build</span>
                </div>
                <div class="font-serif text-2xl font-bold text-on-surface-variant"><?= $maintenanceRooms ?></div>
                <div class="text-[10px] text-outline">Technical Service</div>
            </div>

            <!-- 6. Occupancy Rate -->
            <div class="bg-surface-container-low border border-primary/30 rounded-xl p-3 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-[10px] text-primary font-bold uppercase">
                    <span>Occupancy Rate</span>
                    <span class="material-symbols-outlined text-xs">analytics</span>
                </div>
                <div class="font-serif text-2xl font-bold text-on-surface"><?= $occupancyRate ?>%</div>
                <div class="text-[10px] text-tertiary font-bold"><?= $occupiedRooms ?> of <?= $totalRooms ?> rooms occupied</div>
            </div>
        </div>

        <!-- Filters & Search Toolbar -->
        <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-3 shadow-xl flex flex-wrap items-center justify-between gap-4 text-xs">
            <div class="flex flex-wrap items-center gap-2" id="roomFilters">
                <button data-filter="all" class="room-filter px-3 py-1 bg-primary text-on-primary-fixed rounded-lg font-bold">All Rooms (<?= $totalRooms ?>)</button>
                <button data-filter="Disponible" class="room-filter px-3 py-1 bg-surface-container text-tertiary rounded-lg font-semibold border border-tertiary/25">Available (<?= $availableRooms ?>)</button>
                <button data-filter="Occupée" class="room-filter px-3 py-1 bg-surface-container text-primary rounded-lg font-semibold border border-primary/25">Occupied (<?= $occupiedRooms ?>)</button>
                <button data-filter="Nettoyage" class="room-filter px-3 py-1 bg-surface-container text-secondary rounded-lg font-semibold border border-secondary/25">Housekeeping (<?= $cleaningRooms ?>)</button>
                <button data-filter="Maintenance" class="room-filter px-3 py-1 bg-surface-container text-outline rounded-lg font-semibold border border-outline-variant/30">Maintenance (<?= $maintenanceRooms ?>)</button>
            </div>

            <div class="flex items-center gap-3">
                <input type="text" id="roomSearch" placeholder="Search room #, type, or amenity..."
                       class="px-3 py-1.5 bg-surface-container border border-outline-variant/40 rounded-lg text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-primary">
            </div>
        </div>

        <!-- SECTION 2: LUXURY ROOM CARDS GRID (Matching Stitch Mockup #3) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($rooms as $rm): ?>
                <?php $roomAmenities = array_filter(array_map('trim', explode(',', $rm['equipements'] ?? ''))); ?>
                <div class="room-card bg-surface-container-low border border-outline-variant/30 hover:border-primary/50 rounded-2xl overflow-hidden shadow-2xl transition-all flex flex-col justify-between" data-status="<?= htmlspecialchars($rm['statut_chambre']) ?>">
                    
                    <div>
                        <!-- Card Header Image & Overlay -->
                        <div class="h-44 bg-gradient-to-tr from-surface-container-lowest via-surface-container-high to-primary/10 border-b border-outline-variant/30 p-3.5 flex flex-col justify-between relative">
                            
                            <!-- Top chips -->
                            <div class="flex items-center justify-between z-10">
                                <span class="px-2.5 py-0.5 rounded-lg font-mono font-bold text-xs bg-surface-container-lowest/80 text-primary border border-primary/30">
                                    <?= htmlspecialchars($rm['numero_chambre']) ?> | Fl. <?= htmlspecialchars((string)$rm['etage']) ?>
                                </span>

                                <?php if ($rm['statut_chambre'] === 'Disponible'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-tertiary/20 text-tertiary border border-tertiary/40">
                                        AVAILABLE • READY
                                    </span>
                                <?php elseif ($rm['statut_chambre'] === 'Occupée'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/20 text-primary border border-primary/40">
                                        OCCUPIED • IN-HOUSE
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary/20 text-secondary border border-secondary/40">
                                        <?= strtoupper($rm['statut_chambre']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Bottom rate chip on image -->
                            <div class="z-10 flex items-end justify-between">
                                <span class="text-[10px] text-secondary font-bold uppercase tracking-wider">Panoramic <?= htmlspecialchars($rm['vue']) ?> View</span>
                                <div class="text-right">
                                    <span class="text-[9px] text-outline uppercase block">Nightly Rate</span>
                                    <span class="font-serif text-lg font-bold text-primary"><?= number_format((float)$rm['prix_nuit'], 0) ?> MAD</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4 space-y-3">
                            <div>
                                <h3 class="font-serif text-base font-bold text-on-surface"><?= htmlspecialchars($rm['type_libelle']) ?></h3>
                                <p class="text-[11px] text-secondary"><?= htmlspecialchars($rm['nom_hotel']) ?> • <?= $rm['superficie_m2'] ?> m²</p>
                            </div>

                            <!-- Amenities Row -->
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-on-surface-variant pt-1 border-t border-outline-variant/20">
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">king_bed</span> Max <?= $rm['capacite_max'] ?> Guests</span>
                                <?php $shownAmenities = array_slice($roomAmenities, 0, 3); ?>
                                <?php if (!empty($shownAmenities)): ?>
                                    <?php foreach ($shownAmenities as $name): ?>
                                        <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-primary/70"></span><span class="truncate"><?= htmlspecialchars($name) ?></span></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">balcony</span> <?= htmlspecialchars($rm['vue']) ?> View</span>
                                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">straighten</span> <?= $rm['superficie_m2'] ?> m²</span>
                                <?php endif; ?>
                            </div>

                            <!-- Status update trigger -->
                            <form method="POST" action="rooms.php" class="pt-2 border-t border-outline-variant/20 flex items-center justify-between text-xs">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_chambre" value="<?= $rm['id_chambre'] ?>">
                                <span class="text-[10px] text-outline uppercase">Housekeeping</span>
                                <select name="statut_chambre" onchange="this.form.submit()" 
                                        class="bg-surface-container text-xs text-on-surface border border-outline-variant/40 rounded px-2 py-0.5 focus:outline-none focus:border-primary">
                                    <?php foreach (['Disponible', 'Occupée', 'Maintenance', 'Nettoyage'] as $st): ?>
                                        <option value="<?= $st ?>" <?= ($rm['statut_chambre'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div class="p-4 pt-0">
                        <a href="reservation_create.php?room=<?= $rm['id_chambre'] ?>" class="w-full py-2 bg-gradient-to-r from-primary-container via-primary to-primary-fixed-dim text-on-primary-fixed text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 shadow-md hover:opacity-95 transition-all no-underline">
                            <span class="material-symbols-outlined text-sm">assignment_add</span>
                            <span>Assign &amp; Book Now</span>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <?php require_once __DIR__ . '/../views/partials/footer.php'; ?>
</div>