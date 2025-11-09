<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BTEVTA - Overseas Employment Management')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        .tab-active {
            background-color: #3b82f6;
            color: white;
        }
        .sidebar-item:hover {
            background-color: #f3f4f6;
        }
        .sidebar-item-active {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true, profileDropdown: false }">
    
    <!-- Top Navigation Bar -->
    <nav class="bg-white shadow-sm fixed top-0 left-0 right-0 z-50">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="flex items-center space-x-3">
                        <img src="/images/logo.png" alt="BTEVTA Logo" class="h-10 w-10" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%233b82f6%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2255%22 font-size=%2240%22 text-anchor=%22middle%22 fill=%22white%22%3EB%3C/text%3E%3C/svg%3E'">
                        <div>
                            <h1 class="text-lg font-bold text-gray-900">BTEVTA</h1>
                            <p class="text-xs text-gray-600">Overseas Employment Management</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b">
                                <h3 class="font-semibold text-gray-900">Notifications</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b">
                                    <p class="text-sm font-medium text-gray-900">New candidate registered</p>
                                    <p class="text-xs text-gray-600">5 minutes ago</p>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b">
                                    <p class="text-sm font-medium text-gray-900">Document expiring soon</p>
                                    <p class="text-xs text-gray-600">1 hour ago</p>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50">
                                    <p class="text-sm font-medium text-gray-900">Complaint resolved</p>
                                    <p class="text-xs text-gray-600">2 hours ago</p>
                                </a>
                            </div>
                            <div class="px-4 py-2 border-t">
                                <a href="#" class="text-sm text-blue-600 hover:text-blue-800">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="hidden md:block font-medium">{{ auth()->user()->name }}</span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-600">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</p>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user mr-2"></i> My Profile
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar and Main Content -->
    <div class="flex pt-16">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" 
               class="bg-white shadow-lg fixed left-0 top-16 bottom-0 overflow-y-auto transition-all duration-300 z-40">
            
            <nav class="p-4 space-y-2">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" 
                   class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'sidebar-item-active' : '' }}">
                    <i class="fas fa-home text-lg w-6"></i>
                    <span x-show="sidebarOpen" class="font-medium">Dashboard</span>
                </a>
                
                <!-- 10 Main Tabs -->
                <div class="pt-4">
                    <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Process Management
                    </p>
                    
                    <!-- Tab 1: Candidates Listing -->
                    <a href="{{ route('dashboard.candidates-listing') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.candidates-listing') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-list text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Candidates Listing</span>
                    </a>
                    
                    <!-- Tab 2: Screening -->
                    <a href="{{ route('dashboard.screening') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.screening') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-phone text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Screening</span>
                    </a>
                    
                    <!-- Tab 3: Registration -->
                    <a href="{{ route('dashboard.registration') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.registration') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-user-check text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Registration</span>
                    </a>
                    
                    <!-- Tab 4: Training -->
                    <a href="{{ route('dashboard.training') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.training') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-graduation-cap text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Training</span>
                    </a>
                    
                    <!-- Tab 5: Visa Processing -->
                    <a href="{{ route('dashboard.visa-processing') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.visa-processing') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-passport text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Visa Processing</span>
                    </a>
                    
                    <!-- Tab 6: Departure -->
                    <a href="{{ route('dashboard.departure') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.departure') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-plane-departure text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Departure</span>
                    </a>
                    
                    <!-- Tab 7: Correspondence -->
                    <a href="{{ route('dashboard.correspondence') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.correspondence') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-envelope text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Correspondence</span>
                    </a>
                    
                    <!-- Tab 8: Complaints -->
                    <a href="{{ route('dashboard.complaints') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.complaints') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-exclamation-triangle text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Complaints</span>
                    </a>
                    
                    <!-- Tab 9: Document Archive -->
                    <a href="{{ route('dashboard.document-archive') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.document-archive') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-archive text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Documents</span>
                    </a>
                    
                    <!-- Tab 10: Reports -->
                    <a href="{{ route('dashboard.reports') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.reports') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-chart-bar text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Reports</span>
                    </a>
                </div>
                
                <!-- Admin Section -->
                @if(auth()->user()->role === 'admin')
                <div class="pt-4 border-t">
                    <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Administration
                    </p>
                    
                    <a href="{{ route('admin.campuses.index') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg">
                        <i class="fas fa-building text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Campuses</span>
                    </a>
                    
                    <a href="{{ route('admin.oeps.index') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg">
                        <i class="fas fa-briefcase text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">OEPs</span>
                    </a>
                    
                    <a href="{{ route('admin.trades.index') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg">
                        <i class="fas fa-tools text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Trades</span>
                    </a>
                    
                    <a href="{{ route('admin.users.index') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg">
                        <i class="fas fa-users text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Users</span>
                    </a>
                    
                    <a href="{{ route('admin.settings') }}" 
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg">
                        <i class="fas fa-cog text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Settings</span>
                    </a>
                </div>
                @endif
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main :class="sidebarOpen ? 'ml-64' : 'ml-20'" 
              class="flex-1 p-6 transition-all duration-300">
            
            <!-- Alerts -->
            @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif
            
            @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif
            
            @if(session('warning'))
            <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <span>{{ session('warning') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-yellow-600 hover:text-yellow-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif
            
            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle mr-3 mt-1"></i>
                    <div>
                        <p class="font-semibold">Please fix the following errors:</p>
                        <ul class="list-disc list-inside mt-2">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Page Content -->
            @yield('content')
        </main>
    </div>
    
    <!-- Footer -->
    <footer :class="sidebarOpen ? 'ml-64' : 'ml-20'" 
            class="bg-white border-t py-4 px-6 text-center text-sm text-gray-600 transition-all duration-300">
        <p>&copy; {{ date('Y') }} BTEVTA - Board of Technical Education & Vocational Training Authority, Punjab. All rights reserved.</p>
    </footer>
    
    <!-- Scripts -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.bg-green-50, .bg-red-50, .bg-yellow-50').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);
        
        // CSRF Token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Global AJAX setup
        window.axios = axios;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    </script>
    
    <!-- Axios for AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    @stack('scripts')
</body>
</html>