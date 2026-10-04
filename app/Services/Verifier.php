<?php
namespace App\Services;
use App\Models\{Article,Journal};
/** Rule-based check (FR-10..13). Returns [status, reason, ?Journal]. ISSN is optional: without it the journal is found by name. */
class Verifier {
 public static function run(Article $a): array {
  $byIssn = $a->issn ? Journal::where('issn',$a->issn)->get() : collect();
  $viaName = $byIssn->isEmpty();
  $c = $viaName ? Journal::where('name_norm', self::norm($a->journal_name))->get() : $byIssn;
  if ($c->isEmpty()) return ['manual','Jurnal OAK ro‘yxatidan topilmadi (nomi/ISSN bo‘yicha). Moderator tekshiradi',null];
  $in = $c->filter(fn($j)=>$j->listed_from->lte($a->published_at) && (!$j->listed_to || $j->listed_to->gte($a->published_at)));
  if ($in->isEmpty()) return ['rejected','Rad etildi: chiqqan sanada ro‘yxatda bo‘lmagan',null];
  if ($viaName && $in->count() > 1) return ['manual','Shu nomli bir nechta jurnal bor. Moderator tanlaydi',null];
  $j = $in->first();
  if (!$viaName && self::norm($a->journal_name) !== self::norm($j->name)) return ['manual','Jurnal nomi ma’lumotnomadagi nom bilan mos kelmadi',$j];
  $m = Article::where('user_id',$a->user_id)->where('created_at','>=',now()->startOfMonth())->count();
  if ($m >= 5) return ['manual','Bir oyda 5+ maqola yuklandi',$j];
  return ['approved','Tasdiqlandi'.($j->tier==='X' ? ' (ogohlantirish: xavfli jurnal, 0 ball)' : ''),$j];
 }
 public static function norm(string $s): string { return preg_replace('/[^\p{L}\p{N}]+/u','',mb_strtolower($s)); }
}
