<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Get user and check if active (if is_active column exists)
        $user = Auth::user();

        // Check if user has is_active column and if it's false
        if (isset($user->is_active) && !$user->is_active) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact administrator.',
            ]);
        }

        // Log the login activity using Laravel's built-in Log
        Log::info('User logged in to GAPURA Training System', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role ?? 'staff',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        // Fire login event
        Event::dispatch('user.login', $user);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Log the logout activity
        if (Auth::check()) {
            $user = Auth::user();

            Log::info('User logged out from GAPURA Training System', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role ?? 'staff',
                'ip_address' => $request->ip(),
                'timestamp' => now()
            ]);

            // Fire logout event
            Event::dispatch('user.logout', $user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
