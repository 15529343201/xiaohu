<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
function user_ins(){
    return new App\User;
}
Route::any('api',function(){
    return ['version' => 0.1];
});

Route::any('api/signup',function(){
    return user_ins()->signup();
});
Route::any('api/login',function(){
    return user_ins()->login();
});
Route::any('api/logout',function(){
    return user_ins()->logout();
});
Route::any('test',function(){
    dd(user_ins()->is_logged_in());
});
