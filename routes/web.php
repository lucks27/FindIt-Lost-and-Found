<?php

use Illuminate\Support\Facades\Route;

Route::get('/{section}/{vue_capture?}', function () {
    return view('admin');
})->where([
            'section' => 'admin|superadmin',
            'vue_capture' => '[\/\w\.-]*',
        ])->name('admin.login');

// Student SPA fallback
Route::get('/{vue_capture?}', function () {
    return view('welcome');
})->where('vue_capture', '[\/\w\.-]*');

