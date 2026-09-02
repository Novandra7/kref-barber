export default () => ({
    scroll(direction, refName = "sliderContainer") {
        const container = this.$refs[refName];
        if (!container) return;

        const firstCard = container.querySelector(":scope > *");
        if (!firstCard) return;

        const cardWidth = firstCard.offsetWidth;
        const computedStyle = window.getComputedStyle(container);
        const gap = parseFloat(computedStyle.gap) || 16;
        const step = cardWidth + gap;

        const maxScrollLeft = container.scrollWidth - container.clientWidth;
        const currentScroll = container.scrollLeft;

        if (direction > 0 && currentScroll >= maxScrollLeft - 5) {
            // Sudah di ujung kanan, klik next -> balik ke awal
            container.scrollTo({ left: 0, behavior: "smooth" });
            return;
        }

        if (direction < 0 && currentScroll <= 5) {
            // Sudah di ujung kiri, klik prev -> lompat ke akhir
            container.scrollTo({ left: maxScrollLeft, behavior: "smooth" });
            return;
        }

        container.scrollBy({
            left: direction * step,
            behavior: "smooth",
        });
    },
});