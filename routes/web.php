<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientCheckInController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/register')->name('home');

// Legacy alias for older links.
Route::redirect('/check-in', '/register');
Route::get('/ticket/{patient}', [PatientCheckInController::class, 'ticket'])->name('patient.ticket');
Route::get('/ticket/{patient}/json', [PatientCheckInController::class, 'ticketJson'])->name('patient.ticket.json');
Route::get('/ticket/{patient}/download', [PatientCheckInController::class, 'downloadTicket'])->name('patient.ticket.download');
Route::get('/doctors/by-category/{category}', [PatientCheckInController::class, 'doctorsByCategory'])
    ->name('patient.doctors_by_category');

// Spec-compatible aliases for patient registration.
Route::get('/register', [PatientCheckInController::class, 'create'])->name('patient.register');
Route::post('/register', [PatientCheckInController::class, 'store'])->name('patient.register.submit');

Route::prefix('staff')->name('staff.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('login', [StaffAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [StaffAuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('auth:web')->post('logout', [StaffAuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth:web')->group(function () {
    Route::redirect('/patients', '/admin/patients');
    Route::redirect('/doctors', '/admin/doctors');
    Route::redirect('/categories', '/admin/categories');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/run-scheduler', function () {
        Artisan::call('schedule:run');

        return response()->json(['status' => 'ok']);
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('patients/doctors/by-category/{category}', [PatientController::class, 'doctorsByCategory'])
            ->name('patients.doctors_by_category');

        Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
        Route::post('patients/clear-all', [PatientController::class, 'clearAll'])->name('patients.clear_all');
        Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
        Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
        Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

        Route::resource('categories', CategoryController::class);
        Route::resource('doctors', DoctorController::class);
        Route::resource('users', UserController::class)->except(['show']);
    });

    // Spec-compatible status update endpoint (staff only).
    Route::match(['POST', 'PUT'], '/queue/update-status/{id}/{status}', [QueueController::class, 'updateStatus'])
        ->name('queue.update_status');
});
