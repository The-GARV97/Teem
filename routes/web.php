<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'role:Admin|SuperAdmin'])->name('dashboard');
Route::get('/employee/dashboard', [DashboardController::class, 'employee'])->middleware(['auth', 'role:Employee'])->name('employee.dashboard');
Route::get('/manager/dashboard', [DashboardController::class, 'manager'])->middleware(['auth', 'role:Manager'])->name('manager.dashboard');
Route::get('/superadmin/dashboard', [DashboardController::class, 'superadmin'])->middleware(['auth', 'role:SuperAdmin'])->name('superadmin.dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Public routes (all authenticated + verified users)
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

    // manage-employees permission required
    Route::middleware('permission:manage-employees')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('designations', DesignationController::class)->except(['show']);
    });

    // Public show route — defined after /create to avoid wildcard conflict
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
});

require __DIR__.'/auth.php';
