<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('app.subtitle') }}">
    <meta name="keywords" content="WASL, overseas employment, BTEVTA, vocational training, remittance management">
    <title>@yield('title', config('app.full_name') . ' - ' . config('app.tagline'))</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" type="image/png" href="/images/wasl-logo.svg">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        /* Mobile Responsiveness */
        @media (max-width: 640px) {
            .hide-mobile { display: none !important; }
            .mobile-full { width: 100% !important; }
            .mobile-stack { flex-direction: column !important; }
            .mobile-text-sm { font-size: 0.875rem !important; }
            .mobile-p-2 { padding: 0.5rem !important; }
        }

        /* Smooth collapse animation */
        [x-collapse] {
            overflow: hidden;
            transition: height 0.3s ease-out;
        }

        /* Touch-friendly buttons */
        @media (hover: none) and (pointer: coarse) {
            button, a.btn, .clickable {
                min-height: 44px;
                min-width: 44px;
            }
        }

        /* Safe area for notched devices */
        @supports (padding: max(0px)) {
            .safe-area-bottom {
                padding-bottom: max(1rem, env(safe-area-inset-bottom));
            }
            .safe-area-top {
                padding-top: max(0.75rem, env(safe-area-inset-top));
            }
        }

        /* Better scroll on mobile */
        .scroll-smooth {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* Prevent text selection on mobile nav */
        .no-select {
            -webkit-user-select: none;
            user-select: none;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50" x-data="{
    sidebarOpen: window.innerWidth >= 1024,
    profileDropdown: false,
    isMobile: window.innerWidth < 768,
    init() {
        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth < 768;
            if (window.innerWidth >= 1024) {
                this.sidebarOpen = true;
            }
        });
    }
}" x-init="init()">
    
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
                        <div class="h-10 w-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">W</span>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900">{{ config('app.full_name') }}</h1>
                            <p class="text-xs text-gray-600">{{ config('app.tagline') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Global Search -->
                <div class="flex-1 max-w-2xl mx-8" x-data="globalSearch()">
                    <div class="relative">
                        <div class="relative">
                            <input
                                type="text"
                                x-model="searchTerm"
                                @input.debounce.300ms="search()"
                                @keydown.escape="closeResults()"
                                @keydown.down.prevent="navigateDown()"
                                @keydown.up.prevent="navigateUp()"
                                @keydown.enter.prevent="selectResult()"
                                @focus="showResults = true"
                                placeholder="Search candidates, remittances, batches..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <span x-show="loading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i class="fas fa-spinner fa-spin text-gray-400"></i>
                            </span>
                        </div>

                        <!-- Search Results Dropdown -->
                        <div x-show="showResults && (Object.keys(results).length > 0 || searchTerm.length > 0)"
                             @click.away="closeResults()"
                             x-cloak
                             class="absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-2xl max-h-[600px] overflow-y-auto z-50 border border-gray-200">

                            <!-- No Results -->
                            <div x-show="searchTerm.length >= 2 && Object.keys(results).length === 0 && !loading"
                                 class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                                <p class="text-sm">No results found for "<span x-text="searchTerm"></span>"</p>
                            </div>

                            <!-- Results by Type -->
                            <template x-for="(group, type) in results" :key="type">
                                <div class="border-b border-gray-100 last:border-b-0">
                                    <!-- Group Header -->
                                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                        <h4 class="text-xs font-semibold text-gray-700 uppercase flex items-center">
                                            <i :class="group.icon" class="mr-2"></i>
                                            <span x-text="group.label"></span>
                                            <span class="ml-2 text-gray-500">(<span x-text="group.items.length"></span>)</span>
                                        </h4>
                                    </div>

                                    <!-- Group Items -->
                                    <div>
                                        <template x-for="(item, index) in group.items" :key="item.id">
                                            <a :href="item.url"
                                               :class="{ 'bg-blue-50': selectedIndex === getGlobalIndex(type, index) }"
                                               @mouseenter="selectedIndex = getGlobalIndex(type, index)"
                                               class="block px-4 py-3 hover:bg-gray-50 transition-colors duration-150">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="item.title"></p>
                                                        <p class="text-xs text-gray-600 truncate" x-text="item.subtitle"></p>
                                                    </div>
                                                    <div x-show="item.badge" class="ml-3">
                                                        <span :class="item.badge_class"
                                                              class="px-2 py-1 text-xs font-medium rounded-full text-white"
                                                              x-text="item.badge"></span>
                                                    </div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Footer -->
                            <div x-show="totalResults > 0" class="px-4 py-2 bg-gray-50 border-t border-gray-200 text-center">
                                <p class="text-xs text-gray-600">
                                    Showing <span x-text="totalResults"></span> result(s)
                                    <span class="text-gray-400 mx-2">•</span>
                                    Press <kbd class="px-1 py-0.5 bg-gray-200 rounded text-xs">↑↓</kbd> to navigate, <kbd class="px-1 py-0.5 bg-gray-200 rounded text-xs">Enter</kbd> to select
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative" x-data="{ open: false }">
                        @php
                            $unreadCount = auth()->user()->unreadNotifications()->count();
                            $notifications = auth()->user()->notifications()->latest()->limit(5)->get();
                        @endphp
                        <button @click="open = !open" class="relative text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bell text-xl"></i>
                            @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </button>

                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b flex justify-between items-center">
                                <h3 class="font-semibold text-gray-900">Notifications</h3>
                                @if($unreadCount > 0)
                                <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full">{{ $unreadCount }} unread</span>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                @forelse($notifications as $notification)
                                    <div class="block px-4 py-3 hover:bg-gray-50 border-b {{ $notification->read_at ? '' : 'bg-blue-50' }}">
                                        <p class="text-sm font-medium text-gray-900">{{ $notification->data['message'] ?? 'New notification' }}</p>
                                        <p class="text-xs text-gray-600">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                @empty
                                    <div class="px-4 py-3 text-center text-gray-500">
                                        <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                        <p class="text-sm">No notifications yet</p>
                                    </div>
                                @endforelse
                            </div>
                            <div class="px-4 py-2 border-t">
                                <a href="{{ route('notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View all notifications</a>
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
                             class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-3 border-b">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-600">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</p>
                                @if(auth()->user()->campus)
                                <p class="text-xs text-gray-500 mt-1"><i class="fas fa-building mr-1"></i>{{ auth()->user()->campus->name }}</p>
                                @endif
                            </div>
                            <div class="py-1">
                                <div class="px-4 py-2 text-xs text-gray-500 uppercase tracking-wider">Account</div>
                                <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-home mr-2 w-4"></i> Dashboard
                                </a>
                                <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user mr-2 w-4"></i> My Profile
                                </a>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-cog mr-2 w-4"></i> System Settings
                                </a>
                                <a href="{{ route('admin.activity-logs') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-history mr-2 w-4"></i> Activity Logs
                                </a>
                                @endif
                            </div>
                            <div class="border-t pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt mr-2 w-4"></i> Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar and Main Content -->
    <div class="flex pt-16">
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen && isMobile"
             x-cloak
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <!-- Sidebar -->
        <aside :class="{
                   'w-64': sidebarOpen,
                   'w-20': !sidebarOpen && !isMobile,
                   'w-0 -translate-x-full': !sidebarOpen && isMobile,
                   'translate-x-0': sidebarOpen
               }"
               class="bg-white shadow-lg fixed left-0 top-16 bottom-0 overflow-y-auto transition-all duration-300 z-40 lg:translate-x-0"
               :style="isMobile ? 'padding-bottom: 70px' : ''">

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

                    <!-- Tab 11: Remittance Management -->
                    <a href="{{ route('remittances.index') }}"
                       class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('remittances.*') || request()->routeIs('beneficiaries.*') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-money-bill-transfer text-lg w-6"></i>
                        <span x-show="sidebarOpen" class="font-medium">Remittance</span>
                    </a>
                </div>

                <!-- Admin Section -->
                @if(auth()->user()->isAdmin())
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
        <main :class="{
                  'ml-64': sidebarOpen && !isMobile,
                  'ml-20': !sidebarOpen && !isMobile,
                  'ml-0': isMobile,
                  'pb-20': isMobile
              }"
              class="flex-1 p-3 sm:p-6 transition-all duration-300 min-h-screen">
            
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
    <footer :class="{
                'ml-64': sidebarOpen && !isMobile,
                'ml-20': !sidebarOpen && !isMobile,
                'ml-0 pb-16': isMobile
            }"
            class="bg-white border-t py-4 sm:py-6 px-4 sm:px-6 text-center transition-all duration-300"
            x-show="!isMobile || !sidebarOpen">
        <div class="max-w-4xl mx-auto space-y-3">
            <!-- Main Footer Text -->
            <div class="flex items-center justify-center space-x-2 text-sm">
                <p class="text-gray-900 font-semibold">{{ config('app.full_name') }}</p>
                <span class="text-gray-400">|</span>
                <p class="text-gray-600">{{ config('app.tagline') }}</p>
            </div>

            <!-- Institutional Credits -->
            <div class="text-xs text-gray-500 space-y-1">
                <p>
                    <span class="font-medium">Product Conceived by:</span> {{ config('app.product_credits.conceived_by') }}
                    <span class="mx-2">•</span>
                    <span class="font-medium">Developed by:</span> {{ config('app.product_credits.developed_by') }}
                </p>
            </div>

            <!-- Contact & Copyright -->
            <div class="text-xs text-gray-500 pt-2 border-t">
                <p>
                    <span class="mr-3">{{ config('app.contact.email') }}</span>
                    <span>{{ config('app.contact.website') }}</span>
                </p>
                <p class="mt-2">&copy; {{ date('Y') }} All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <!-- Axios for AJAX (must load BEFORE usage) -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

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

        // Global AJAX setup (axios is now loaded above)
        window.axios = axios;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

        // Global Search Component
        function globalSearch() {
            return {
                searchTerm: '',
                results: {},
                showResults: false,
                loading: false,
                selectedIndex: 0,
                totalResults: 0,
                allItems: [],

                async search() {
                    const term = this.searchTerm.trim();

                    if (term.length < 2) {
                        this.results = {};
                        this.totalResults = 0;
                        this.allItems = [];
                        return;
                    }

                    this.loading = true;

                    try {
                        const response = await axios.get('/api/v1/global-search', {
                            params: { q: term }
                        });

                        if (response.data.success) {
                            this.results = response.data.results;
                            this.totalResults = response.data.total_results;
                            this.buildFlatList();
                            this.selectedIndex = 0;
                            this.showResults = true;
                        }
                    } catch (error) {
                        console.error('Search failed:', error);
                        this.results = {};
                        this.totalResults = 0;
                    } finally {
                        this.loading = false;
                    }
                },

                buildFlatList() {
                    this.allItems = [];
                    Object.keys(this.results).forEach(type => {
                        this.results[type].items.forEach(item => {
                            this.allItems.push({
                                type: type,
                                ...item
                            });
                        });
                    });
                },

                getGlobalIndex(type, index) {
                    let globalIndex = 0;
                    for (let t in this.results) {
                        if (t === type) {
                            return globalIndex + index;
                        }
                        globalIndex += this.results[t].items.length;
                    }
                    return globalIndex;
                },

                navigateDown() {
                    if (this.allItems.length === 0) return;
                    this.selectedIndex = Math.min(this.selectedIndex + 1, this.allItems.length - 1);
                },

                navigateUp() {
                    if (this.allItems.length === 0) return;
                    this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                },

                selectResult() {
                    if (this.allItems.length === 0 || this.selectedIndex >= this.allItems.length) return;
                    const selected = this.allItems[this.selectedIndex];
                    if (selected && selected.url) {
                        window.location.href = selected.url;
                    }
                },

                closeResults() {
                    this.showResults = false;
                    this.selectedIndex = 0;
                }
            }
        }
    </script>

    {{-- Global UI Helpers --}}
    <script>
        // Auto-loading states for forms
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form:not([data-no-loading])').forEach(form => {
                form.addEventListener('submit', function() {
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn && !btn.disabled) {
                        btn.dataset.originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                        btn.disabled = true;

                        // Safety fallback
                        setTimeout(() => {
                            if (btn.disabled && btn.dataset.originalHtml) {
                                btn.innerHTML = btn.dataset.originalHtml;
                                btn.disabled = false;
                            }
                        }, 15000);
                    }
                });
            });

            // Delete confirmations
            document.querySelectorAll('[data-confirm-delete]').forEach(el => {
                el.addEventListener('click', function(e) {
                    if (!confirm(el.dataset.confirmDelete || 'Are you sure you want to delete this?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            });

            document.querySelectorAll('form[data-confirm]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm(form.dataset.confirm || 'Are you sure?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        });
    </script>

    <!-- Real-time Notifications Component -->
    @include('components.realtime-notifications')

    <!-- Mobile Bottom Navigation -->
    <div x-show="isMobile" x-cloak
         class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-40 safe-area-bottom lg:hidden">
        <div class="flex justify-around py-2">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600">
                <i class="fas fa-home text-lg"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="{{ route('dashboard.candidates-listing') }}" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600">
                <i class="fas fa-users text-lg"></i>
                <span class="text-xs mt-1">Candidates</span>
            </a>
            <a href="{{ route('dashboard.training') }}" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600">
                <i class="fas fa-graduation-cap text-lg"></i>
                <span class="text-xs mt-1">Training</span>
            </a>
            <a href="{{ route('dashboard.reports') }}" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600">
                <i class="fas fa-chart-bar text-lg"></i>
                <span class="text-xs mt-1">Reports</span>
            </a>
            <button @click="sidebarOpen = !sidebarOpen" class="flex flex-col items-center px-3 py-2 text-gray-600 hover:text-blue-600">
                <i class="fas fa-bars text-lg"></i>
                <span class="text-xs mt-1">Menu</span>
            </button>
        </div>
    </div>

    @stack('scripts')
</body>
</html>