<?php
/**
 * Shared Executive Left Navigation Sidebar
 * LuxeStay Grand Riviera & Spa
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="fixed top-0 left-0 bottom-0 z-50 w-64 flex flex-col justify-between p-4 bg-surface-container-lowest border-r border-outline-variant/30 shadow-2xl">
    <!-- Top Branding & Property Identity -->
    <div class="space-y-6">
        <a href="index.php" class="flex items-center gap-3 px-2 pt-1 no-underline">
            <!-- Monogram Crest -->
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary via-primary-container to-secondary-container p-[1px] flex items-center justify-center shadow-lg">
                <div class="w-full h-full bg-surface-container-lowest rounded-lg flex items-center justify-center">
                    <span class="font-serif text-primary text-base font-bold tracking-wider">LS</span>
                </div>
            </div>
            <div class="flex flex-col">
                <span class="font-serif text-primary tracking-widest uppercase text-sm font-bold">LuxeStay Grand</span>
                <span class="text-secondary text-[10px] tracking-wider opacity-85">Palace &amp; Riviera Reserve</span>
            </div>
        </a>

        <!-- Quick Action CTA -->
        <a href="reservation_create.php" class="w-full py-2.5 px-3 bg-gradient-to-r from-primary-container via-primary to-primary-fixed-dim text-on-primary-fixed font-semibold text-xs rounded-lg flex items-center justify-center gap-2 hover:opacity-95 transition-all shadow-md active:scale-98 no-underline">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">add_circle</span>
            <span>New Reservation</span>
        </a>

        <!-- Navigation Tabs -->
        <nav class="space-y-1">
            <a href="index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-xs transition-colors <?= ($currentPage === 'index.php') ? 'bg-surface-container text-primary border-l-2 border-primary' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' ?>">
                <span class="material-symbols-outlined text-sm <?= ($currentPage === 'index.php') ? 'text-primary' : '' ?>" style="<?= ($currentPage === 'index.php') ? "font-variation-settings: 'FILL' 1;" : '' ?>">calendar_month</span>
                <span>Tape Chart &amp; Front Desk</span>
            </a>
            
            <a href="index.php#reservations-table" class="flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-xs text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-sm">book_online</span>
                <span>Reservations List</span>
            </a>

            <a href="clients.php" class="flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-xs transition-colors <?= ($currentPage === 'clients.php') ? 'bg-surface-container text-primary border-l-2 border-primary' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' ?>">
                <span class="material-symbols-outlined text-sm <?= ($currentPage === 'clients.php') ? 'text-primary' : '' ?>">badge</span>
                <span>Guest Directory</span>
            </a>

            <a href="rooms.php" class="flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-xs transition-colors <?= ($currentPage === 'rooms.php') ? 'bg-surface-container text-primary border-l-2 border-primary' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' ?>">
                <span class="material-symbols-outlined text-sm <?= ($currentPage === 'rooms.php') ? 'text-primary' : '' ?>">hotel</span>
                <span>Room Inventory Matrix</span>
            </a>

            </nav>
    </div>

    <!-- Sidebar Bottom: Current Shift Staff -->
    <div class="pt-4 border-t border-outline-variant/30 space-y-2">
        <div class="flex items-center gap-3 px-2 pt-2">
            <div class="w-8 h-8 rounded-full border border-primary/40 overflow-hidden relative flex-shrink-0 bg-surface-container-high flex items-center justify-center text-primary font-bold text-xs">
                HV
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-xs font-semibold text-on-surface truncate">Henri de Valois</span>
                <span class="text-[10px] text-secondary truncate">Director of Front Desk</span>
            </div>
        </div>
    </div>
</aside>
