<?php

use App\Http\Controllers\EventsController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::get('/events', [EventsController::class, 'index'])->name('events.index');
Route::get('/events/data', [EventsController::class, 'data'])->name('events.data');
Route::get('/events/{event}', [EventsController::class, 'show'])->name('events.show');
Route::post('/events/{event}/replay', [EventsController::class, 'replay'])->name('events.replay');

// throttle ограничивает количество запросов в минуту с одного IP по ключу источника,
// чтобы даже с валидной подписью нельзя было заспамить очередь. Порог берётся
// из webhooks.rate_limit_per_minute (по умолчанию 60/мин).
Route::post('/webhook/{source}', WebhookController::class)
    ->whereIn('source', ['telephony', 'messenger', 'email'])
    ->middleware([
        'throttle:'.config('webhooks.rate_limit_per_minute', 60).',1',
        VerifyWebhookSignature::class,
    ])
    ->name('webhook.receive');

Route::redirect('/', '/events');
