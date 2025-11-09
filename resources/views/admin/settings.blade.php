@extends('layouts.app')
@section('title', 'System Settings')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">System Settings</h2>

    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="#general" class="list-group-item list-group-item-action active" data-toggle="list">
                    <i class="fas fa-cog"></i> General Settings
                </a>
                <a href="#email" class="list-group-item list-group-item-action" data-toggle="list">
                    <i class="fas fa-envelope"></i> Email Settings
                </a>
                <a href="#security" class="list-group-item list-group-item-action" data-toggle="list">
                    <i class="fas fa-lock"></i> Security Settings
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.save') }}">
                        @csrf

                        <div id="general" class="settings-section">
                            <h4>General Settings</h4>
                            <div class="form-group">
                                <label>Application Name</label>
                                <input type="text" name="app_name" class="form-control" value="{{ config('app.name') }}">
                            </div>
                            <div class="form-group">
                                <label>Support Email</label>
                                <input type="email" name="support_email" class="form-control">
                            </div>
                        </div>

                        <div id="email" class="settings-section">
                            <h4>Email Settings</h4>
                            <div class="form-group">
                                <label>Mail Driver</label>
                                <select name="mail_driver" class="form-control">
                                    <option value="smtp">SMTP</option>
                                    <option value="sendmail">Sendmail</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>From Address</label>
                                <input type="email" name="mail_from_address" class="form-control">
                            </div>
                        </div>

                        <div id="security" class="settings-section">
                            <h4>Security Settings</h4>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="twoFactor" name="two_factor">
                                <label class="custom-control-label" for="twoFactor">
                                    Enable Two-Factor Authentication
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection