<?php
namespace App\Models;
use App\Notifications\ResetPasswordUz;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable {
 use HasFactory, \Illuminate\Notifications\Notifiable;
 protected $guarded = [];
 protected $hidden = ['password','remember_token'];
 protected function casts(): array { return ['password'=>'hashed','blocked'=>'boolean']; }
 public function articles() { return $this->hasMany(Article::class); }
 public function sendPasswordResetNotification($token): void { $this->notify(new ResetPasswordUz($token)); }
}
