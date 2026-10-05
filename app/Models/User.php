<?php

namespace App\Models;

use App\Notifications\ResetPasswordUz;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, \Illuminate\Notifications\Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'blocked' => 'boolean', 'gpa' => 'decimal:2', 'birth_date' => 'date'];
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordUz($token));
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->last_name, $this->first_name, $this->patronymic], fn ($part) => is_string($part) && trim($part) !== '')));
    }

    public function isStudent(): bool
    {
        return in_array($this->category, ['bakalavr', 'magistr'], true);
    }

    public function isMentor(): bool
    {
        return in_array($this->category, ['professor', 'tadqiqotchi'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isLeadership(): bool
    {
        return $this->role === 'rahbariyat';
    }
}
