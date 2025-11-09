<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Campus;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('campus')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $campuses = Campus::pluck('name', 'id');
        $roles = ['admin', 'campus_admin', 'oep_coordinator', 'visa_officer', 'trainer'];
        return view('admin.users.create', compact('campuses', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,campus_admin,oep_coordinator,visa_officer,trainer',
            'campus_id' => 'nullable|exists:campuses,id',
            'phone' => 'nullable|string',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        $user->load('campus');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $campuses = Campus::pluck('name', 'id');
        $roles = ['admin', 'campus_admin', 'oep_coordinator', 'visa_officer', 'trainer'];
        return view('admin.users.edit', compact('user', 'campuses', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,campus_admin,oep_coordinator,visa_officer,trainer',
            'campus_id' => 'nullable|exists:campuses,id',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validated['password'] ?? null) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'User deleted successfully!');
    }
}