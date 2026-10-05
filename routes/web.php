<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/reyting', [PageController::class, 'rating']);
Route::get('/baza', [PageController::class, 'base']);
Route::get('/malumotnoma/{token}', [PageController::class, 'verify']);
Route::middleware('guest')->group(function () {
    Route::get('/royxat', [AuthController::class, 'showRegister']);
    Route::post('/royxat', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::view('/tasdiq-kutilmoqda', 'auth.pending')->name('registration.pending');
    Route::get('/kirish', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/kirish', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/parolni-tiklash', [PasswordController::class, 'forgot']);
    Route::post('/parolni-tiklash', [PasswordController::class, 'send'])->middleware('throttle:5,1');
    Route::get('/parolni-tiklash/{token}', [PasswordController::class, 'showReset']);
    Route::post('/parolni-tiklash/yangi', [PasswordController::class, 'reset'])->middleware('throttle:10,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/chiqish', [AuthController::class, 'logout']);
    Route::get('/profil', [ProfileController::class, 'edit']);
    Route::post('/profil', [ProfileController::class, 'update']);
    Route::get('/talaba/{user}', [ProfileController::class, 'show']);
    Route::get('/foto/{user}', [ProfileController::class, 'photo']);
    Route::get('/portfel', [ArticleController::class, 'portfolio']);
    Route::get('/yuklash', [ArticleController::class, 'create']);
    Route::post('/yuklash', [ArticleController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/maqola/{article}', [ArticleController::class, 'show']);
    Route::post('/malumotnoma', [PageController::class, 'certify']);
    Route::get('/admin', [AdminController::class, 'index']);
    Route::post('/admin/qaror/{article}', [AdminController::class, 'decide']);
    Route::post('/admin/import', [AdminController::class, 'import']);
    Route::post('/admin/sozlamalar', [AdminController::class, 'settings']);
    Route::post('/admin/foydalanuvchi/{user}/tasdiq', [AdminController::class, 'approveUser'])->name('admin.users.approval');
    Route::post('/admin/foydalanuvchi/{user}',[AdminController::class, 'role']);
});
