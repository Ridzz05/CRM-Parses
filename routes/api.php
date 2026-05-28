<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\InboundWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');
Route::post('/webhooks/inbound', InboundWebhookController::class)->name('webhooks.inbound');
