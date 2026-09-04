<div id="barber-modal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 items-center justify-center overflow-y-auto overflow-x-hidden p-4">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative w-full rounded-base border border-default bg-neutral-primary-soft p-4 shadow-sm md:p-6">
            <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                <h3 id="barber-modal-title" class="text-lg font-medium text-heading">
                    Add Barber
                </h3>
                <button type="button" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="barber-modal">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            <form id="barber-form" action="{{ route('admin.barbers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input id="barber-method" type="hidden" name="_method" value="">

                <div class="space-y-4 py-4 md:py-6">
                    <div>
                        <label for="barber-name" class="block mb-2 text-sm font-medium text-heading">Name</label>
                        <input type="text" name="name" id="barber-name" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="Enter barber name" required>
                    </div>
                    
                    <div>
                        <label for="barber-phone" class="block mb-2 text-sm font-medium text-heading">Phone</label>
                        <input type="text" inputmode="numeric" name="phone" id="barber-phone" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="Enter phone number" required>
                    </div>

                    <div>
                        <label for="barber-role" class="block mb-2 text-sm font-medium text-heading">Role</label>
                        <select id="barber-role" name="role" class="block w-full bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand px-3 py-2.5 shadow-xs placeholder:text-body" required>
                            <option value="" disabled selected>Select role</option>
                            <option value="owner">Owner</option>
                            <option value="senior">Senior</option>
                            <option value="junior">Junior</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2.5 text-sm font-medium text-heading" for="barber-photo">Barber Photo</label>
                        
                        <!-- Container Preview Photo -->
                        <div id="photo-preview-container" class="mb-3 hidden items-center gap-3">
                            <img id="photo-preview" src="" alt="Barber Photo Preview" class="h-14 w-14 rounded-full object-cover border border-default">
                            <span class="text-xs text-gray-500">Current photo</span>
                        </div>

                        <input name="photo" class="cursor-pointer bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full shadow-xs placeholder:text-body" aria-describedby="file_input_help" id="barber-photo" type="file" accept="image/*">
                        <p class="mt-1 text-xs text-gray-500" id="file_input_help">PNG, JPG or WEBP (MAX. 2MB). Leave blank if you don't want to change it.</p>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="barber-is-active" class="sr-only peer" checked>
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand"></div>
                            <span class="ms-3 text-sm font-medium text-heading">Active Status</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center space-x-4 border-t border-default pt-4 md:pt-6">
                    <button id="barber-submit" type="submit" class="inline-flex items-center text-white bg-brand hover:bg-brand-strong border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium rounded-base text-sm px-4 py-2.5 focus:outline-none">
                        <span id="barber-submit-label">Add Barber</span>
                    </button>
                    <button data-modal-hide="barber-modal" type="button" class="text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium rounded-base text-sm px-4 py-2.5 focus:outline-none">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                window.openCreateModal = function() {
                    const form = document.getElementById('barber-form');

                    form.reset();
                    form.action = @json(route('admin.barbers.store'));
                    document.getElementById('barber-method').value = '';
                    document.getElementById('barber-modal-title').textContent = 'Add Barber';
                    document.getElementById('barber-submit-label').textContent = 'Add Barber';
                    document.getElementById('barber-is-active').checked = true;
                };

                window.openEditModal = function(button, barber) {
                    const form = document.getElementById('barber-form');

                    form.reset();
                    form.action = button.dataset.updateUrl;
                    document.getElementById('barber-method').value = 'PUT';
                    document.getElementById('barber-modal-title').textContent = 'Edit Barber';
                    document.getElementById('barber-submit-label').textContent = 'Save Changes';
                    document.getElementById('barber-name').value = barber.name;
                    document.getElementById('barber-phone').value = barber.phone;
                    document.getElementById('barber-role').value = barber.role;
                    document.getElementById('barber-is-active').checked = Boolean(Number(barber.is_active));

                    // Tampilkan foto jika barber memiliki foto
                    const previewContainer = document.getElementById('photo-preview-container');
                    const previewImg = document.getElementById('photo-preview');

                    if (barber.photo) {
                        previewImg.src = `/storage/${barber.photo}`;
                        previewContainer.classList.remove('hidden');
                        previewContainer.classList.add('flex');
                    } else {
                        previewContainer.classList.add('hidden');
                        previewContainer.classList.remove('flex');
                    }
                };
            </script>
        @endpush
    @endonce
</div>