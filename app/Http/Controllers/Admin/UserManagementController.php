<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    // READ: Display the list of users
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'profile_picture', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();

        // Get session lifetime in minutes (default 120)
        $sessionLifetime = config('session.lifetime', 120);
        $sessionTimeout = now()->subMinutes($sessionLifetime)->timestamp;

        // Get active user IDs from sessions table
        $activeUserIds = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>', $sessionTimeout)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Add is_online flag to each user
        $users = $users->map(function ($user) use ($activeUserIds) {
            $user->is_online = in_array($user->id, $activeUserIds);
            return $user;
        });

        return inertia('Admin/ManageUsers', ['users' => $users]);
    }

    // CREATE: Store a new user
    public function store(Request $request)
    {
        // Security: Explicit admin check (defense in depth)
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only administrators can create user accounts.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'role' => ['required', 'string', 'in:staff-production,staff-marketing,treasurer'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ];

        // Handle profile picture upload - store as base64 in database
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageData = file_get_contents($image->getRealPath());
            $base64 = base64_encode($imageData);
            $mimeType = $image->getMimeType();
            $userData['profile_picture'] = 'data:' . $mimeType . ';base64,' . $base64;
        }

        User::create($userData);

        return to_route('admin.users.index')->with('success', 'User created successfully.');
    }

    // UPDATE: Update an existing user's details
    public function update(Request $request, User $user)
    {
        // Security: Explicit admin check (defense in depth)
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only administrators can update user accounts.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            'role' => ['required', 'string', 'in:admin,staff-production,staff-marketing,treasurer'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        // Prevent deactivating admin accounts
        if ($user->role === 'admin' && !$request->is_active) {
            return back()->with('error', 'Cannot deactivate an administrator account.');
        }
        $user->is_active = $request->is_active;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Handle profile picture upload - store as base64 in database
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageData = file_get_contents($image->getRealPath());
            $base64 = base64_encode($imageData);
            $mimeType = $image->getMimeType();
            $user->profile_picture = 'data:' . $mimeType . ';base64,' . $base64;
        }

        $user->save();
        
        // Refresh the user data to ensure profile_picture is included
        $user->refresh();
        
        return to_route('admin.users.index')->with('success', 'User updated successfully.');
    }
    
    // DELETE: Delete a user
    public function destroy(User $user)
    {
        // Security: Explicit admin check (defense in depth)
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only administrators can delete user accounts.');
        }

        // Add a check to prevent deleting yourself or another admin
        if ($user->role === 'admin' || $user->id === Auth::id()) {
             return back()->with('error', 'Cannot delete an administrator or yourself.');
        }
        $user->delete();
        return to_route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}