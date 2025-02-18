<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PortierController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AccessCardController;
use App\Http\Controllers\TicketCategoryController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/utilisateurs', [UserController::class, 'index']);
    Route::get('/utilisateurs/me', [UserController::class, 'me']);
    Route::post('/utilisateurs', [UserController::class, 'store']);
    Route::delete('/utilisateurs/{id}', [UserController::class, 'destroy']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::get('/events/{id}', [EventController::class, 'show']);

    Route::apiResource('/roles', RoleController::class);
    Route::apiResource('/clubs', ClubController::class)->except(['update']);
    Route::put('/clubs/{id}', [ClubController::class, 'update']);
    Route::apiResource('/ticket-categories', TicketCategoryController::class);
    Route::apiResource('/tickets', TicketController::class);
    Route::apiResource('/locations', LocationController::class);
    Route::post('/generate-tickets', [TicketController::class, 'generateTickets']);
    Route::prefix('cartes')->group(function () {
        Route::get('/', [AccessCardController::class, 'index']);
        Route::get('/{card}', [AccessCardController::class, 'show']);
        Route::post('/generate', [AccessCardController::class, 'generate']);
        Route::post('/block/{card}', [AccessCardController::class, 'block']);
        Route::post('/desactivate/{card}', [AccessCardController::class, 'desactivate']);
        Route::post('/activate/{card}', [AccessCardController::class, 'activate']);
        Route::post('/sell/{card}', [AccessCardController::class, 'sell']);
        Route::get('/stats', [AccessCardController::class, 'stats']);
        
    });
    Route::post('/portier/entrée', [PortierController::class, 'checkIn']);
    Route::get('/portier/total-entrées', [PortierController::class, 'myCheckIns']);
    Route::get('/events/{sale_id}/tickets', [TicketController::class, 'getUserTickets']);
});

Route::get('/events', [EventController::class, 'index']);
Route::get('/utilisateurs/{id}', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->put('/utilisateurs/{id}', [UserController::class, 'update']);

Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Route::post('/payment/callback', [TicketController::class, 'handleCallback'])->name('payment.callback');
Route::get('/get-payment-url/{saleId}', [TicketController::class, 'getPaymentUrl']);
Route::post('/payment/notify/{saleId}', [TicketController::class, 'handleNotify'])->name('payment.notify');
Route::get('/payment/callback/{saleId}', [TicketController::class, 'handleCallback'])->name('payment.callback');
Route::post('/purchase-ticket', [TicketController::class, 'purchaseTicket']);
Route::get('sales/stats', [SalesController::class, 'getSalesStats']);



