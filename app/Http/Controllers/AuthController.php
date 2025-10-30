<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showRegister() { return view('auth.register'); }

    public function register(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        Auth::login($user);
        return redirect()->route('home')->with('success', 'Registration successful');
    }

    public function showLogin() { return view('auth.login'); }

    public function login(Request $req)
    {
        $creds = $req->validate(['email'=>'required|email','password'=>'required']);
        if (Auth::attempt($creds, $req->boolean('remember'))) {
            $req->session()->regenerate();
            return redirect()->intended(route('home'));
        }
        return back()->withErrors(['email'=>'Invalid credentials'])->onlyInput('email');
    }

    public function logout(Request $req)
    {
        Auth::logout();
        $req->session()->invalidate();
        $req->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function profile() { return view('auth.profile', ['user'=>Auth::user()]); }

    public function updateProfile(Request $req)
    {
        $user = Auth::user();
        $data = $req->validate([
            'name'=>'required|string|max:255',
            'email'=>"required|email|unique:users,email,{$user->id}",
            'password'=>'nullable|confirmed|min:6'
        ]);
        $user->fill($data);
        if (!empty($data['password'])) $user->password = Hash::make($data['password']);
        $user->save();
        return back()->with('success', 'Profile updated');
    }
}
