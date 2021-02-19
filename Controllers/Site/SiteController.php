<?php

namespace App\Http\Controllers\Site;

use App\Mail\FactorMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Kavenegar\KavenegarApi;
use Vinkla\Hashids\Facades\Hashids;


class SiteController extends Controller
{
    public function SMS()  /*این تابع برای ارسال پیامک استفاده میشود*/
    {
        $SMSHandler = new KavenegarApi('325A77523867687576347446426D6A69332B2F706B664B38476F6D6F6C4C38354B714B326D333078616E493D');
        try {
            // $send = $SMSHandler->VerifyLookup('09158029097', '1766', '', '', 'VerifyUser'); /* در این قسمت به هر شماره ی دلخواه میتوان پیامک فرستاد*/
            $message = 'به وب سایت فروشگاه رمضانی خوش آمدید';
            $send = $SMSHandler->Send('10008663', '09028777931', $message); /*در اینجا فقط به شماره ای که تو سایت کاوه نگار ثبت نام کردیم میشود پیامک فرستاد*/
            dd($send);
        } catch (\Kavenegar\Exceptions\ApiException $e) {
            dd($e->getMessage());
        }
    }

    public function DeleteSpamPayments()   // این تابع برای تست کردن کرون جاب است
    {
        DB::table('payment')
            ->where('status', 0)
            ->whereDate('created_at', '<', Carbon::yesterday())
            ->delete();
        return 'ok';
    }
    public function index(Request $request)
    {
        App::setLocale($request->input('Language'));

        $slider = DB::table('slider')
            ->get();
        $Category = DB::table('shop_category')
            ->orderBy('sort', 'ASC')
            ->get();
        $BestProduct = DB::table('shop_product')/*محصولات پرفروش*/
        ->where('status', 1)/*استاتوس برابر یک یعنی محصول پیش نویس نیست و منتشر شده است*/
        ->orderBy('count_buy', 'DESC')/* کانت بای به صورت نزولی مرتب شده یعنی هر محصول کانت بای(تعداد فروش) بیشتری داشته باشد از بالا می آید اول صف*/
        ->take(5)/*فانکشن تیک می آید تعداد دریافتی از دیتابیس را محدود میکند که در اینجا ما فقط تعداد 5 تا را دریافت کردیم*/
        ->get();
        $SpecialProduct = DB::table('shop_product')
            ->where('status', 1)
            ->where('special', 1)
            ->orderBy('sort', 'ASC')
            ->take(5)
            ->get();
        $LastProduct = DB::table('shop_product')/* آخرین محصولات*/
        ->where('status', 1)
            ->orderBy('id', 'DESC')/*بر اساس نزولی مرتب شده یعنی هر محصول آیدی بزرگتری دارد دیرتر ثبت شده و می آید اول صف*/
            ->take(5)
            ->get();
        return view('site.index', compact('Category', 'BestProduct', 'SpecialProduct', 'LastProduct', 'slider'));
    }

    public function single($id)
    {
        $product = DB::table('shop_product')
            ->where('id', $id)
            ->first();
        if (!is_null($product)) {
            $BestProduct = DB::table('shop_product')
                ->where('status', 1)
                ->orderBy('count_buy', 'DESC')
                ->take(5)
                ->get();
            $SpecialProduct = DB::table('shop_product')
                ->where('status', 1)
                ->where('special', 1)
                ->orderBy('sort', 'ASC')
                ->take(5)
                ->get();
            $Detail = DB::table('shop_product_detail')
                ->where('id_post', $id)
                ->get();
            $Gallery = DB::table('shop_product_gallery')
                ->where('post_id', $id)
                ->get();
            $RelatedProduct = DB::table('shop_product')/*محصولات مرتبط*/
            ->where('category', $product->category)/*برو از جدول شاپ پروداکت محصولاتی را انتخاب کن که دسته بندیشون برابرباشه با دسته بندی محصولی که الان انتخاب شده یا همون product->category*/
            ->where('id', '!=', $product->id)/*برو از جدول شاپ پروداکت محصولاتی را انتخاب کن که آیدیشون برابر با محصولی که الان انتخاب شده نباشد یعنی محصولی که الان انتخاب شده جزوء محصولات مرتبط نمایش نده*/
            ->take(6)
                ->get();
            return view('site.single', compact('product', 'BestProduct', 'SpecialProduct', 'Detail', 'Gallery', 'RelatedProduct'));

        } else {
            return view('404');
        }
    }

    public function AddToBasket(Request $request)
    {
        $id = $request->input('id');
        $product = DB::table('shop_product')
            ->where('id', $id)
            ->first();
        if (!is_null($product)) {
            if (Session::has("basket.$id")) {
                $count = Session::get("basket.$id.count");
                if ($product->count < $count + $request->input('quantity')) {
                    return response()->json([
                        'error' => ['موجودی انبار کمتر از مقدار محصول جدیدی است که به سبد خرید اضافه کردید !!']
                    ], 400);
                }
            }
            if ($product->count < $request->input('quantity')) {
                return response()->json([
                    'error' => ['موجودی محصول در انبار کمتر از مقدار درخواستی شما میباشد. !!!']
                ], 400);
            }
            if (Session::has("basket.$id")) {
                $count = Session::get("basket.$id.count");
                Session::put("basket.$id.count", $count + $request->input('quantity'));
                return response()->json([
                ], 200);
            }
            $data = [
                "id" => $id,
                "name" => $product->title,
                "img" => $product->img,
                "price" => $product->price,
                "dis_price" => $product->dis_price,
                "discount" => $product->discount,
                "count" => $request->input('quantity')
            ];
            Session::put("basket.$id", $data);
            return response()->json([
            ], 200);

        } else {
            return response()->json([
                'error' => ['محصول مورد نظر یافت نشد !!!']
            ], 400);
        }
    }

    public function DeleteFromBasket(Request $request)  /*مربوط به قسمت آیتم بالا*/
    {
        $id = $request->input('id');
        if (Session::has("basket.$id")) {
            Session::forget("basket.$id");
            return response()->json([
            ], 200);
        } else {
            return response()->json([
                'error' => 'درخواست شما با مشکل مواجه شده است'
            ], 400);
        }
    }

    public function Cart()
    {
        $Session = (Session::has('basket')) ? Session::get('basket') : [];  /*استفاده از شرط کوتاه */
        if ($Session == null) {
            Session::flash('alert', 'متاسفانه سبد خرید شما خالی است !!! ');
            return view('site.cart');
        } else {
            return view('site.cart');
        }
    }

    public function UpdateBasket(Request $request)  /* مربوط به صفحه cart ( مشاهده سبد خرید) */
    {
        $id = $request->input('id');
        $product = DB::table('shop_product')
            ->where('id', $id)
            ->first();
        if (Session::get("basket.$id.discount") == 1) {
            $movaghat_price = Session::get("basket.$id.dis_price");
        } else {
            $movaghat_price = Session::get("basket.$id.price");
        }
        if ($product->count < $request->input('count')) {
            return response()->json([
            ], 400);
        }

        Session::put("basket.$id.count", $request->input('count'));  /*در اینجا اینپوت count همون مقداری است که در کدهای ایجکس از اینپوت quantity مقدارش راگرفتیم*/


        $price = Session::get("basket.$id.count") * $movaghat_price;  /*   در اینجا $price قیمت کل هست */
        $totalprice = 0;
        foreach (Session::get('basket') as $value) {
            $totalprice += $value['count'] * $movaghat_price;
        }
        return response()->json([
            'price' => number_format($price),
            'totalprice' => number_format($totalprice),
            'msg' => 'سبد خرید شما با موفقیت ویرایش گردید'

        ], 200);
    }

    public function RemoveFromBasket(Request $request)      /* مربوط به صفحه cart ( مشاهده سبد خرید) */
    {
        $id = $request->input('id');
        if (Session::has("basket.$id")) {
            $totalprice = 0;
            Session::forget("basket.$id");
            foreach (Session::get("basket") as $value) {
                if ($value['discount'] == 1) {
                    $movaghat_price = $value['dis_price'];
                } else {
                    $movaghat_price = $value['price'];
                }
                $totalprice += $value['count'] * $movaghat_price;
            }
            return response()->json([
                'totalprice' => number_format($totalprice),
                'msg' => ['محصول از سبد خرید حذف گردید']
            ], 200);
        } else {
            return response()->json([
                'error' => 'درخواست شما با مشکل مواجه شده است'
            ], 400);
        }


    }

    public function CustomRegister(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'family' => 'required',
                'mobile' => 'required|unique:member',
                //'img' => 'mimes:jpeg,jpg',
                'email' => 'required|unique:member|email',
                'password' => 'required|min:8',
                'confirm_password' => 'required|same:password',
                'captcha' => 'required|captcha'
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all(),
                /*'key'   =>  $validator->errors()->keys()*/  /*این خط اضافه کردم تا پیغام خطا را بعد اینپوت نمایش دهد*/
            ], 400);
        } else {
            $img = null;
            if ($request->hasFile('img')) {
                $img = $this->UploadFile($request->file('img'), 'uploads/img/');
            }
            $member = DB::table('member')
                ->insertGetId([
                    'name' => $request->input('name'),
                    'family' => $request->input('family'),
                    'mobile' => $request->input('mobile'),
                    'email' => $request->input('email'),
                    'city' => $request->input('city'),
                    'img' => $img,
                    'password' => bcrypt($request->input('password')),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            Auth::guard('member')->LoginUsingId($member);
        }
        return response()->json([
        ], 200);
    }

    public function memberLogin(Request $request)
    {
        $request->validate(
            [
                'username' => 'required',
                'password' => 'required',
                'captcha' => 'required|captcha'
            ],
            [
                'username.required' => 'لطفا ایمیل یا شماره موبایل را وارد نمایید',
                'password.required' => 'لطفا کلمه عبور را وارد نمایید',
                'captcha.required' => 'لطفا کلمه امنیتی را وارد نمایید'
            ]
        );
        $Member = DB::table('member')
            ->where('mobile', $request->input('username'))
            ->orWhere('email', $request->input('username'))
            ->first();
        $remember = $request->input('remember_me');
        if ($remember == 'on') {
            $remember = 1;
        } else {
            $remember = 0;
        }
        if (!is_null($Member)) {
            if (Hash::check($request->input('password'), $Member->password)) {
                Auth::guard('member')->loginUsingId($Member->id, $remember);

                if (Session::has('BackToCheckout')) {
                    return redirect('checkout');
                } else {
                    return redirect('member');
                }
            } else {
                return view('member.auth.login', [
                    /*چون ولیدیت بالا کالکشن است ولی پیغام ما اینجا کالکشن نیست واز all استفاده کردیم ما هم باید اینجا از کلمه collect استفاده کنیم تا پیغام ما هم به صورت کالکشن تبدیل شود */
                    /*کالکشن کاملتر از آرایه است یعنی چندتا آرایه یک کالکشن میباشد*/
                    'errors' => collect(['کلمه عبور شما نادرست میباشد .'])
                ]);
            }
        } else {
            return view('member.auth.login', [
                'errors' => collect(['کاربری بااین مشخصات یافت نشد .'])
            ]);
        }
    }

    public function checkout()  /*توضیحات کاملتر در فیلم چهارشنبه 24 اردیبهشت ساعت 40 دقیقه*/
    {
        if (Session::has('basket')) {
            if (Auth::guard('member')->check()) {
                $User_id = Auth::guard('member')->user()->id;
                $Payment_id = DB::table('payment')/*ساختن یک تراکنش*/
                ->insertGetId([
                    'id_user_app' => $User_id,
                    'status' => 0  /*یعنی این تراکنش هنوز معلق است*/
                ]);
                $Factor_id = DB::table('shop_request')/*ساختن فاکتور*/
                ->insertGetId([
                    'id_payment' => $Payment_id,
                    'user_id' => $User_id,
                    'status' => 0, /*یعنی این فاکتور در حال بررسی یا معلق است*/
                    'payment_status' => 0
                ]);
                $totalprice = 0;
                $AllProducts = Session::get('basket');
                foreach ($AllProducts as $value) {
                    /*در چندخط پایین به خاطر امنیت بیشتر استناد به دیتابیس کردیم و قیمت محصول را از دیتابیس گرفتیم چون سشن امینت ندارد و ممکن است با یک سری نرم افزار قیمت داخل سشن دستکاری شود*/
                    $product = DB::table('shop_product')
                        ->where('id', $value['id'])
                        ->first();
                    if (!is_null($product)) {
                        if ($product->discount == 1) {
                            $movaghat_price = $product->dis_price;
                        } else {
                            $movaghat_price = $product->price;
                        }
                        DB::table('shop_request_product')/*داخل این جدول shop_request_product مشخص میکنیم هرفاکتور چه محصولاتی داخلش آمده است*/
                        ->insert([
                            'title' => $product->title,
                            'price' => $product->price,
                            'discount' => $product->discount,
                            'dis_price' => $product->dis_price,
                            'product_id' => $product->id,
                            'factor_id' => $Factor_id,
                            'payment_id' => $Payment_id,
                            'user_id' => $User_id,
                            'count' => $value['count'],
                        ]);
                        $totalprice += $movaghat_price * $value['count'];
                    }
                }
                DB::table('payment')
                    ->where('id', $Payment_id)
                    ->update([
                        'id_value' => $Factor_id,  /*قرار دادن آیدی فاکتور در آیدی ولیو تراکنش*/
                        'price' => $totalprice
                    ]);
                DB::table('shop_request')
                    ->where('id', $Factor_id)
                    ->update([
                        'price' => $totalprice
                    ]);
                Session::put('payment', Hashids::encode($Payment_id));
                $state = DB::table('state')
                    ->pluck('name', 'id')->toArray();
                $this->SendMailToUser('draft', Auth::guard('member')->user()->name, $totalprice, $AllProducts, $Factor_id);
                $message = "کاربر عزیز سفارش شماره $Factor_id باموفقیت برای شما ثبت شد
وضعیت سفارش:منتظرپرداخت";
                $this->SendSmsToUser(Auth::guard('member')->user()->mobile, $message);/*در اینجا فقط به شماره ای که توسایت کاوه نگار ثبت نام کردیم میشود پیامک فرستاد*/
                return view('site.checkout', compact('state'));
            } else {
                Session::put('BackToCheckout', 1);
                return redirect('member/login');
            }
        } else {
            Session::flash('alert', ' متاسفانه سبد خرید شما خالی است و نمیتوانید وارد صفحه تسویه حساب شوید');
            return redirect('/');
        }
    }

    public function SendMailToUser($type, $name, $totalprice, $products, $OrderId)
    {
        Mail::to(Auth::guard('member')->user()->email)/*ایمیل فردی که قرار هست ایمیل برایش ارسال گردد*/
        ->send(new FactorMail($type, $name, $totalprice, $products, $OrderId));
    }

    public function GetCities(Request $request)              /*گرفتن شهرها از دیتابیس*/
    {
        $city = DB::table('city')
            ->where('state_id', $request->input('id'))
            ->pluck('name', 'id');
        return response()->json($city, 200);
    }

    public function PostCheckout(Request $request)
    {
        $validator = $request->validate(
            [
                'state' => 'required',
                'city' => 'required',
                'zip_code' => 'required',
                'address' => 'required',
            ],
            [
                'state.required' => 'لطفا استان خود را وارد نمایید',
                'city.required' => 'لطفا شهر خود را وارد نمایید',
                'zip_code.required' => 'لطفا کدپستی خود را وارد نمایید',
                'address.required' => 'لطفا آدرس خود را وارد نمایید',
            ]
        );
        if (Session::has('payment')) {
            /*وقتی هشیدز را دیکود میکنیم خروجی اش یک آرایه است وبرای اینکه به خانه اول آرایه (یک عدد استرینگ) دسترسی داشته باشیم باید مقدار صفر را درآخرش وارد کنیم*/
            $token = Hashids::decode(Session::get('payment'))[0];
            /*دراینجا$token آیدی تراکنش ذخیره شده میباشد که در مرحله قبل به صورت هش شده در سشن payment قرار دادیم*/
            $payment = DB::table('payment')
                ->where('id', $token)
                ->first();
            if (!is_null($payment)) {
                DB::table('shop_request_address')
                    ->insert([
                        'req_id' => $payment->id_value, /*در مرحله قبل آیدی فاکتور را در آیدی ولیو جدول payment قرار دادیم */
                        'state' => $request->input('state'),
                        'city' => $request->input('city'),
                        'address' => $request->input('address'),
                        'zip_code' => $request->input('zip_code'),
                        'user_id' => Auth::guard('member')->user()->id,
                        'mobile' => Auth::guard('member')->user()->mobile

                    ]);
                DB::table('shop_request')/*چون در مراحل قبل فاکتور ثبت شده فقط اینجا قسمت توضیحات را آپدیت میکنیم*/
                ->where('id', $payment->id_value)
                    ->update([
                        'detail' => $request->input('comments')
                    ]);
                /*چون در مراحل قبل آیدی تراکنش یا همون پیمنت به صورت هش شده در سشن پیمنت قرار داده شده پس اینجا هم برای امنیت بیشتر از سشن پیمنت به صورت هش شده استفاده میکنیم و سشن را دیکود نمیکنیم*/
                return redirect('payment/' . Session::get('payment'));

            } else {
                abort(404);
            }

        } else {
            abort(404);
        }

    }
}
