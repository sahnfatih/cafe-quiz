<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\PresentationController;
use App\Livewire\Display\Board as DisplayBoard;
use Illuminate\Support\Facades\Route;

// Ana ekran
Route::get('/', function () {
    return view('home');
})->name('home');

// Dashboard admin alanı (Breeze)
Route::get('/dashboard', function () {
    return redirect()->route('admin.quizzes.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profil
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin quiz yönetimi (sadece giriş yapmış kullanıcılar)
Route::middleware('auth')->group(function () {
    Route::get('/admin/quizzes', [QuizController::class, 'index'])->name('admin.quizzes.index');
    Route::post('/admin/quizzes', [QuizController::class, 'store'])->name('admin.quizzes.store');
    Route::get('/admin/quizzes/{quiz}', [QuizController::class, 'show'])->name('admin.quizzes.show');
    Route::put('/admin/quizzes/{quiz}', [QuizController::class, 'update'])->name('admin.quizzes.update');
    Route::delete('/admin/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('admin.quizzes.destroy');
    Route::post('/admin/quizzes/{quiz}/questions', [QuizController::class, 'addQuestion'])->name('admin.quizzes.questions.store');
    Route::put('/admin/quizzes/{quiz}/questions/{question}', [QuizController::class, 'updateQuestion'])->name('admin.quizzes.questions.update');
    Route::delete('/admin/quizzes/{quiz}/questions/{question}', [QuizController::class, 'destroyQuestion'])->name('admin.quizzes.questions.destroy');

    // Sunumu başlat / oturum oluşturma
    Route::post('/admin/quizzes/{quiz}/start-session', [PresentationController::class, 'startSession'])
        ->name('admin.quizzes.startSession');
});

// Public Display (büyük ekran) - Livewire bileşeni
Route::get('/display/{code}', DisplayBoard::class)->name('display.show');

// Remote Controller (admin kumanda) - token zaten gizli olduğu için extra middleware yok
Route::get('/remote/{adminToken}',                             [PresentationController::class, 'remote'])->name('remote.show');
Route::post('/remote/{adminToken}/action',                     [PresentationController::class, 'control'])->name('remote.action');
Route::post('/remote/{adminToken}/lock',                       [PresentationController::class, 'lockAnswers'])->name('remote.lock');
Route::post('/remote/{adminToken}/timer',                      [PresentationController::class, 'setTimer'])->name('remote.timer');
Route::delete('/remote/{adminToken}/participants/{participant}',[PresentationController::class, 'kickParticipant'])->name('remote.kick');
Route::get('/remote/{adminToken}/export',                      [PresentationController::class, 'exportResults'])->name('remote.export');
Route::get('/remote/{adminToken}/analytics',                   [PresentationController::class, 'analytics'])->name('remote.analytics');
Route::get('/remote/{adminToken}/qr',                          [PresentationController::class, 'qrFullscreen'])->name('remote.qr');

// Participant (müşteri ekranı)
Route::get('/join/{code}', [PresentationController::class, 'joinForm'])->name('participant.join');
Route::post('/join/{code}', [PresentationController::class, 'registerParticipant'])->name('participant.register');
Route::get('/play/{code}/{participant}', [PresentationController::class, 'participantView'])->name('participant.play');
Route::post('/play/{code}/{participant}/answer', [PresentationController::class, 'submitAnswer'])
    ->name('participant.answer');

require __DIR__.'/auth.php';
