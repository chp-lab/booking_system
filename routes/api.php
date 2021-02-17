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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => ['cors']], function () {

    Route::get('v1/dev/{one_email?}/{select?}/{date?}', 'App\Http\Controllers\BookingController@dev')->middleware('log.route');


    Route::post('v1/checkAvailableRoom', 'App\Http\Controllers\BookingController@checkAvailableRoom')->middleware('log.route');
    Route::post('v1/booking', 'App\Http\Controllers\BookingController@booking')->middleware('log.route');
    Route::post('v1/guestManager/{select?}', 'App\Http\Controllers\BookingController@guestManager')->middleware('log.route');
    Route::post('v1/unlock/{user_token?}', 'App\Http\Controllers\BookingController@unlock')->middleware('log.route');
    Route::get('v1/ejectBooking/{one_email?}/{booking_number?}', 'App\Http\Controllers\BookingController@ejectBooking')->middleware('log.route');

    Route::get('v1/bookingTable/{one_email?}/{select?}/{date?}', 'App\Http\Controllers\BookingController@bookingTable')->middleware('log.route');
    Route::get('v1/userTable/{select?}/{one_email?}', 'App\Http\Controllers\BookingController@userTable')->middleware('log.route');
    Route::get('v1/roomTable', 'App\Http\Controllers\BookingController@roomTable')->middleware('log.route');
    Route::get('v1/getProfile/{user_token?}', 'App\Http\Controllers\BookingController@getProfile')->middleware('log.route');
    Route::get('v1/nowMeetingTable/{room_num?}', 'App\Http\Controllers\BookingController@nowMeetingTable')->middleware('log.route');
    Route::get('v1/availableStat/{day?}', 'App\Http\Controllers\BookingController@availableStat')->middleware('log.route');
    Route::get('v1/test', 'App\Http\Controllers\BookingController@test')->middleware('log.route');

});

