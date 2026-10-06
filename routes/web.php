<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\JournalSearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/reyting', [PageController::class, 'rating']);
Route::get('/baza', [PageController::class, 'base']);
Route::get('/api/jurnallar/qidiruv', JournalSearchController::class)->middleware(['auth', 'throttle:60,1'])->name('journals.search');
Route::get('/api/jurnallar/qidiruv', JournalSearchController::class)->middleware(['auth', 'throttle:60,1'])->name('journals.search');
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
    Route::get('/bildirishnomalar', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/bildirishnomalar/hammasi-oqildi', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/bildirishnomalar/{id}/oqildi', [NotificationController::class, 'markRead'])->whereUuid('id')->name('notifications.read');
    Route::get('/email/tasdiqlash', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/tasdiqlash', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/email/tasdiqlash/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::get('/profil', [ProfileController::class, 'edit'])->middleware('verified');
    Route::post('/profil', [ProfileController::class, 'update'])->middleware('verified');
    Route::get('/talaba/{user}', [ProfileController::class, 'show'])->middleware('verified');
    Route::get('/foto/{user}', [ProfileController::class, 'photo'])->middleware('verified');
    Route::get('/portfel', [ArticleController::class, 'portfolio'])->middleware('verified');
    Route::get('/yuklash', [ArticleController::class, 'create'])->middleware('verified');
    Route::post('/yuklash', [ArticleController::class, 'store'])->middleware(['verified', 'throttle:20,1']);
    Route::get('/maqola/{article}', [ArticleController::class, 'show'])->middleware('verified');
    Route::post('/malumotnoma', [PageController::class, 'certify'])->middleware('verified');
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/admin/audit', [AdminController::class, 'audit'])->name('admin.audit');
    Route::post('/admin/qaror/{article}', [AdminController::class, 'decide']);
    Route::post('/admin/import', [AdminController::class, 'import']);
    Route::post('/admin/sozlamalar', [AdminController::class, 'settings']);
    Route::get('/admin/foydalanuvchi/{user}/tahrir', [ProfileController::class, 'editUser']);
    Route::post('/admin/foydalanuvchi/{user}/tahrir', [ProfileController::class, 'updateUser']);
    Route::post('/admin/foydalanuvchi/{user}/tasdiq', [AdminController::class, 'approveUser'])->name('admin.users.approval');
    Route::post('/admin/foydalanuvchi/{user}', [AdminController::class, 'role']);
});
