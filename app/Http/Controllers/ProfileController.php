<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\Scorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ProfileController extends Controller {
 public function show(User $user) { // logged-in users only (see routes)
  $s = Scorer::for($user);
  return view('profile.show', ['u'=>$user,'s'=>$s,'rows'=>collect($s['rows'])->where('counted',true)->values()]);
 }
 public function edit() { return view('profile.edit', ['u'=>auth()->user()]); }
 public function update(Request $r) {
  $u = auth()->user();
  $d = $r->validate(['first_name'=>'required|string|max:60','last_name'=>'required|string|max:60','course'=>'required|integer|between:1,6',
   'bio'=>'nullable|string|max:1000','interests'=>'nullable|string|max:200','photo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
  $path = $u->photo_path;
  if ($r->boolean('remove_photo') && $path) { Storage::delete($path); $path = null; }
  if ($r->hasFile('photo')) { if ($path) Storage::delete($path); $path = $r->file('photo')->store('photos'); } // private disk, hashed name
  $u->update(['first_name'=>$d['first_name'],'last_name'=>$d['last_name'],'name'=>trim($d['first_name'].' '.$d['last_name']),
   'course'=>$d['course'],'bio'=>$d['bio'] ?? null,'interests'=>$d['interests'] ?? null,'photo_path'=>$path]);
  return redirect('/talaba/'.$u->id)->with('ok','Profil saqlandi');
 }
 public function photo(User $user) {
  abort_unless($user->photo_path && Storage::exists($user->photo_path), 404);
  return Storage::response($user->photo_path);
 }
}
