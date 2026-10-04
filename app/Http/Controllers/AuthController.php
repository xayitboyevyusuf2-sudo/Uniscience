<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function showRegister() { return view('auth.register'); }
 public function register(Request $r) {
  $d = $r->validate(['first_name'=>'required|string|max:60','last_name'=>'required|string|max:60','student_id'=>'required|string|max:30|unique:users',
   'faculty'=>'required|string|max:120','direction'=>'required|in:'.implode(',',config('uniscience.fields')),
   'course'=>'required|integer|between:1,6','email'=>'required|email|unique:users',
   'password'=>'required|min:8|confirmed','consent'=>'accepted']);
  $u = User::create(Arr::except($d,'consent') + ['name'=>trim($d['first_name'].' '.$d['last_name']),'consent_at'=>now()]);
  Auth::login($u); return redirect('/portfel');
 }
 public function showLogin() { return view('auth.login'); }
 public function login(Request $r) {
  $c = $r->validate(['email'=>'required|email','password'=>'required']);
  if (!Auth::attempt($c + ['blocked'=>false], $r->boolean('remember'))) return back()->withErrors(['email'=>'Email yoki parol noto‘g‘ri']);
  $r->session()->regenerate(); return redirect()->intended('/portfel');
 }
 public function logout(Request $r) { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/'); }
}
