export default (initialState = {}) => ({
    isAdding: Boolean(initialState.isAdding),
    editingId: initialState.editingId !== undefined && initialState.editingId !== null
        ? Number(initialState.editingId)
        : null,
    rulesMap: initialState.rulesMap || {},
    newForm: {
        name: initialState.newForm?.name || '',
        description: initialState.newForm?.description || '',
        barber_id: initialState.newForm?.barber_id || '',
        items: Array.isArray(initialState.newForm?.items) && initialState.newForm.items.length > 0
            ? [...initialState.newForm.items]
            : [''],
        slots_required: Number(initialState.newForm?.slots_required || 2),
        is_active: initialState.newForm?.is_active !== undefined ? Boolean(initialState.newForm.is_active) : true,
    },
    editForm: {
        name: initialState.editForm?.name || '',
        description: initialState.editForm?.description || '',
        barber_id: initialState.editForm?.barber_id || '',
        items: Array.isArray(initialState.editForm?.items) && initialState.editForm.items.length > 0
            ? [...initialState.editForm.items]
            : [''],
        slots_required: Number(initialState.editForm?.slots_required || 2),
        is_active: initialState.editForm?.is_active !== undefined ? Boolean(initialState.editForm.is_active) : true,
    },

    isEditing(ruleId) {
        return this.editingId === Number(ruleId);
    },

    startAdd() {
        this.editingId = null;
        this.newForm = {
            name: '',
            description: '',
            barber_id: '',
            items: [''],
            slots_required: 2,
            is_active: true,
        };
        this.isAdding = true;
    },

    cancelAdd() {
        this.isAdding = false;
    },

    startEdit(ruleId) {
        const rule = this.rulesMap[String(ruleId)] || this.rulesMap[Number(ruleId)] || null;
        if (!rule) return;

        this.isAdding = false;
        this.editingId = Number(ruleId);
        this.editForm = {
            name: rule.name || '',
            description: rule.description || '',
            barber_id: rule.barber_id ? String(rule.barber_id) : '',
            items: Array.isArray(rule.items) && rule.items.length > 0 ? [...rule.items] : [''],
            slots_required: Number(rule.slots_required || 2),
            is_active: Boolean(rule.is_active),
        };
    },

    cancelEdit() {
        this.editingId = null;
    },
});
