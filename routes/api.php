<?php

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

Route::namespace('API')->group(
    function () {
        Route::post('signup', 'UserController@signup');
        Route::post('login', 'UserController@login');

        Route::middleware(['auth:api'])->group(
            function () {
                Route::post('loan/apply', 'LoanController@apply');
                Route::post('loan/{loan}/repay', 'LoanController@repay');
            }
        );
    }
);
