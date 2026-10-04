<?php
namespace App\Services;
use App\Models\Journal;
/** CSV columns: issn (may be empty), name, field, tier, listed_from, listed_to, country, publisher. Never deletes. */
class JournalImporter {
 public static function fromFile(string $path): int {
  $f = fopen($path,'r'); $h = null; $n = 0;
  while (($row = fgetcsv($f)) !== false) {
   if (!$h) { $h = array_map(fn($x)=>trim(preg_replace('/^\xEF\xBB\xBF/','',$x)),$row); continue; }
   $d = array_combine($h, array_slice(array_pad($row,count($h),null),0,count($h)));
   if (empty($d['name']) || empty($d['listed_from']) || !in_array($d['tier'] ?? '',['A','B','C','D','E','X'])) continue;
   $issn = trim($d['issn'] ?? '') ?: null;
   $key = $issn ? ['issn'=>$issn,'listed_from'=>$d['listed_from']] : ['name_norm'=>Verifier::norm($d['name']),'listed_from'=>$d['listed_from']];
   $vals = ['name'=>$d['name'],'field'=>$d['field'] ?? '','tier'=>$d['tier'],'listed_to'=>($d['listed_to'] ?? '') ?: null,'country'=>$d['country'] ?? null,'publisher'=>$d['publisher'] ?? null];
   if ($issn) $vals['issn'] = $issn; // never erase an ISSN learned earlier
   Journal::updateOrCreate($key,$vals); $n++;
  }
  fclose($f); return $n;
 }
}
