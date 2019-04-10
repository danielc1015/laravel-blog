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

// Cargando cases
use \App\Http\Middleware\ApiAuthMiddleware;

//RUTAS DE PRUEBA
/*
Route::get('/', function () {
    return view('welcome');
});

Route::get('/pruebas/{nombre?}', function($nombre=null){
    $texto = '<h1>texto jaja</h1>';
    $texto .= $nombre;
    return view('pruebas', array(
        'texto' => $texto
    ));
});
*/

Route::get('/animales', 'PruebasController@animales');
Route::get('/testorm', 'PruebasController@testOrm');

//RUTAS DEL API
Route::get('/usuario/pruebas', 'UserController@pruebas');
Route::get('/categoria/pruebas', 'CategoryController@pruebas');
Route::get('/post/pruebas', 'PostController@pruebas');

//rutas del controlador de usuarios
Route::post('api/register', 'UserController@register');
Route::post('/api/login', 'UserController@login');
Route::put('/api/user/update', 'UserController@update');
Route::post('/api/user/upload', 'UserController@upload')->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/avatar/{filename}', 'UserController@getImage');
Route::get('/api/user/detail/{id}', 'UserController@detail');

//rutas del controlador de categoria
Route::resource('/api/category', 'CategoryController');

//Rutas del controlador de entradas (post)
Route::resource('/api/post', 'PostController');