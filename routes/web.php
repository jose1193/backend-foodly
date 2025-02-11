<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy-policy', function () {
    return view('policies.privacy');
})->name('policy-privacy');

Route::get('/terms-conditions', function () {
    return view('policies.terms');
})->name('terms-conditions');

Route::get('/policy-cookies', function () {
    return view('policies.cookies');
})->name('policy-cookies');
