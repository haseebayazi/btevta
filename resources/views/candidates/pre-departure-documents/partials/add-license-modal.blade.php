<!-- Add License Modal -->
<div id="addLicenseModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeLicenseModal()"></div>

        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl transform transition-all sm:max-w-2xl sm:w-full mx-4">
            <form action="{{ route('candidates.licenses.store', $candidate) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-purple-600 to-purple-700 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-id-card text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-white">Add License</h3>
                                <p class="text-purple-200 text-sm">Driving or Professional License</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeLicenseModal()" class="text-white/80 hover:text-white transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- License Type -->
                        <div>
                            <label for="license_type" class="block text-sm font-medium text-gray-700 mb-2">
                                License Type <span class="text-red-500">*</span>
                            </label>
                            <select id="license_type" name="license_type" required
                                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                                <option value="">Select Type</option>
                                <option value="driving">Driving License</option>
                                <option value="professional">Professional License</option>
                            </select>
                        </div>

                        <!-- License Name -->
                        <div>
                            <label for="license_name" class="block text-sm font-medium text-gray-700 mb-2">
                                License Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="license_name"
                                   name="license_name"
                                   placeholder="e.g., Car License, RN License"
                                   required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                        </div>

                        <!-- License Number -->
                        <div>
                            <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">
                                License Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="license_number"
                                   name="license_number"
                                   placeholder="e.g., ABC123456"
                                   required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition font-mono">
                        </div>

                        <!-- License Category -->
                        <div id="categoryField">
                            <label for="license_category" class="block text-sm font-medium text-gray-700 mb-2">
                                Category
                            </label>
                            <input type="text"
                                   id="license_category"
                                   name="license_category"
                                   placeholder="e.g., B, C, D"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                            <p class="text-xs text-gray-500 mt-1">For driving licenses (B, C, D, etc.)</p>
                        </div>

                        <!-- Issuing Authority -->
                        <div>
                            <label for="issuing_authority" class="block text-sm font-medium text-gray-700 mb-2">
                                Issuing Authority
                            </label>
                            <input type="text"
                                   id="issuing_authority"
                                   name="issuing_authority"
                                   placeholder="e.g., Traffic Police, Medical Council"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                        </div>

                        <!-- Issue Date -->
                        <div>
                            <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Issue Date
                            </label>
                            <input type="date"
                                   id="issue_date"
                                   name="issue_date"
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                        </div>

                        <!-- Expiry Date -->
                        <div>
                            <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Expiry Date
                            </label>
                            <input type="date"
                                   id="expiry_date"
                                   name="expiry_date"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label for="license_file" class="block text-sm font-medium text-gray-700 mb-2">
                                License Copy (optional)
                            </label>
                            <input type="file"
                                   id="license_file"
                                   name="file"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                            <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG (Max: 5MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex justify-end gap-3 border-t border-gray-100">
                    <button type="button"
                            onclick="closeLicenseModal()"
                            class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Save License
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const licenseType = document.getElementById('license_type');
    const categoryField = document.getElementById('categoryField');

    if (licenseType && categoryField) {
        licenseType.addEventListener('change', function() {
            if (this.value === 'driving') {
                categoryField.style.display = 'block';
            } else {
                categoryField.style.display = 'none';
                document.getElementById('license_category').value = '';
            }
        });
    }
});
</script>
