<div
    x-data="{
        active: {{ $active ? 'true' : 'false' }},
        busy: false,
        async toggle() {
            if (this.busy) return;
            const prev = this.active;
            this.active = !prev;
            this.busy = true;
            try {
                const res = await fetch('{{ $action }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) {
                    this.active = prev;
                } else {
                    const data = await res.json();
                    if (typeof data.is_active === 'boolean') {
                        this.active = data.is_active;
                    }
                }
            } catch (e) {
                this.active = prev;
            } finally {
                this.busy = false;
            }
        }
    }"
    class="inline-flex items-center"
>
    <button
        type="button"
        @click="toggle()"
        role="switch"
        :aria-checked="active.toString()"
        aria-label="{{ $label ?? 'Toggle status' }}"
        :title="active ? 'Active — klik untuk nonaktifkan' : 'Inactive — klik untuk aktifkan'"
        :disabled="busy"
        :class="active ? 'bg-green-400' : 'bg-gray-200'"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] transition-colors duration-200 ease-in-out focus:outline-none disabled:cursor-wait disabled:opacity-75"
    >
        <span
            :class="active ? 'translate-x-5' : 'translate-x-0.5'"
            class="inline-block h-4 w-4 transform rounded-full border-2 border-gray-900 bg-white transition-transform duration-200 ease-in-out"
        ></span>
    </button>
</div>
