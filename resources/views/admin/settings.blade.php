@extends('layouts.app')
@section('title', 'System Settings')
@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">System Settings</h2>
        <p class="text-gray-600 mt-1">Manage application configuration and preferences</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
            <nav class="space-y-1 bg-white rounded-lg shadow-sm p-4">
                <button onclick="switchTab('general')" id="tab-general" class="tab-button w-full text-left px-4 py-3 rounded-lg flex items-center bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-cog mr-3"></i>
                    General Settings
                </button>
                <button onclick="switchTab('email')" id="tab-email" class="tab-button w-full text-left px-4 py-3 rounded-lg flex items-center text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-envelope mr-3"></i>
                    Email Settings
                </button>
                <button onclick="switchTab('security')" id="tab-security" class="tab-button w-full text-left px-4 py-3 rounded-lg flex items-center text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-lock mr-3"></i>
                    Security Settings
                </button>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
                    @csrf

                    <!-- General Settings Section -->
                    <div id="section-general" class="settings-section">
                        <div class="border-b border-gray-200 pb-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-cog mr-2 text-blue-600"></i>
                                General Settings
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Configure basic application settings</p>
                        </div>

                        <div class="space-y-6">
                            <!-- Application Name -->
                            <div>
                                <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Application Name
                                </label>
                                <input type="text" name="app_name" id="app_name"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="{{ config('app.name') }}" placeholder="BTEVTA Management System">
                            </div>

                            <!-- Support Email -->
                            <div>
                                <label for="support_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Support Email
                                </label>
                                <input type="email" name="support_email" id="support_email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="support@btevta.gov.pk">
                                <p class="mt-1 text-xs text-gray-500">Email address for user support inquiries</p>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings Section -->
                    <div id="section-email" class="settings-section hidden">
                        <div class="border-b border-gray-200 pb-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-envelope mr-2 text-blue-600"></i>
                                Email Settings
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Configure email delivery settings</p>
                        </div>

                        <div class="space-y-6">
                            <!-- Mail Driver -->
                            <div>
                                <label for="mail_driver" class="block text-sm font-medium text-gray-700 mb-2">
                                    Mail Driver
                                </label>
                                <select name="mail_driver" id="mail_driver"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="smtp">SMTP</option>
                                    <option value="sendmail">Sendmail</option>
                                </select>
                            </div>

                            <!-- From Address -->
                            <div>
                                <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-2">
                                    From Address
                                </label>
                                <input type="email" name="mail_from_address" id="mail_from_address"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="noreply@btevta.gov.pk">
                                <p class="mt-1 text-xs text-gray-500">Email address used as sender for outgoing emails</p>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings Section -->
                    <div id="section-security" class="settings-section hidden">
                        <div class="border-b border-gray-200 pb-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-lock mr-2 text-blue-600"></i>
                                Security Settings
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Configure security and authentication options</p>
                        </div>

                        <div class="space-y-6">
                            <!-- Two-Factor Authentication -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" id="two_factor" name="two_factor"
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <label for="two_factor" class="text-sm font-medium text-gray-700">
                                        Enable Two-Factor Authentication
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Require users to verify their identity using a second factor
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                            <i class="fas fa-save mr-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(section => {
        section.classList.add('hidden');
    });

    // Remove active state from all tabs
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('bg-blue-50', 'text-blue-700', 'font-medium');
        button.classList.add('text-gray-700', 'hover:bg-gray-50');
    });

    // Show selected section
    document.getElementById('section-' + tabName).classList.remove('hidden');

    // Add active state to selected tab
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.add('bg-blue-50', 'text-blue-700', 'font-medium');
    activeTab.classList.remove('text-gray-700', 'hover:bg-gray-50');
}
</script>
@endsection
