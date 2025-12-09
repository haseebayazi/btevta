@extends('layouts.app')
@section('title', 'System Settings')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-cogs text-primary"></i> System Settings</h2>
            <p class="text-muted">Configure application settings and preferences</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <!-- Settings Navigation -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-list"></i> Settings Menu</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#general" class="list-group-item list-group-item-action active" data-toggle="list">
                        <i class="fas fa-cog mr-2"></i> General Settings
                    </a>
                    <a href="#email" class="list-group-item list-group-item-action" data-toggle="list">
                        <i class="fas fa-envelope mr-2"></i> Email Configuration
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action" data-toggle="list">
                        <i class="fas fa-shield-alt mr-2"></i> Security Settings
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-toggle="list">
                        <i class="fas fa-bell mr-2"></i> Notifications
                    </a>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Quick Actions</h6>
                    <a href="{{ route('admin.activity-logs') }}" class="btn btn-outline-primary btn-sm btn-block mb-2">
                        <i class="fas fa-history"></i> View Activity Logs
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm btn-block">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-md-9">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf

                <div class="tab-content">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cog text-primary"></i> General Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Application Name</label>
                                            <input type="text" name="app_name" class="form-control"
                                                   value="{{ $settings['app_name'] ?? config('app.name') }}"
                                                   placeholder="Enter application name">
                                            <small class="form-text text-muted">This will appear in the header and emails</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Support Email</label>
                                            <input type="email" name="support_email" class="form-control"
                                                   value="{{ $settings['support_email'] ?? '' }}"
                                                   placeholder="support@example.com">
                                            <small class="form-text text-muted">Users will contact this email for support</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Default Timezone</label>
                                            <select name="timezone" class="form-control">
                                                <option value="Asia/Karachi" {{ ($settings['timezone'] ?? 'Asia/Karachi') === 'Asia/Karachi' ? 'selected' : '' }}>Asia/Karachi (PKT)</option>
                                                <option value="UTC" {{ ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' }}>UTC</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Date Format</label>
                                            <select name="date_format" class="form-control">
                                                <option value="Y-m-d">YYYY-MM-DD (2025-01-15)</option>
                                                <option value="d/m/Y">DD/MM/YYYY (15/01/2025)</option>
                                                <option value="M d, Y">Month DD, YYYY (Jan 15, 2025)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings -->
                    <div class="tab-pane fade" id="email">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-envelope text-primary"></i> Email Configuration</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Mail Driver</label>
                                            <select name="mail_driver" class="form-control">
                                                <option value="smtp" {{ ($settings['mail_driver'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                                <option value="sendmail" {{ ($settings['mail_driver'] ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                                <option value="log" {{ ($settings['mail_driver'] ?? '') === 'log' ? 'selected' : '' }}>Log (Testing)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>From Address</label>
                                            <input type="email" name="mail_from_address" class="form-control"
                                                   value="{{ $settings['mail_from_address'] ?? '' }}"
                                                   placeholder="noreply@example.com">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>From Name</label>
                                            <input type="text" name="mail_from_name" class="form-control"
                                                   value="{{ $settings['mail_from_name'] ?? config('app.name') }}"
                                                   placeholder="BTEVTA System">
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    SMTP server settings should be configured in the environment file (.env) for security reasons.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shield-alt text-primary"></i> Security Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="twoFactor"
                                               name="two_factor_enabled" value="1"
                                               {{ ($settings['two_factor_enabled'] ?? false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="twoFactor">
                                            <strong>Enable Two-Factor Authentication</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted ml-4">
                                        Require users to verify their identity with a second factor when logging in.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="forcePasswordChange"
                                               name="force_password_change" value="1"
                                               {{ ($settings['force_password_change'] ?? false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="forcePasswordChange">
                                            <strong>Force Password Change on First Login</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted ml-4">
                                        New users must change their password when they first log in.
                                    </small>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Session Timeout (minutes)</label>
                                            <input type="number" name="session_timeout" class="form-control"
                                                   value="{{ $settings['session_timeout'] ?? 120 }}" min="5" max="1440">
                                            <small class="form-text text-muted">Users will be logged out after this period of inactivity</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Maximum Login Attempts</label>
                                            <input type="number" name="max_login_attempts" class="form-control"
                                                   value="{{ $settings['max_login_attempts'] ?? 5 }}" min="3" max="10">
                                            <small class="form-text text-muted">Account will be locked after this many failed attempts</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="tab-pane fade" id="notifications">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bell text-primary"></i> Notification Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="emailNotifications"
                                               name="email_notifications" value="1"
                                               {{ ($settings['email_notifications'] ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="emailNotifications">
                                            <strong>Enable Email Notifications</strong>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="documentExpiryAlerts"
                                               name="document_expiry_alerts" value="1"
                                               {{ ($settings['document_expiry_alerts'] ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="documentExpiryAlerts">
                                            <strong>Document Expiry Alerts</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted ml-4">
                                        Send alerts when documents are about to expire.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>Document Expiry Warning Days</label>
                                    <input type="number" name="expiry_warning_days" class="form-control" style="max-width: 200px;"
                                           value="{{ $settings['expiry_warning_days'] ?? 30 }}" min="7" max="90">
                                    <small class="form-text text-muted">Days before expiry to start sending warnings</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save All Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
