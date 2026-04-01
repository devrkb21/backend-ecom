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

    public function showLoginForm()
    {
        if ($this->guard()->check() && $this->guard()->user()->canAccessAdminPanel()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->guard()->attempt($credentials, $request->boolean('remember'))) {
            if (!$this->guard()->user()->canAccessAdminPanel()) {
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
