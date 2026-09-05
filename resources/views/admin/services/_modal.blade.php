<div id="service-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto overflow-x-hidden p-4">
    <div class="relative max-h-full w-full max-w-md p-4">
        <div class="relative w-full rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
            <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                <h3 id="service-modal-title" class="text-lg font-medium text-heading">Add Service</h3>
                <button type="button" class="ms-auto inline-flex h-9 w-9 items-center justify-center rounded-base bg-transparent text-sm text-body hover:bg-neutral-tertiary hover:text-heading" data-modal-hide="service-modal">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            <form id="service-form" action="{{ route('admin.services.store') }}" method="POST">
                @csrf
                <input id="service-method" type="hidden" name="_method" value="">

                <div class="space-y-4 py-4 md:py-6">
                    <div>
                        <label for="service-name" class="mb-2 block text-sm font-medium text-heading">Name</label>
                        <input type="text" name="name" id="service-name" required class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading shadow-xs focus:border-brand focus:ring-brand" placeholder="Enter service name">
                    </div>
                    <div>
                        <label for="service-price" class="mb-2 block text-sm font-medium text-heading">Price</label>
                        <input type="number" name="price" id="service-price" min="0" step="1000" required class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading shadow-xs focus:border-brand focus:ring-brand" placeholder="Enter service price">
                    </div>
                    <div>
                        <label for="service-category" class="mb-2 block text-sm font-medium text-heading">Category</label>
                        <input type="text" name="category" id="service-category" required class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading shadow-xs focus:border-brand focus:ring-brand" placeholder="Example: Haircut">
                    </div>
                    <div>
                        <label for="service-description" class="mb-2 block text-sm font-medium text-heading">Description</label>
                        <textarea name="description" id="service-description" rows="3" class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium px-3 py-2.5 text-sm text-heading shadow-xs focus:border-brand focus:ring-brand" placeholder="Explain this service"></textarea>
                    </div>
                    <div>
                        <label class="inline-flex cursor-pointer items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="service-is-active" class="peer sr-only" checked>
                            <div class="relative h-6 w-11 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            <span class="ms-3 text-sm font-medium text-heading">Active Status</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center space-x-4 border-t border-default pt-4 md:pt-6">
                    <button id="service-submit" type="submit" class="inline-flex items-center rounded-base border border-transparent bg-brand px-4 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-brand-strong focus:outline-none focus:ring-4 focus:ring-brand-medium">
                        <span id="service-submit-label">Add Service</span>
                    </button>
                    <button data-modal-hide="service-modal" type="button" class="rounded-base border border-default-medium bg-neutral-secondary-medium px-4 py-2.5 text-sm font-medium text-body shadow-xs hover:bg-neutral-tertiary-medium hover:text-heading focus:outline-none focus:ring-4 focus:ring-neutral-tertiary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                window.openCreateServiceModal = function () {
                    const form = document.getElementById('service-form');

                    form.reset();
                    form.action = @json(route('admin.services.store'));
                    document.getElementById('service-method').value = '';
                    document.getElementById('service-modal-title').textContent = 'Add Service';
                    document.getElementById('service-submit-label').textContent = 'Add Service';
                    document.getElementById('service-is-active').checked = true;
                };

                window.openEditServiceModal = function (button, service) {
                    const form = document.getElementById('service-form');

                    form.reset();
                    form.action = button.dataset.updateUrl;
                    document.getElementById('service-method').value = 'PUT';
                    document.getElementById('service-modal-title').textContent = 'Edit Service';
                    document.getElementById('service-submit-label').textContent = 'Save Changes';
                    document.getElementById('service-name').value = service.name ?? '';
                    document.getElementById('service-price').value = service.price ?? 0;
                    document.getElementById('service-category').value = service.category ?? '';
                    document.getElementById('service-description').value = service.description ?? '';
                    document.getElementById('service-is-active').checked = Boolean(Number(service.is_active));
                };
            </script>
        @endpush
    @endonce
</div>
