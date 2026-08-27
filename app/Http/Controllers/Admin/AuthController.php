<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Get the web guard instance for session-based auth.
     */
    protected function guard()
    {
        return Auth::guard('web');
    }

    public function showLoginForm(Request $request)
    {
        if ($this->guard()->check()) {
            $user = $this->guard()->user();

            if ($user && $user->canAccessAdminPanel()) {
                return redirect()->route('admin.dashboard');
            }

            // Clear stale non-admin web sessions before showing admin login form.
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', 'Access denied. Please login with an admin account.');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        if ($this->guard()->check()) {
            $user = $this->guard()->user();

            if ($user && $user->canAccessAdminPanel()) {
                return redirect()->route('admin.dashboard');
            }

            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', 'Access denied. Please login with an admin account.');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->guard()->attempt($credentials, $request->boolean('remember'))) {
            if (! $this->guard()->user()->canAccessAdminPanel()) {
                $this->guard()->logout();

                return back()->withErrors(['email' => 'Access denied. Your role cannot access admin panel.']);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
