<?php
error_reporting(1);
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::controller("news","NewsController");

Route::controller("order","OrderController");

Route::controller("tweets","TweetsController");

Route::get('/', function () {

    print_r($_SERVER['HTTP_HOST']);

    //return view('welcome');
});
