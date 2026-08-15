<?php

use App\Http\Controllers\ShopifyAuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [ShopifyAuthController::class, 'install'])->name('shopify.install');
Route::get('/shopify/callback', [ShopifyAuthController::class, 'callback'])->name('shopify.callback');
Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
