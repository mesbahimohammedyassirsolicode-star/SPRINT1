<?php
/**
 * LuxeStay Grand Riviera & Spa
 * Guests & Loyalty Directory (Matching Stitch Luxury Theme)
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$errors = [];

// New Guest Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $pays = trim($_POST['pays'] ?? 'Maroc');
    $statut_fidelite = $_POST['statut_fidelite'] ?? 'Standard';
    $password_hash = password_hash('GuestPass123!', PASSWORD_DEFAULT);

    if (empty($nom) || empty($prenom) || empty($email)) {
        $errors[] = "First name, last name, and email are required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO client (nom, prenom, email, mot_de_passe, telephone, ville, pays, statut_fidelite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nom, $prenom, $email, $password_hash, $telephone, $ville, $pays, $statut_fidelite]);
            header("Location: clients.php?msg=created");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Error saving guest: " . $e->getMessage();
        }
    }
}

// Fetch all guests
$sql = "
    SELECT 
        c.id_client, c.nom, c.prenom, c.email, c.telephone, c.ville, c.pays, c.statut_fidelite, c.date_inscription,
        COUNT(r.id_reservation) AS total_reservations,
        COALESCE(SUM(r.montant_total), 0) AS total_spend
    FROM client c
    LEFT JOIN reservation r ON c.id_client = r.id_client
    GROUP BY c.id_client
    ORDER BY c.id_client DESC
";
$clients = $pdo->query($sql)->fetchAll();

$pageTitle = "Guest Directory & VIP Loyalty Dossier - LuxeStay";
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
?>

<div class="pl-64 flex flex-col min-h-screen">
    <?php require_once __DIR__ . '/../views/partials/topbar.php'; ?>

    <main class="mt-16 p-4 xl:p-6 space-y-6 flex-1">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-bold text-primary uppercase tracking-widest flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-xs">workspace_premium</span>
                    VIP Heritage &amp; Ambassador Guest Relations
                </span>
                <h1 class="font-serif text-2xl font-bold text-on-surface">Guest Directory &amp; Loyalty Dossier</h1>
                <p class="text-xs text-on-surface-variant">Profile credentials, concierge dossiers, and historical stay folios</p>
            </div>
            <a href="reservation_create.php" class="px-3.5 py-2 bg-primary text-on-primary-fixed rounded-xl text-xs font-bold shadow-md hover:brightness-110 no-underline flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">add</span>
                <span>Create Booking for Guest</span>
            </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
            <div class="p-3 rounded-xl bg-tertiary/10 border border-tertiary/30 text-tertiary text-xs">
                ✅ Guest profile registered successfully and loyalty tier assigned!
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Guests Table (8 cols) -->
            <div class="lg:col-span-8 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-4 bg-surface-container-lowest border-b border-outline-variant/30 flex items-center justify-between">
                    <h3 class="font-serif text-base font-bold text-on-surface">Registered Guests (<?= count($clients) ?>)</h3>
                    <span class="text-[10px] text-outline font-mono">Synced with Central PMS</span>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-surface-container-lowest text-secondary uppercase font-bold text-[10px] tracking-wider border-b border-outline-variant/30">
                                <th class="py-3 px-4">Guest Profile</th>
                                <th class="py-3 px-4">Contact Info</th>
                                <th class="py-3 px-4">Location</th>
                                <th class="py-3 px-4">Loyalty Tier</th>
                                <th class="py-3 px-4">Stays / Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php foreach ($clients as $c): ?>
                                <tr class="hover:bg-surface-container/50 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-on-surface"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></div>
                                        <div class="text-[10px] text-outline font-mono">Member ID: #CL-<?= $c['id_client'] ?></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="text-on-surface"><?= htmlspecialchars($c['email']) ?></div>
                                        <div class="text-[10px] text-secondary"><?= htmlspecialchars($c['telephone'] ?? 'No phone recorded') ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 text-on-surface-variant">
                                        <div><?= htmlspecialchars($c['ville'] ?? '—') ?></div>
                                        <div class="text-[10px] text-outline"><?= htmlspecialchars($c['pays'] ?? '—') ?></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($c['statut_fidelite'] === 'Platinum'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary/20 text-primary border border-primary/40">★ Platinum VIP</span>
                                        <?php elseif ($c['statut_fidelite'] === 'Gold'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-secondary/20 text-secondary border border-secondary/40">★ Gold Member</span>
                                        <?php elseif ($c['statut_fidelite'] === 'Silver'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-tertiary/20 text-tertiary border border-tertiary/40">Silver Elite</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-surface-container-high text-outline">Standard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-on-surface"><?= $c['total_reservations'] ?> Stays</div>
                                        <div class="text-[10px] text-secondary font-mono"><?= number_format((float)$c['total_spend'], 0) ?> MAD</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fast Guest Registration Card (4 cols) -->
            <div class="lg:col-span-4 bg-surface-container-low border border-outline-variant/30 rounded-2xl p-5 shadow-2xl space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-outline-variant/30">
                    <span class="material-symbols-outlined text-primary text-base">person_add</span>
                    <h3 class="font-serif text-base font-bold text-on-surface">Add New Guest Profile</h3>
                </div>

                <form method="POST" action="clients.php" class="space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="text-[10px] text-secondary font-bold uppercase">First Name *</label>
                        <input type="text" name="prenom" required 
                               class="w-full p-2 bg-surface-container rounded-xl border border-outline-variant/40 text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] text-secondary font-bold uppercase">Last Name *</label>
                        <input type="text" name="nom" required 
                               class="w-full p-2 bg-surface-container rounded-xl border border-outline-variant/40 text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] text-secondary font-bold uppercase">Email Address *</label>
                        <input type="email" name="email" required 
                               class="w-full p-2 bg-surface-container rounded-xl border border-outline-variant/40 text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] text-secondary font-bold uppercase">Phone Number</label>
                        <input type="text" name="telephone" placeholder="+212 600 000000"
                               class="w-full p-2 bg-surface-container rounded-xl border border-outline-variant/40 text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <label class="text-[10px] text-secondary font-bold uppercase">City</label>
                            <input type="text" name="ville" placeholder="e.g. Tanger"
                                   class="w-full p-2 bg-surface-container rounded-xl border border-outline-variant/40 text-on-surface focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] text-secondary font-bold uppercase">Loyalty Tier</label>
                            <select name="statut_fidelite" class="w-full p-2 bg-surface-container rounded-xl border border-outline-variant/40 text-on-surface focus:outline-none">
                                <option value="Standard">Standard</option>
                                <option value="Silver">Silver Elite</option>
                                <option value="Gold">Gold Member</option>
                                <option value="Platinum">Platinum VIP</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-2 py-2.5 bg-gradient-to-r from-primary-container via-primary to-primary-fixed-dim text-on-primary-fixed font-bold text-xs rounded-xl shadow-md hover:opacity-95 transition-all">
                        Register &amp; Issue Loyalty ID
                    </button>
                </form>
            </div>

        </div>

    </main>

    <?php require_once __DIR__ . '/../views/partials/footer.php'; ?>
</div>