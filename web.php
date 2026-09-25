<?php

use App\Http\Controllers\EventsController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::get('/events', [EventsController::class, 'index'])->name('events.index');
Route::get('/events/data', [EventsController::class, 'data'])->name('events.data');
Route::get('/events/{event}', [EventsController::class, 'show'])->name('events.show');
Route::post('/events/{event}/replay', [EventsController::class, 'replay'])->name('events.replay');

Route::post('/webhook/{source}', WebhookController::class)
    ->whereIn('source', ['telephony', 'messenger', 'email'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('webhook.receive');

Route::redirect('/', '/events');