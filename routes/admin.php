<?php
Route::group(['prefix' => 'admins'],function (){     // روت های مربوط به مدیریت کاربران
    Route::group(['middleware' => ['role:SuperAdmin']], function () {
        Route::get('/', 'Admin\AdminController@index');
        Route::get('/create', 'Admin\AdminController@create');
    });
    Route::post('/store', 'Admin\AdminController@store');
});

Route::get('/home', 'Admin\DashboardController@index');
Route::get('/category', 'Admin\CategoryController@index');
Route::get('/category/create', 'Admin\CategoryController@create');
Route::get('/category/destroy/{id}', 'Admin\CategoryController@destroy');
Route::get('/category/edit/{id}', 'Admin\CategoryController@edit');
Route::post('/category/store', 'Admin\CategoryController@store');
Route::post('/category/update', 'Admin\CategoryController@update');

Route::group(['prefix'=>'discount'],function (){
    Route::get('/','Admin\DiscountController@index');
    Route::get('/create','Admin\DiscountController@Create');
    Route::post('/store','Admin\DiscountController@Store');
    Route::get('/edit/{id}','Admin\DiscountController@Edit');
    Route::post('/update','Admin\DiscountController@Update');
    Route::get('/destroy/{id}','Admin\DiscountController@Destroy');
});

Route::get('product', 'Admin\ProductController@index');
Route::get('product/create', 'Admin\ProductController@create');
Route::post('product/store', 'Admin\ProductController@store');
Route::get('product/edit/{id}', 'Admin\ProductController@edit');
Route::post('product/update', 'Admin\ProductController@update');
Route::get('product/destroy/{id}', 'Admin\ProductController@destroy');

Route::get('members', 'Admin\MemberController@index');
Route::post('members/store', 'Admin\MemberController@store');
Route::get('members/edit/{id}', 'Admin\MemberController@edit');
Route::post('members/update', 'Admin\MemberController@update');
Route::get('members/destroy/{id}', 'Admin\MemberController@destroy');


