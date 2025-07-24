<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle login request
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/home');
        }

        return back()->withErrors([
            'email' => 'Identifiants incorrects.',
        ])->withInput($request->except('password'));
    }

    // register form
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // Handle register request
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|regex:/^[0-9]{10}$/',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:client,merchant,courier,service_provider',
        ]);

        $user = User::create([
            'name' => $validated['nom'] . ' ' . $validated['prenom'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'type' => $validated['user_type'],
        ]);

        Auth::login($user);
        if ($user->type == 'merchant') {
            return redirect('/merchant/home');
        }
        elseif ($user->type == 'client') {
            return redirect('/client/home');
        }
        elseif ($user->type == 'service_provider') {
            return redirect('/service_provider/home');
        }
        elseif ($user->type == 'courier') {
            return redirect('/courier/home');
        }
        elseif ($user->type == 'admin') {
            return redirect('/admin/home');
        }
        else {
            return redirect('/home');
        }
    }

    // logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function getAllUsers()
    {
        $users = User::select('id', 'name', 'email', 'phone', 'type', 'created_at as registration_date')->get();
        return view('admin.users', ['users' => $users]);
    }
}
