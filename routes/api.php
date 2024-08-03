<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

Route::get('/classrooms', [App\Http\Controllers\ClassroomController::class, 'index']);
Route::post('/bookings', [App\Http\Controllers\ClassroomController::class, 'book']);
Route::delete('/bookings/{id}', [App\Http\Controllers\ClassroomController::class, 'cancel']);
