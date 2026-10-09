export default (allAvailableIds = [], cellAvailableMap = {}) => ({
    selectedIds: [],
    lastClicked: null,
    allAvailableIds: allAvailableIds,
    cellAvailableMap: cellAvailableMap,

    isSelected(id) {
        return this.selectedIds.includes(id);
    },

    handleSlotClick(id, barberId, dateStr, timeStr, event) {
        if (event.shiftKey && this.lastClicked !== null) {
            // Scope Shift + Click hanya pada barber & tanggal yang sama
            if (this.lastClicked.barberId === barberId && this.lastClicked.date === dateStr) {
                const cellKey = barberId + '_' + dateStr;
                const cellSlots = this.cellAvailableMap[cellKey] || [];

                const minTime = this.lastClicked.time < timeStr ? this.lastClicked.time : timeStr;
                const maxTime = this.lastClicked.time > timeStr ? this.lastClicked.time : timeStr;

                const rangeIds = cellSlots
                    .filter(s => s.time >= minTime && s.time <= maxTime)
                    .map(s => s.id);

                rangeIds.forEach(rId => {
                    if (!this.selectedIds.includes(rId)) {
                        this.selectedIds.push(rId);
                    }
                });

                this.lastClicked = { id, barberId, date: dateStr, time: timeStr };
                return;
            }
        }

        const idx = this.selectedIds.indexOf(id);
        if (idx !== -1) {
            this.selectedIds.splice(idx, 1);
        } else {
            this.selectedIds.push(id);
            this.lastClicked = { id, barberId, date: dateStr, time: timeStr };
        }
    },

    toggleCell(cellKey) {
        const cellSlots = this.cellAvailableMap[cellKey] || [];
        if (cellSlots.length === 0) return;
        const cellIds = cellSlots.map(s => s.id);
        const allSelected = cellIds.every(id => this.selectedIds.includes(id));
        if (allSelected) {
            this.selectedIds = this.selectedIds.filter(id => !cellIds.includes(id));
        } else {
            cellIds.forEach(id => {
                if (!this.selectedIds.includes(id)) {
                    this.selectedIds.push(id);
                }
            });
        }
    },

    isCellAllSelected(cellKey) {
        const cellSlots = this.cellAvailableMap[cellKey] || [];
        return cellSlots.length > 0 && cellSlots.every(s => this.selectedIds.includes(s.id));
    },

    isCellPartiallySelected(cellKey) {
        const cellSlots = this.cellAvailableMap[cellKey] || [];
        if (cellSlots.length === 0) return false;
        const count = cellSlots.filter(s => this.selectedIds.includes(s.id)).length;
        return count > 0 && count < cellSlots.length;
    },

    selectAll() {
        this.selectedIds = [...this.allAvailableIds];
    },

    deselectAll() {
        this.selectedIds = [];
        this.lastClicked = null;
    }
});
