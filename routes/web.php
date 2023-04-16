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
Route::get('language_en', 'Language\LanguageController@Language_en');
Route::get('language_fa', 'Language\LanguageController@Language_fa');

Route::get('/sms', 'Site\SiteController@SMS');  /* این روت برای تست ارسال پیامک استفاده میشود*/

Route::get('/DeleteSpamPayments', 'Site\SiteController@DeleteSpamPayments'); // این روت برای کرون جاب است

Route::get('/', 'Site\SiteController@index');
Route::post('/', 'Site\SiteController@index'); // مربوط به زبان سایت

Route::get('/product/{id}', 'Site\SiteController@single');  /*   single product*/
Route::post('/AddToBasket', 'Site\SiteController@AddToBasket');  /*   AddToBascket*/
Route::get('/DeleteFromBasket', 'Site\SiteController@DeleteFromBasket');   /*DeleteFromBasket -- مربوط به قسمت آیتم بالا */
Route::get('cart', 'Site\SiteController@Cart');    /*مشاهده سبد خرید */
Route::get('UpdateBasket', 'Site\SiteController@UpdateBasket');    /*بروز رسانی سبد خرید در صفحه cart*/
Route::get('payment/{token}', 'Site\PaymentController@PrepareToBank');       /*ارسال به درگاه بانکی*/

/*چون بعضی از درگاهها با متد گت ارسال میکنن و بعضیها با متد پست پس چون ما از درگاههای متفاوتی استفاده میکنیم از هردو متد استفاده میکنیم*/
Route::get('CallBack', 'Site\PaymentController@CallBack');          /*CallBack*/
Route::post('CallBack', 'Site\PaymentController@CallBack');         /*CallBack*/

Route::post('Shop/SubmitDiscount', 'Site\PaymentController@SubmitDiscount')->name('SubmitDiscount');   /*اعمال کد تخفیف -- مربوط به صفحه checkout*/

Route::get('/GetCities', 'Site\SiteController@GetCities');        /*   برای استان و شهر */

Route::get('checkout', 'Site\SiteController@checkout');       /*تسویه حساب*/
Route::post('checkout', 'Site\SiteController@PostCheckout');
Route::get('RemoveFromBasket', 'Site\SiteController@RemoveFromBasket');/* حذف از سبد خرید مربوط به صفحه cart */

Route::group(['prefix' => 'admin'], function () {
    Route::get('/login', 'AdminAuth\LoginController@showLoginForm')->name('login'); /*صفحه مربوط به لاگین ادمین*/
    Route::post('/login', 'AdminAuth\LoginController@login');
    Route::get('/logout', 'AdminAuth\LoginController@logout')->name('logout');  /*اینجا چون باگ دارد به صورت پست هست که باید به گت تغییر کند*/

    // Route::get('/register', 'AdminAuth\RegisterController@showRegistrationForm')->name('register');
    // Route::post('/register', 'AdminAuth\RegisterController@register');

    Route::post('/password/email', 'AdminAuth\ForgotPasswordController@sendResetLinkEmail')->name('password.request');
    Route::post('/password/reset', 'AdminAuth\ResetPasswordController@reset')->name('password.email');
    Route::get('/password/reset', 'AdminAuth\ForgotPasswordController@showLinkRequestForm')->name('password.reset');
    Route::get('/password/reset/{token}', 'AdminAuth\ResetPasswordController@showResetForm');
});


Route::group(['prefix' => 'member'], function () {
    Route::get('/login', 'MemberAuth\LoginController@showLoginForm')->name('login');

    Route::get('/fast_login', 'MemberAuth\LoginController@ShowFastLoginForm');          // fast_login
    Route::post('/fast_login/sendcode', 'MemberAuth\LoginController@SendCodeToUser');
    Route::post('/fast_login/checkcode', 'MemberAuth\LoginController@CheckCodeForLogin');
    Route::get('/fast_login/removesession', 'MemberAuth\LoginController@RemoveSession');

    Route::post('/login', 'MemberAuth\LoginController@login');               /*استفاده نکردم*/
    Route::get('/logout', 'MemberAuth\LoginController@logout')->name('logout');  /*اینجا چون باگ دارد به صورت پست هست که باید به گت تغییر کند*/
    Route::get('/register', 'MemberAuth\RegisterController@showRegistrationForm')->name('register');    /*استفاده نکردم*/

    Route::post('/CustomRegister', 'Site\SiteController@CustomRegister');
    Route::post('/memberLogin', 'Site\SiteController@memberLogin');

    Route::post('/password/email', 'MemberAuth\ForgotPasswordController@sendResetLinkEmail')->name('password.request');
    Route::post('/password/reset', 'MemberAuth\ResetPasswordController@reset')->name('password.email');
    Route::get('/password/reset', 'MemberAuth\ForgotPasswordController@showLinkRequestForm')->name('password.reset');
    Route::get('/password/reset/{token}', 'MemberAuth\ResetPasswordController@showResetForm');

});
                                                        /* وب سرویس ها / API*/
Route::group(['prefix' => 'api/v1'], function () {
                                                                           /* نکته; ==>>>  بهتر است وب سرویسها با متد پست نوشته شود*/

    Route::post('/firstpage', 'Api\FirstPageController@index');       /* صفخه اصلی*/
    Route::post('/singleproduct', 'Api\FirstPageController@SingleProduct');  /*دریافت جزئیات محصول*/
    Route::post('/ProductListBycategory', 'Api\FirstPageController@ProductListBycategory'); /* دریافت لیست محصولات بر اساس دسته بندی*/
});
