{{-- Real-time Notifications Component --}}
{{-- Add this to your layout to enable real-time notifications --}}

<div x-data="realtimeNotifications()" x-init="init()" class="fixed bottom-4 right-4 z-50 space-y-2 max-w-sm">
    <!-- Notification Toast Stack -->
    <template x-for="notification in notifications" :key="notification.id">
        <div x-show="notification.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-full"
             :class="{
                 'bg-blue-500': notification.type === 'info',
                 'bg-green-500': notification.type === 'success',
                 'bg-yellow-500': notification.type === 'warning',
                 'bg-red-500': notification.type === 'error'
             }"
             class="rounded-lg shadow-lg p-4 text-white flex items-start space-x-3">
            <!-- Icon -->
            <div class="flex-shrink-0">
                <i :class="{
                    'fas fa-info-circle': notification.type === 'info',
                    'fas fa-check-circle': notification.type === 'success',
                    'fas fa-exclamation-triangle': notification.type === 'warning',
                    'fas fa-times-circle': notification.type === 'error'
                }" class="text-xl"></i>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm" x-text="notification.title"></p>
                <p class="text-sm opacity-90 mt-1" x-text="notification.message"></p>
                <p class="text-xs opacity-75 mt-1" x-text="notification.time"></p>
            </div>
            <!-- Close Button -->
            <button @click="dismissNotification(notification.id)"
                    class="flex-shrink-0 text-white opacity-75 hover:opacity-100">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </template>

    <!-- Connection Status (shown when disconnected) -->
    <div x-show="!connected" x-cloak
         class="bg-gray-800 rounded-lg shadow-lg p-3 text-white flex items-center space-x-2">
        <i class="fas fa-wifi text-yellow-400"></i>
        <span class="text-sm">Reconnecting...</span>
    </div>
</div>

<!-- Notification Sound (optional) -->
<audio id="notificationSound" preload="auto">
    <source src="/sounds/notification.mp3" type="audio/mpeg">
</audio>

@push('scripts')
<script>
function realtimeNotifications() {
    return {
        notifications: [],
        connected: true,
        notificationId: 0,
        pollingInterval: null,
        lastCheck: Date.now(),

        init() {
            // Start polling for notifications (WebSocket alternative)
            this.startPolling();

            // Listen for custom events from the page
            window.addEventListener('show-notification', (e) => {
                this.addNotification(e.detail);
            });

            // Check for stored notifications on page load
            this.checkStoredNotifications();
        },

        startPolling() {
            // Poll every 15 seconds for new notifications
            this.pollingInterval = setInterval(() => {
                this.fetchNotifications();
            }, 15000);

            // Initial fetch
            this.fetchNotifications();
        },

        async fetchNotifications() {
            try {
                const response = await axios.get('/api/v1/notifications/unread', {
                    params: { since: this.lastCheck }
                });

                if (response.data && response.data.notifications) {
                    response.data.notifications.forEach(n => {
                        if (!this.hasNotification(n.id)) {
                            this.addNotification({
                                id: n.id,
                                type: n.type || 'info',
                                title: n.title || 'New Notification',
                                message: n.message,
                                time: n.created_at
                            });
                        }
                    });
                }

                this.lastCheck = Date.now();
                this.connected = true;
            } catch (error) {
                console.log('Notification fetch failed:', error);
                this.connected = false;
            }
        },

        hasNotification(id) {
            return this.notifications.some(n => n.serverId === id);
        },

        addNotification(data) {
            const notification = {
                id: ++this.notificationId,
                serverId: data.id,
                type: data.type || 'info',
                title: data.title || 'Notification',
                message: data.message || '',
                time: this.formatTime(data.time),
                visible: true
            };

            this.notifications.unshift(notification);

            // Play sound if available
            this.playSound();

            // Auto-dismiss after 8 seconds
            setTimeout(() => {
                this.dismissNotification(notification.id);
            }, 8000);

            // Keep only last 5 notifications
            if (this.notifications.length > 5) {
                this.notifications = this.notifications.slice(0, 5);
            }
        },

        dismissNotification(id) {
            const notification = this.notifications.find(n => n.id === id);
            if (notification) {
                notification.visible = false;
                setTimeout(() => {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }, 300);
            }
        },

        formatTime(time) {
            if (!time) return 'Just now';
            const date = new Date(time);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return date.toLocaleDateString();
        },

        playSound() {
            const sound = document.getElementById('notificationSound');
            if (sound) {
                sound.currentTime = 0;
                sound.play().catch(() => {}); // Ignore autoplay restrictions
            }
        },

        checkStoredNotifications() {
            // Check sessionStorage for any queued notifications
            const stored = sessionStorage.getItem('pendingNotifications');
            if (stored) {
                try {
                    const notifications = JSON.parse(stored);
                    notifications.forEach(n => this.addNotification(n));
                    sessionStorage.removeItem('pendingNotifications');
                } catch (e) {}
            }
        }
    }
}

// Helper to trigger notifications from anywhere
window.showNotification = function(type, title, message) {
    window.dispatchEvent(new CustomEvent('show-notification', {
        detail: { type, title, message, time: new Date().toISOString() }
    }));
};
</script>
@endpush
