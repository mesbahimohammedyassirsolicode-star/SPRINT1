document.addEventListener('DOMContentLoaded', () => {
    /* ---- Reservation folio calculation (create / edit) ---- */
    const roomSelect = document.getElementById('id_chambre');
    const arrivalInput = document.getElementById('date_arrivee');
    const departureInput = document.getElementById('date_depart');
    const promoSelect = document.getElementById('id_promotion');
    const calculatedNightsSpan = document.getElementById('calculated-nights');
    const calculatedPriceSpan = document.getElementById('calculated-price');
    const totalAmountInput = document.getElementById('montant_total');

    function calculateTotal() {
        if (!arrivalInput || !departureInput || !roomSelect) return;

        const arrivalVal = arrivalInput.value;
        const departureVal = departureInput.value;

        if (arrivalVal && departureVal) {
            const date1 = new Date(arrivalVal);
            const date2 = new Date(departureVal);
            const diffTime = date2 - date1;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 0) {
                if (calculatedNightsSpan) {
                    calculatedNightsSpan.textContent = `${diffDays} night${diffDays > 1 ? 's' : ''}`;
                }

                const selectedOption = roomSelect.options[roomSelect.selectedIndex];
                const pricePerNight = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
                let total = diffDays * pricePerNight;

                if (promoSelect && promoSelect.selectedIndex > 0) {
                    const promoOption = promoSelect.options[promoSelect.selectedIndex];
                    const discount = parseFloat(promoOption.dataset.discount || 0);
                    if (discount > 0) {
                        total = total * ((100 - discount) / 100);
                    }
                }

                if (calculatedPriceSpan) {
                    calculatedPriceSpan.textContent = `${total.toFixed(2)} MAD`;
                }
                if (totalAmountInput) {
                    totalAmountInput.value = total.toFixed(2);
                }
            } else {
                if (calculatedNightsSpan) calculatedNightsSpan.textContent = 'Invalid dates';
                if (calculatedPriceSpan) calculatedPriceSpan.textContent = '0.00 MAD';
            }
        }
    }

    if (arrivalInput && departureInput && roomSelect) {
        arrivalInput.addEventListener('change', calculateTotal);
        departureInput.addEventListener('change', calculateTotal);
        roomSelect.addEventListener('change', calculateTotal);
        if (promoSelect) {
            promoSelect.addEventListener('change', calculateTotal);
        }
        calculateTotal();
    }

    /* ---- Reservations table filter (index.php) ---- */
    const tableSearch = document.getElementById('tableSearch');
    const statusFilter = document.getElementById('statusFilter');
    const dataTable = document.querySelector('.data-table');

    function filterTable() {
        if (!dataTable) return;
        const rows = dataTable.querySelectorAll('tbody tr');
        if (!rows.length) return;
        const searchTerm = tableSearch ? tableSearch.value.toLowerCase() : '';
        const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : '';

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const statusBadge = row.querySelector('.badge');
            const rowStatus = statusBadge ? statusBadge.textContent.toLowerCase().trim() : '';

            const matchesSearch = text.includes(searchTerm);
            const matchesStatus = !selectedStatus || rowStatus.includes(selectedStatus);

            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    /* ---- Global topbar search drives the data table ---- */
    const globalSearch = document.getElementById('globalSearchInput');
    if (globalSearch && tableSearch) {
        globalSearch.addEventListener('input', () => {
            tableSearch.value = globalSearch.value;
            filterTable();
        });
    }

    if (tableSearch) tableSearch.addEventListener('input', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (globalSearch) {
                globalSearch.focus();
                globalSearch.select();
            }
        }
    });

    /* ---- Rooms filter + search (rooms.php) ---- */
    const roomFilters = document.querySelectorAll('.room-filter');
    const roomSearch = document.getElementById('roomSearch');
    const roomCards = document.querySelectorAll('.room-card');

    function applyRoomFilters() {
        if (!roomCards.length) return;
        const activeFilter = document.querySelector('.room-filter[aria-pressed="true"]');
        const filterValue = activeFilter ? activeFilter.dataset.filter : 'all';
        const searchTerm = roomSearch ? roomSearch.value.toLowerCase() : '';

        roomCards.forEach(card => {
            const status = card.dataset.status ? card.dataset.status.toLowerCase() : '';
            const text = card.textContent.toLowerCase();

            const matchesStatus = filterValue === 'all' || status === filterValue.toLowerCase();
            const matchesSearch = text.includes(searchTerm);

            card.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
        });
    }

    roomFilters.forEach(btn => {
        btn.setAttribute('aria-pressed', btn.dataset.filter === 'all' ? 'true' : 'false');
        btn.addEventListener('click', () => {
            roomFilters.forEach(b => b.setAttribute('aria-pressed', 'false'));
            btn.setAttribute('aria-pressed', 'true');
            applyRoomFilters();
        });
    });

    if (roomSearch) roomSearch.addEventListener('input', applyRoomFilters);
});