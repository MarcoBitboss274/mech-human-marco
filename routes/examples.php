<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('examples/show', function () {
    return Inertia::render('examples/Show');
})->name('examples.show');