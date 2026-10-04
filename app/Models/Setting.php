<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Setting extends Model {
 protected $primaryKey = 'key'; public $incrementing = false; protected $keyType = 'string'; protected $guarded = [];
 public static function get($k, $d = null) { return static::find($k)?->value ?? $d; }
 public static function put($k, $v, $uid) {
  $old = static::get($k);
  static::updateOrCreate(['key'=>$k], ['value'=>$v]);
  DB::table('setting_changes')->insert(['key'=>$k,'old'=>$old,'new'=>$v,'user_id'=>$uid,'created_at'=>now()]);
 }
}
