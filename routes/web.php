<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ---------- Öffentliche Website ----------
Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/rechtliches/{doc}', [SiteController::class, 'legal'])->whereIn('doc', ['impressum', 'datenschutz', 'agb'])->name('legal');
Route::redirect('/impressum', '/rechtliches/impressum');
Route::redirect('/datenschutz', '/rechtliches/datenschutz');
Route::redirect('/agb', '/rechtliches/agb');

// Handy-Ansicht (QR-Code)
Route::get('/m/{slug}', [PublicPageController::class, 'show'])->where('slug', '[a-z0-9]{6,16}')->name('public.page');

// Bestellen per Handy
Route::post('/m/{slug}/bestellen', [OrderController::class, 'store'])->where('slug', '[a-z0-9]{6,16}')->middleware('throttle:20,1')->name('order.store');
Route::get('/bestellung/{code}', [OrderController::class, 'status'])->where('code', '[a-z0-9]{12}')->name('order.status');
Route::get('/bestellung/{code}/status', [OrderController::class, 'statusJson'])->where('code', '[a-z0-9]{12}')->middleware('throttle:60,1')->name('order.status.json');
Route::get('/abholung/{token}', [OrderController::class, 'board'])->name('board');
Route::get('/abholung/{token}/status', [OrderController::class, 'boardJson'])->middleware('throttle:60,1')->name('board.json');
Route::livewire('/kueche/{token}', Livewire\Orders::class)->name('kitchen');

// ---------- Bildschirm ----------
Route::get('/tv', [PlayerController::class, 'show'])->name('player');
Route::redirect('/player', '/tv');
Route::prefix('api/player')->group(function () {
    Route::post('register', [PlayerController::class, 'register'])->middleware('throttle:60,1');
    Route::get('{token}/content', [PlayerController::class, 'content'])->middleware('throttle:120,1');
    Route::get('{token}/agent', [PlayerController::class, 'agent'])->middleware('throttle:120,1');
    Route::post('{token}/ack', [PlayerController::class, 'ack'])->middleware('throttle:120,1');
    Route::post('{token}/screenshot', [PlayerController::class, 'screenshot'])->middleware('throttle:20,1');
});

// Android-App zum Herunterladen (Datei: storage/app/signo.apk)
Route::get('/app/signo.apk', function () {
    $f = storage_path('app/signo.apk');
    abort_unless(is_file($f), 404);
    return response()->download($f, 'signo.apk', ['Content-Type' => 'application/vnd.android.package-archive']);
})->name('apk');
Route::redirect('/app/android', '/app/signo.apk');

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook');

// ---------- Anmeldung ----------
Route::middleware('guest')->group(function () {
    Route::livewire('/registrieren', Livewire\Auth\Register::class)->name('register');
    Route::livewire('/anmelden', Livewire\Auth\Login::class)->name('login');
    Route::livewire('/passwort-vergessen', Livewire\Auth\ForgotPassword::class)->name('password.request');
    Route::livewire('/passwort-neu/{token}', Livewire\Auth\ResetPassword::class)->name('password.reset');
});
Route::redirect('/register', '/registrieren');
Route::redirect('/login', '/anmelden');

Route::post('/abmelden', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// ---------- Kundenbereich ----------
Route::middleware(['auth', 'org'])->group(function () {
    Route::redirect('/app', '/inhalte')->name('dashboard');
    Route::livewire('/inhalte', Livewire\Pages\Index::class)->name('pages.index');
    Route::livewire('/inhalte/{page}', Livewire\Pages\Edit::class)->name('pages.edit');
    Route::get('/inhalte/{page}/vorschau', [PublicPageController::class, 'preview'])->name('pages.preview');
    Route::livewire('/bildschirme', Livewire\Screens\Index::class)->name('screens.index');
    Route::livewire('/bildschirme/{screen}', Livewire\Screens\Edit::class)->name('screens.edit');
    Route::livewire('/bestellungen', Livewire\Orders::class)->name('orders');
    Route::livewire('/konto', Livewire\Account::class)->name('account');
    Route::livewire('/admin', Livewire\Admin\Organizations::class)->name('admin');
});
