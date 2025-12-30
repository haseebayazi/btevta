<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Change Password Required - {{ config('app.full_name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" type="image/png" href="/images/wasl-logo.svg">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-orange-500 to-red-600 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Security Alert Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-full shadow-lg mb-4">
                <i class="fas fa-shield-alt text-orange-500 text-5xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Password Change Required</h1>
            <p class="text-orange-100 text-lg">Security Compliance</p>
        </div>

        <!-- Password Change Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Warning Message -->
            <div class="mb-6 bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-orange-500 mr-3 mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Password change required</p>
                        <p class="text-sm mt-1">
                            For security compliance, you must change your password before accessing the system.
                            This is a one-time requirement.
                        </p>
                    </div>
                </div>
            </div>

            @if(session('warning'))
            <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                {{ session('warning') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>
                @foreach($errors->all() as $error)
                    <span>{{ $error }}</span><br>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('password.force-change.update') }}" class="space-y-5">
                @csrf

                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-400"></i>
                        </div>
                        <input type="password"
                               name="current_password"
                               id="current_password"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('current_password') border-red-500 @enderror"
                               placeholder="Enter your current password"
                               required
                               autofocus>
                    </div>
                    @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password"
                               name="password"
                               id="password"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('password') border-red-500 @enderror"
                               placeholder="Enter your new password"
                               required>
                    </div>
                    @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Confirm your new password"
                               required>
                    </div>
                </div>

                <!-- Password Requirements -->
                <div class="bg-gray-50 rounded-lg p-4 text-sm">
                    <p class="font-semibold text-gray-700 mb-2">Password Requirements:</p>
                    <ul class="text-gray-600 space-y-1">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2 text-xs"></i>
                            At least 12 characters long
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2 text-xs"></i>
                            At least one uppercase letter (A-Z)
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2 text-xs"></i>
                            At least one lowercase letter (a-z)
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2 text-xs"></i>
                            At least one number (0-9)
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2 text-xs"></i>
                            At least one special character (!@#$%^&*)
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2 text-xs"></i>
                            Must be different from current password
                        </li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                    <i class="fas fa-key mr-2"></i>
                    Change Password & Continue
                </button>
            </form>

            <!-- Logout Option -->
            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        Sign out instead
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-orange-100 text-sm">
            <p><i class="fas fa-shield-alt mr-1"></i> Security Compliance Required</p>
            <p class="text-xs mt-1 text-orange-200">This action is logged for audit purposes.</p>
        </div>
    </div>

</body>
</html>
