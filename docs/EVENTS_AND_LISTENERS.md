# Event/Listener Architecture

This document describes the event-driven architecture used in the BTEVTA Overseas Employment Management System for real-time notifications and decoupled processing.

## Overview

The application uses Laravel's event system with WebSocket broadcasting for real-time updates. Events are dispatched when significant actions occur (status changes, new complaints, etc.) and can be consumed by:

1. **Listeners** - Server-side handlers for background processing
2. **Broadcasting** - Real-time WebSocket notifications to connected clients
3. **Notifications** - Email, SMS, or database notifications

---

## Available Events

### 1. CandidateStatusUpdated

**File:** `app/Events/CandidateStatusUpdated.php`

**Purpose:** Fired when a candidate's status changes in the workflow.

**Trigger Points:**
- `CandidateController@updateStatus`
- `ScreeningController@approve/reject`
- `TrainingController@complete`
- `DepartureController@recordDeparture`

**Payload:**
```php
[
    'candidate_id' => int,
    'candidate_name' => string,
    'btevta_id' => string,
    'old_status' => string,    // e.g., 'training'
    'new_status' => string,    // e.g., 'visa_process'
    'campus_id' => int,
    'campus_name' => string,
    'updated_at' => string,    // ISO 8601 format
    'message' => string,       // Human-readable message
]
```

**Broadcasting Channels:**
| Channel | Type | Description |
|---------|------|-------------|
| `candidates` | Public | All candidate status changes |
| `campus.{id}` | Private | Campus-specific changes |
| `admin` | Private | Admin notifications |

**Event Name:** `candidate.status.updated`

**Usage Example:**
```php
use App\Events\CandidateStatusUpdated;

// Dispatch when status changes
$oldStatus = $candidate->status;
$candidate->update(['status' => 'visa_process']);

CandidateStatusUpdated::dispatch($candidate, $oldStatus, 'visa_process');
```

**JavaScript Listener:**
```javascript
// Using Laravel Echo
Echo.channel('candidates')
    .listen('.candidate.status.updated', (e) => {
        console.log(`${e.candidate_name} moved to ${e.new_status}`);
        // Update UI accordingly
    });

// Private channel for campus
Echo.private(`campus.${campusId}`)
    .listen('.candidate.status.updated', (e) => {
        // Campus-specific handling
    });
```

---

### 2. DashboardStatsUpdated

**File:** `app/Events/DashboardStatsUpdated.php`

**Purpose:** Fired when dashboard statistics need to be refreshed in real-time.

**Trigger Points:**
- After candidate status changes
- After batch completion
- After remittance recording
- Scheduled jobs (e.g., daily statistics refresh)

**Payload:**
```php
[
    'stats' => [
        'total_candidates' => int,
        'active_training' => int,
        'visa_processing' => int,
        'departed' => int,
        'remittances_total' => float,
        // ... additional stats
    ],
    'campus_id' => int|null,   // null for global stats
    'updated_at' => string,    // ISO 8601 format
]
```

**Broadcasting Channels:**
| Channel | Type | Description |
|---------|------|-------------|
| `dashboard` | Public | Global dashboard stats |
| `dashboard.campus.{id}` | Public | Campus-specific dashboard |

**Event Name:** `stats.updated`

**Usage Example:**
```php
use App\Events\DashboardStatsUpdated;

// Refresh global dashboard
$stats = $this->dashboardService->getStats();
DashboardStatsUpdated::dispatch($stats);

// Refresh campus-specific dashboard
$campusStats = $this->dashboardService->getCampusStats($campusId);
DashboardStatsUpdated::dispatch($campusStats, $campusId);
```

**JavaScript Listener:**
```javascript
Echo.channel('dashboard')
    .listen('.stats.updated', (e) => {
        updateDashboardWidgets(e.stats);
    });

// Campus-specific
Echo.channel(`dashboard.campus.${campusId}`)
    .listen('.stats.updated', (e) => {
        updateCampusDashboard(e.stats);
    });
```

---

### 3. NewComplaintRegistered

**File:** `app/Events/NewComplaintRegistered.php`

**Purpose:** Fired when a new complaint is registered in the system.

**Trigger Points:**
- `ComplaintController@store`
- Public complaint form submission

**Payload:**
```php
[
    'complaint_id' => int,
    'ticket_number' => string,     // e.g., 'CMP-2025-0001'
    'candidate_name' => string,
    'category' => string,          // e.g., 'salary', 'working_conditions'
    'priority' => string,          // 'low', 'normal', 'high', 'urgent'
    'status' => string,            // 'open'
    'created_at' => string,        // ISO 8601 format
    'message' => string,           // Notification message
]
```

**Broadcasting Channels:**
| Channel | Type | Description |
|---------|------|-------------|
| `admin` | Private | Admin notifications |
| `complaints` | Private | Complaint handlers |

**Event Name:** `complaint.registered`

**Usage Example:**
```php
use App\Events\NewComplaintRegistered;

$complaint = Complaint::create($validatedData);
NewComplaintRegistered::dispatch($complaint);
```

**JavaScript Listener:**
```javascript
Echo.private('admin')
    .listen('.complaint.registered', (e) => {
        showNotification({
            title: 'New Complaint',
            message: e.message,
            type: e.priority === 'urgent' ? 'error' : 'warning',
        });
    });

Echo.private('complaints')
    .listen('.complaint.registered', (e) => {
        // Add to complaints list
        addComplaintToQueue(e);
    });
```

---

## Recommended Listeners

The following listeners are recommended for implementation to extend the event system:

### 1. SendCandidateStatusNotification

**Purpose:** Send email/SMS notifications when candidate status changes.

```php
// app/Listeners/SendCandidateStatusNotification.php
namespace App\Listeners;

use App\Events\CandidateStatusUpdated;
use App\Notifications\CandidateStatusChanged;

class SendCandidateStatusNotification
{
    public function handle(CandidateStatusUpdated $event): void
    {
        // Notify candidate
        $event->candidate->notify(new CandidateStatusChanged(
            $event->oldStatus,
            $event->newStatus
        ));

        // Notify relevant staff based on new status
        if ($event->newStatus === 'visa_process') {
            $event->candidate->visaPartner?->notify(...);
        }
    }
}
```

### 2. LogCandidateStatusChange

**Purpose:** Create audit log entries for compliance.

```php
// app/Listeners/LogCandidateStatusChange.php
namespace App\Listeners;

use App\Events\CandidateStatusUpdated;

class LogCandidateStatusChange
{
    public function handle(CandidateStatusUpdated $event): void
    {
        activity()
            ->performedOn($event->candidate)
            ->causedBy($event->updatedBy)
            ->withProperties([
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ])
            ->log('Status changed from ' . $event->oldStatus . ' to ' . $event->newStatus);
    }
}
```

### 3. AssignComplaintHandler

**Purpose:** Auto-assign complaints based on category and priority.

```php
// app/Listeners/AssignComplaintHandler.php
namespace App\Listeners;

use App\Events\NewComplaintRegistered;

class AssignComplaintHandler
{
    public function handle(NewComplaintRegistered $event): void
    {
        if ($event->complaint->priority === 'urgent') {
            // Assign to senior handler
            $handler = User::role('senior_complaint_handler')
                ->available()
                ->withLowestLoad()
                ->first();

            $event->complaint->update(['assigned_to' => $handler->id]);
        }
    }
}
```

---

## Broadcasting Configuration

### Server Setup (Laravel)

**1. Install Pusher or Laravel Websockets:**
```bash
composer require pusher/pusher-php-server
# or
composer require beyondcode/laravel-websockets
```

**2. Configure `.env`:**
```env
BROADCAST_DRIVER=pusher
# or reverb for Laravel 11+
BROADCAST_DRIVER=reverb

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

**3. Enable broadcasting in `bootstrap/app.php`:**
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php', // Add this
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
    )
    ->create();
```

**4. Define channel authorization in `routes/channels.php`:**
```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin', function ($user) {
    return $user->hasRole('admin');
});

Broadcast::channel('campus.{campusId}', function ($user, $campusId) {
    return $user->campus_id === (int) $campusId || $user->hasRole('admin');
});

Broadcast::channel('complaints', function ($user) {
    return $user->hasRole(['admin', 'complaint_handler']);
});
```

### Client Setup (JavaScript)

**1. Install Laravel Echo:**
```bash
npm install laravel-echo pusher-js
```

**2. Configure Echo in `resources/js/bootstrap.js`:**
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});
```

---

## Event Registration

### Laravel 11+ (Automatic Discovery)

Events and listeners are automatically discovered if they follow naming conventions:

```
app/Events/CandidateStatusUpdated.php
app/Listeners/SendCandidateStatusNotification.php
```

Register in listener class:
```php
class SendCandidateStatusNotification
{
    public function subscribe($events): void
    {
        $events->listen(
            CandidateStatusUpdated::class,
            [self::class, 'handle']
        );
    }
}
```

### Manual Registration (if needed)

In `app/Providers/AppServiceProvider.php`:
```php
use App\Events\CandidateStatusUpdated;
use App\Listeners\SendCandidateStatusNotification;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(
        CandidateStatusUpdated::class,
        SendCandidateStatusNotification::class
    );
}
```

---

## Testing Events

### Unit Testing

```php
use App\Events\CandidateStatusUpdated;
use Illuminate\Support\Facades\Event;

public function test_candidate_status_update_fires_event()
{
    Event::fake();

    $candidate = Candidate::factory()->create(['status' => 'training']);

    // Update status
    $candidate->update(['status' => 'visa_process']);
    CandidateStatusUpdated::dispatch($candidate, 'training', 'visa_process');

    Event::assertDispatched(CandidateStatusUpdated::class, function ($event) use ($candidate) {
        return $event->candidate->id === $candidate->id
            && $event->oldStatus === 'training'
            && $event->newStatus === 'visa_process';
    });
}
```

### Testing Broadcasting

```php
use Illuminate\Support\Facades\Broadcast;

public function test_candidate_status_broadcasts_to_correct_channels()
{
    Broadcast::spy();

    $candidate = Candidate::factory()->create();

    CandidateStatusUpdated::dispatch($candidate, 'training', 'visa_process');

    Broadcast::assertBroadcasted(CandidateStatusUpdated::class, function ($event) {
        return in_array('candidates', $event->broadcastOn());
    });
}
```

---

## Fallback Behavior

When WebSocket broadcasting fails or is unavailable:

1. **Database Notifications** - Events create database notification records
2. **Polling** - Frontend falls back to polling API endpoints
3. **Email Digest** - Batch notifications are sent via email

**Fallback Configuration:**
```php
// config/broadcasting.php
'fallback' => [
    'enabled' => true,
    'driver' => 'database',
    'poll_interval' => 30000, // 30 seconds
],
```

---

## Performance Considerations

1. **Queue Events** - Heavy listeners should be queued:
   ```php
   class SendCandidateStatusNotification implements ShouldQueue
   {
       use InteractsWithQueue;
   }
   ```

2. **Batch Broadcasting** - Use `broadcastAfterCommit` for database consistency:
   ```php
   public $afterCommit = true;
   ```

3. **Rate Limiting** - Implement debouncing for high-frequency events like dashboard stats.

---

## Monitoring

Monitor events using:
- Laravel Telescope (development)
- Laravel Horizon (queue monitoring)
- Application logs

**Log Event Dispatches:**
```php
// In AppServiceProvider
Event::listen('*', function ($eventName, array $data) {
    Log::channel('events')->info($eventName, [
        'data' => $data,
        'timestamp' => now(),
    ]);
});
```

---

*Document Version: 1.0*
*Last Updated: December 2025*
