<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
class PasswordController extends Controller {
 public function forgot() { return view('auth.forgot'); }
 public function send(Request $r) {
  $r->validate(['email'=>'required|email']);
  Password::sendResetLink($r->only('email')); // same answer whether or not the email exists
  return back()->with('ok','Agar bu email ro‘yxatdan o‘tgan bo‘lsa, parolni tiklash havolasi yuborildi.');
 }
 public function showReset(Request $r, $token) { return view('auth.reset', ['token'=>$token,'email'=>$r->query('email')]); }
 public function reset(Request $r) {
  $r->validate(['token'=>'required','email'=>'required|email','password'=>'required|min:8|confirmed']);
  $s = Password::reset($r->only('email','password','password_confirmation','token'), function ($u, $p) {
   $u->forceFill(['password'=>$p,'remember_token'=>Str::random(60)])->save(); // 'hashed' cast hashes it
  });
  return $s === Password::PASSWORD_RESET
   ? redirect('/kirish')->with('ok','Parol yangilandi. Endi kiring.')
   : back()->withErrors(['email'=>'Havola yaroqsiz yoki muddati tugagan.']);
 }
}
