<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CRUD extends Controller
{
    /**
     * List the users (paginated).
     */
    public function index(): Response
    {
        // Paginate the users so the page stays fast when the table grows.
        $users = User::query()->latest()->paginate(10);

        return Inertia::render('Crud/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        // An empty User so the React form can share the same shape as edit.
        return Inertia::render('Crud/Create', [
            'user' => new User,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        User::create($validated);

        return redirect()->route('crud.index')->with('success', 'User created successfully.');
    }

    /**
     * Show a single user in read-only mode.
     */
    public function show(User $crud): Response
    {
        return Inertia::render('Crud/Show', [
            'user' => $crud,
        ]);
    }

    /**
     * Show the form for editing the given user.
     */
    public function edit(User $crud): Response
    {
        return Inertia::render('Crud/Edit', [
            'user' => $crud,
        ]);
    }

    /**
     * Update the given user.
     */
    public function update(Request $request, User $crud): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$crud->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$crud->id],
            'role' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ]);

        // Only replace the password when the admin types a new one.
        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        if ($request->hasFile('profile_picture')) {
            if ($crud->profile_picture) {
                Storage::disk('public')->delete($crud->profile_picture);
            }

            $validated['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        $crud->update($validated);

        return redirect()->route('crud.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the given user.
     */
    public function destroy(User $crud): RedirectResponse
    {
        if ($crud->profile_picture) {
            Storage::disk('public')->delete($crud->profile_picture);
        }

        $crud->delete();

        return redirect()->route('crud.index')->with('success', 'User deleted successfully.');
    }
}
