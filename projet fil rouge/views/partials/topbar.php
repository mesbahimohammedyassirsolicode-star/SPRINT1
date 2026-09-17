<?php
/**
 * Shared Top Navigation & System Bar
 * LuxeStay Grand Riviera & Spa PMS
 */
?>
<!-- Top Fixed Navigation Bar -->
<header class="fixed top-0 left-64 right-0 z-40 h-16 flex items-center justify-between px-6 border-b border-outline-variant/30 bg-surface-container-low/95 backdrop-blur-md shadow-2xl">
    <!-- Left: Property Switcher & Search -->
    <div class="flex items-center gap-6 flex-1 max-w-2xl">
        <!-- Property Badge -->
        <div class="flex items-center gap-2 pr-4 border-r border-outline-variant/30">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">villa</span>
            <div class="flex flex-col">
                <span class="text-[10px] text-secondary uppercase tracking-widest leading-none font-bold">Property Active</span>
                <span class="text-xs text-on-surface font-semibold truncate max-w-[200px]">LuxeStay Grand Riviera &amp; Spa</span>
            </div>
        </div>
        <!-- Search Bar -->
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-outline">
                <span class="material-symbols-outlined text-sm">search</span>
            </span>
            <input id="globalSearchInput" 
                   type="text" 
                   class="w-full pl-9 pr-14 py-1.5 bg-surface-container-lowest border border-outline-variant/40 rounded-lg text-xs text-on-surface placeholder:text-outline/70 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/40 transition-all" 
                   placeholder="Search guest name, folio #, suite or VIP tag... (Press ⌘K)">
            <span class="absolute inset-y-0 right-2 flex items-center">
                <kbd class="text-[9px] text-outline-variant bg-surface-container px-1.5 py-0.5 rounded border border-outline-variant/40 font-mono">⌘K</kbd>
            </span>
        </div>
    </div>

    <!-- Center-Right Operational Telemetry & Trailing Actions -->
    <div class="flex items-center gap-4">
        <!-- Live Clock Widget -->
        <div class="hidden xl:flex items-center gap-3 px-3 py-1 bg-surface-container-lowest/80 border border-outline-variant/25 rounded-lg text-on-surface-variant text-xs">
            <span class="material-symbols-outlined text-primary text-sm">schedule</span>
            <span id="liveClock" class="font-mono font-semibold text-on-surface">--:--:--</span>
        </div>

        <!-- Trailing Actions -->
        <a href="reservation_create.php" class="px-3 py-1.5 rounded-lg bg-primary text-on-primary-fixed font-semibold flex items-center gap-1.5 hover:bg-primary-fixed-dim transition-all text-xs shadow-sm active:scale-95">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">how_to_reg</span>
            <span>Express Check-In</span>
        </a>
    </div>
</header>
<script>
    // Live update clock
    setInterval(() => {
        const now = new Date();
        const clockEl = document.getElementById('liveClock');
        if (clockEl) {
            clockEl.textContent = now.toLocaleTimeString('en-GB') + ' CEST';
        }
    }, 1000);
</script>
