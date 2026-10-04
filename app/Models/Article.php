<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Article extends Model {
 protected $guarded = [];
 protected $casts = ['published_at'=>'date'];
 public function user() { return $this->belongsTo(User::class); }
 public function journal() { return $this->belongsTo(Journal::class); }
}
