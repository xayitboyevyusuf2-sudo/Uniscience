<?php
namespace App\Models;
use App\Services\Verifier;
use Illuminate\Database\Eloquent\Model;
class Journal extends Model {
 protected $guarded = [];
 protected $casts = ['listed_from'=>'date','listed_to'=>'date'];
 protected static function booted(): void { static::saving(fn($j) => $j->name_norm = Verifier::norm($j->name)); }
}
