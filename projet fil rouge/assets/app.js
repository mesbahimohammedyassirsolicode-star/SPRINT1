document.addEventListener("DOMContentLoaded", () => {
    const clock = document.getElementById("liveClock");
    if (clock) {
        const tick = () => {
            const now = new Date();
            clock.textContent =
                now.toLocaleDateString("fr-FR") + " " + now.toLocaleTimeString("fr-FR", { hour12: false });
        };
        tick();
        setInterval(tick, 1000);
    }

    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll("a[data-confirm]").forEach((link) => {
        link.addEventListener("click", (event) => {
            if (!window.confirm(link.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});