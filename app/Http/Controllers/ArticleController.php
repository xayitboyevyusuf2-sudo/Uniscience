<?php
namespace App\Http\Controllers;
use App\Models\Article;
use App\Notifications\ArticleStatus;
use App\Services\{Scorer,Verifier};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ArticleController extends Controller {
 public function portfolio() {
  $s = Scorer::for(auth()->user());
  return view('articles.portfolio', ['s'=>$s,'articles'=>auth()->user()->articles()->latest()->get()]);
 }
 public function create() { return view('articles.create'); }
 public function store(Request $r) {
  $d = $r->validate(['title'=>'required|string|max:500','journal_name'=>'required|string|max:255',
   'issn'=>['nullable','regex:/^\d{4}-\d{3}[\dXx]$/'],'published_at'=>'required|date|before_or_equal:today',
   'url'=>'required|url|max:500','position'=>'required|in:'.implode(',',array_keys(config('uniscience.positions'))),
   'coauthors'=>'nullable|string|max:500','abstract'=>'nullable|string|max:3000','pdf'=>'nullable|file|mimes:pdf|max:20480']);
  if (Article::where('url',$d['url'])->when($d['issn'] ?? null, fn($q,$i)=>$q->where('issn',$i))->exists())
   return back()->withInput()->withErrors(['url'=>'Bu maqola avval yuklangan. Qabul qilinmadi.']);
  $d['pdf_path'] = $r->file('pdf')?->store('articles'); // private disk, hashed name
  unset($d['pdf']);
  $a = Article::create($d + ['user_id'=>auth()->id(),'status'=>'pending']);
  [$st,$why,$j] = Verifier::run($a);
  $a->update(['status'=>$st,'reason'=>$why,'journal_id'=>$j?->id]);
  DB::table('review_logs')->insert(['article_id'=>$a->id,'decision'=>$st,'note'=>$why,'created_at'=>now(),'updated_at'=>now()]);
  try { auth()->user()->notify(new ArticleStatus($a->fresh())); } catch (\Throwable $e) { report($e); }
  return redirect("/maqola/{$a->id}")->with('ok',$why);
 }
 public function show(Article $article) {
  $u = auth()->user();
  abort_unless($article->user_id===$u->id || $u->role==='admin' || ($u->role==='moderator' && $article->user->faculty===$u->faculty), 403);
  $row = Scorer::for($article->user)['rows'][$article->id] ?? null;
  return view('articles.show', compact('article','row'));
 }
}
