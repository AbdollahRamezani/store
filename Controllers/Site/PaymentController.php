<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\FactorMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Vinkla\Hashids\Facades\Hashids;

class PaymentController extends Controller
{
    public function CheckTokenStatus($token) /*تابع چک کردن توکن*/
    {
        $Decode = Hashids::decode($token); /*مقدار token$ را که از طریق روت و یوآرال برای ما ارسال شده را دیکود میکنیم و داخل متغییر Decode$  میریزیم*/
        if (!is_null($Decode)) {  /*اگر متغییر Decode$  وجود داشته باشد به صورت آرایه برمیگردد ومقدارش null نیست*/
            return $Decode[0];   /* در صورت موجود بودن Decode$  خانه صفر آرایه که یک عدد است را برای ما برمیگرداند*/
        } else {
            return -1;    /*در صورت موجود نبودن Decode$  خودمان مقدار 1- را برمیگردانیم وجون مقدار آیدی تراکنش منفی نیست پس خطایی رخ داده است و توکن اشتباه است*/
        }
    }

    public function PrepareToBank($token)
    {
        $IdPayment = $this->CheckTokenStatus($token);
        if ($IdPayment != -1) {
            $payment = DB::table('payment')
                ->where('id', $IdPayment)
                ->first();
            if (!is_null($payment)) {
                if ($payment->status == 0) { /*  تراکنش معلق == 0  ,تراکنش پرداخت شده == 1  ,تراکنش پرداخت نشده == 1-  */

                    $PaymentGate = DB::table('setting')
                        ->where('param', 'PaymentGate')
                        ->first()->value;
                    switch ($PaymentGate) {
                        case "ZarrinPall":
                            $this->PrepareToZarrinpall($payment);
                            break;

                        case "Mellat":
                            $this->PrepareToMellat();
                            break;

                        case "Saman":
                            $this->PrepareToSaman();
                            break;
                    }
                } else {
                    die('تراکنش غیر مجاز');
                }
            } else {
                die('تراکنش یافت نشد');
            }
        } else {
            die('توکن اشتباه است');
        }
    }

    public function PrepareToZarrinpall($payment)
    {
        $MerchantID = 'b8c44aa4-ab6a-11ea-880e-000c295eb8fc'; //Required
        $Amount = $payment->price; //Amount will be based on Toman - Required
        $Description = 'ارسال به بانک جهت تسویه حساب'; // Required
        $Email = Auth::guard('member')->user()->email; // Optional
        $Mobile = Auth::guard('member')->user()->mobile; // Optional
        /*آدرسی که بعد از بازگشت از صفجه بانک باید وارد کنیم که در مرحله کالبک چک میکنیم کاربر پرداختش را به درستی انجام داده یا نه*/
        $CallbackURL = url('CallBack'); // Required


        $client = new \SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);

        $result = $client->PaymentRequest(
            [
                'MerchantID' => $MerchantID,
                'Amount' => $Amount,
                'Description' => $Description,
                'Email' => $Email,
                'Mobile' => $Mobile,
                'CallbackURL' => $CallbackURL,
            ]
        );

//Redirect to URL You can do it also by creating a form
        if ($result->Status == 100) {
            DB::table('payment')  /* قبل از پرداخت رسید دیجیتالی را که از زرین پال گرفتیم داخل جدول پیمنت قرار میدهیم */
                ->where('id', $payment->id)
                ->update([
                    'authority' => $result->Authority
                ]);
            Header('Location: https://www.zarinpal.com/pg/StartPay/' . $result->Authority);
        } else {
            echo 'ERR: ' . $result->Status;
        }
    }

    public function CallBack(Request $request)
    {
        $PaymentGate = DB::table('setting')
            ->where('param', 'PaymentGate')
            ->first()->value;

        switch ($PaymentGate) {
            case "ZarrinPall":
                $BackStatus = $this->CallbackFromZarrinPall($request);
                break;

            case "Mellat":
                $BackStatus = $this->CallbackFromMellat($request);
                break;

            case "Saman":
                $BackStatus = $this->CallbackFromSaman($request);
                break;
        }
        Session::forget('basket');
        Session::forget('payment');
        return view('site.callback', compact('BackStatus'));
    }

    public function SendMailToUser($type, $name, $totalprice, $products, $OrderId)
    {
        Mail::to(Auth::guard('member')->user()->email)
            ->send(new FactorMail($type, $name, $totalprice, $products, $OrderId));
    }

    public function CallbackFromZarrinPall($request)
    {
        $payment = DB::table('payment')
            ->where('authority', $request->input('Authority'))
            ->first();

        $MerchantID = 'b8c44aa4-ab6a-11ea-880e-000c295eb8fc';
        $Amount = $payment->price; //Amount will be based on Toman
        $Authority = $request->input('Authority');

        if ($request->input('Status') == 'OK') {

            $client = new \SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);

            $result = $client->PaymentVerification(
                [
                    'MerchantID' => $MerchantID,
                    'Authority' => $Authority,
                    'Amount' => $Amount,
                ]
            );
            if ($result->Status == 100) {
                DB::table('payment')
                    ->where('authority', $Authority)
                    ->update([
                        'status' => 1, /*چون پرداخت با موفقیت انجام شده استاتوس تراکنش برابر 1 یا همون پرداخت شده میکنیم*/
                        'refid' => $result->RefID /*چون تراکنش با موفقیت انجام شده کد پیگیری را در جدول تراکنش ها ذخیره میکنیم*/
                    ]);
                DB::table('shop_request')
                    ->where('id', $payment->id_value)  /*آیدی ولیو همون آیدی فاکتور هست که در جدول پیمنت داریم*/
                    ->update([
                        'payment_status' => 1,  /*وضعیت فاکتور را برابر 1 میکنیم */
                        'status' => 1
                    ]);
                $products = DB::table('shop_request_product')  /*محصولاتی که خریداری شده را داخل متغییر product$ میریزیم*/
                    ->where('factor_id', $payment->id_value)
                    ->get();
                foreach ($products as $value) {
                    DB::table('shop_product')
                        ->where('id', $value->product_id)
                        ->decrement('count', $value->count);

                    DB::table('shop_product')
                        ->where('id', $value->product_id)
                        ->increment('count_buy', $value->count);
                }
                return [
                    'status' => 1,
                    'RefID' => $result->RefID,
                    'Message' => ' پرداخت شما با موفقیت انجام شد .'
                ];
            } else {
                return [
                    'status' => 0,
                    'Message' => 'پرداخت شما ناموفق بود !'
                ];
            }
        } else {
            return [
                'status' => 0,
                'Message' => 'پرداخت شما ناموفق بود !'
            ];
        }

    }

    public function SubmitDiscount(Request $request)
    {              /* check kardan khali naboodan input*/
        if ($request->input('code') != '') {
            /*چون یک ورودی بیشتر نداریم از ولیدیتور استفاده نکردیم ونشان دادن پیغام خطا را با این روش پایین نوشتیم*/

            /* check kardan vojood session Tarakonesh*/
            if (Session::has('payment')) {
                /*barrasi dastkari shodan Token tarakonesh tavasot karbar*/
                $token = $this->CheckTokenStatus(Session::get('payment'));
                if ($token != -1) {
                    $Payment = DB::table('payment')
                        ->where('id', $token)
                        ->first();
                    /*Barreasi Vojod Tarakonesh dar database*/
                    if (!is_null($Payment)) {
                        /*Barreasi 2Bar Emal nashodan Code Takhfif*/
                        if ($Payment->discount == 0) {
                            $discount = DB::table('shop_discount')
                                ->where('token', $request->input('code'))
                                ->first();
                            /*Barreasi Mojood Boodan Code Takhfif*/
                            if (!is_null($discount)) {
                                /*Barreasi Faal Boodan Code Takhfif*/
                                if ($discount->status == 1) {
                                    /*Barreasi ٍ Shart Payan Code Takhfif Bar asas Tedad*/
                                    if ($discount->type_end == 'count') {
                                        if ($discount->permission_count_used <= $discount->count_used) {
                                            return $this->ReturnError('محدودیت تعداد کد تخفیف شما به پایان رسیده است  !');
                                        }
                                    }
                                    /*Barreasi ٍ Shart Payan Code Takhfif Bar asas Tarikh*/
                                    if ($discount->type_end == 'date') {
                                        $EndedAt = Carbon::parse($discount->ended_at);
                                        if ($EndedAt->isPast()) {
                                            return $this->ReturnError('اعتبار زمانی کد تخفیف شما به پایان رسیده است  !');
                                        }
                                    }
                                                            ////***Emal Takhfif (function dar Payin)***////
                                         return response()
                                             ->json($this->MakeDiscount($Payment, $discount),200);
                                } else {
                                    return $this->ReturnError('کد تخفیف مورد نظر غیرفعال میباشد  !');
                                }
                            } else {
                                return $this->ReturnError('کد تخفیفی با مشخصات ورودی شما یافت نشد  !');
                            }
                        } else {
                            return $this->ReturnError('روی این تراکنش قبلا کد تخفیف اعمال شده است  !');
                        }
                    } else {
                        return $this->ReturnError('تراکنش یافت نشد  !');
                    }
                } else {
                    return $this->ReturnError('توکن نادرست  !');
                }

            } else {
                return $this->ReturnError('تراکنش نامعتبر !');
            }
        } else {
            return $this->ReturnError('لطفا کد تخفیف را وارد نمایید');
        }
    }

    /* Function Emal Takhfif */
    public function MakeDiscount($Payment, $discount)
    {
                   /* kasr be soorat mablagh sabet*/
        if ($discount->type == 'price') {
            $Price = $Payment->price - $discount->value;      /*  $Price == mablagh kol menhay jam mablagh Takhfifat*/
            $DiscountPrice = $discount->value;               /*  $DiscountPrice == jame mablagh Takhfifat*/
        }
                    /* kasr be soorat darsadi*/
        if ($discount->type == 'percent') {
            $Price = $Payment->price - (($Payment->price / 100) * $discount->value);  /*  $Price == mablagh kol menhay jam mablagh Takhfifat*/
            $DiscountPrice = ($Payment->price / 100) * $discount->value;            /*  $DiscountPrice == jame mablagh Takhfifat*/
        }
        DB::table('payment')
            ->where('id', $Payment->id)
            ->update([
                'price' => $Price,
                'discount' => 1,
                'discount_id' => $discount->id
            ]);
        DB::table('shop_discount')
            ->where('id', $discount->id)
            ->increment('count_used');
        return [      /* in 2 meghdar be function  SubmitDiscount ferestadeh mishavad*/
            'Price' => number_format($Price),  /*  $Price == mablagh kol menhay jam mablagh Takhfifat*/
            'DiscountPrice' =>number_format( $DiscountPrice)  /*  $DiscountPrice == jame mablagh Takhfifat*/
        ];
    }

    /* Baray kholaseh nevisi yek function baray Error hamon dorost kardam va har bar faghat matn error dar bala taghyir mikonad*/
    public function ReturnError($err)
    {
        {
            return response()->json([
                'error' => $err
            ], 400);
        }
    }
}
