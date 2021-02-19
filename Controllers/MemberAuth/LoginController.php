<?php

namespace App\Http\Controllers\MemberAuth;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Hesto\MultiAuth\Traits\LogsoutGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers, LogsoutGuard {
        LogsoutGuard::logout insteadof AuthenticatesUsers;
    }

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    public $redirectTo = '/member';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('member.guest', ['except' => 'logout']);
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('member.auth.login');
    }

    public function ShowFastLoginForm()
    {
        return view('member.auth.fast_login');
    }

    public function SendCodeToUser(Request $request)
    {
        $request->validate(
            [
                'mobile' => 'required'
            ],
            [
                'mobile.required' => 'لطفا شماره موبایل را وارد نمایید'
            ]
        );
        $CheckUser = DB::table('member')
            ->where('mobile', $request->input('mobile'))
            ->first();
        if (!is_null($CheckUser)) {
            $code = rand(1000, 9999);
            DB::table('active_code')->insert([
                'mobile' => $request->input('mobile'),
                'code' => $code,
                'status' => 0,
                'created_at' => Carbon::now()
            ]);
            $message = "کد ورود شما :$code";
            $this->SendSmsToUser($request->input('mobile'), $message);
            Session::put('VerifyUser', $request->input('mobile'));
            Session::flash('success', 'کد فعالسازی شما با موفقیت ارسال شد');
            return back();
        } else {
            return back()->withErrors([
                'کاربری با این شماره موبایل یافت نشد !'
            ]);
        }
    }

    public function CheckCodeForLogin(Request $request)
    {
        $request->validate([
            'code' => 'required'
        ],
            [
                'code.required' => 'لطفا کد فعالسازی خود را وارد نمایید'
            ]);
        if (Session::has('VerifyUser')) {
            $mobile = Session::get('VerifyUser');
            $checkcode = DB::table('active_code')
                ->where('mobile', $mobile)
                ->where('code', $request->input('code'))
                ->first();
            if (!is_null($checkcode)) {
                if ($checkcode->status == 0) {
                    $User = DB::table('member')
                        ->where('mobile', $mobile)
                        ->first();
                    Auth::guard('member')->loginUsingId($User->id);
                    DB::table('active_code')
                        ->where('id', $checkcode->id)
                        ->update([
                            'status' => 1
                        ]);
                    Session::forget('VerifyUser');
                    return redirect('member');
                } else {
                    Session::flash('error', 'کد فعالسازی شما منقضی شده است');
                    return back();
                }
            } else {
                Session::flash('error', 'کدفعالسازی شما نادرست است');
                return back();
            }
        } else {
            Session::flash('erroe', 'لطفا مجدد تلاش کنید');
            return back();
        }
    }

    public function RemoveSession()
    {
        Session::forget('VerifyUser');
        return back();
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('member');
    }
}
