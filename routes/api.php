<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/class/list', [\App\Http\Controllers\Api\ClassRoomSlotBooking::class, 'getAvailableClass'])->name('classroom.list');
Route::post('/class/book', [\App\Http\Controllers\Api\ClassRoomSlotBooking::class, 'bookClass'])->name('classroom.book');
Route::post('/class/cancel', [\App\Http\Controllers\Api\ClassRoomSlotBooking::class, 'cancelClass'])->name('classroom.cancel');
