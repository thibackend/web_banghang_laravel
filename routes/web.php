<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('page',[App\http\Controllers\PageController::class,'getIndex']);

Route::get('loaisanpham/{type}',[App\http\Controllers\PageController::class,'getLoaiSP']);

Route::get('chitietsanpham',[App\http\Controllers\PageController::class,'getDetail']);

Route::get('lienhe',[App\http\Controllers\PageController::class,'getContact']);

Route::get('gioithieu',[App\http\Controllers\PageController::class,'getAbout']);

// Admin

Route::get('admin', [App\Http\Controllers\PageController::class, 'getIndexAdmin']);

Route::get('admin-add-form', [App\Http\Controllers\PageController::class, 'getAdminAdd'])->name('add-product');

Route::post('admin-add-form', [App\Http\Controllers\PageController::class, 'postAdminAdd']);	

Route::get('admin-edit-form/{id}', [App\Http\Controllers\PageController::class, 'getAdminEdit']);

Route::post('admin-edit', [App\Http\Controllers\PageController::class, 'postAdminEdit']);	

Route::post('admin-delete/{id}', [App\Http\Controllers\PageController::class, 'postAdminDelete']);	

Route::get('admin-export', [App\Http\Controllers\PageController::class, 'exportAdminProduct'])->name('export');

