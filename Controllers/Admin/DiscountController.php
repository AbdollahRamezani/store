<?php

namespace App\Http\Controllers\Admin;

use App\lib\Jdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Vinkla\Hashids\Facades\Hashids;

class DiscountController extends Controller
{
    public function index()
    {
        $Discount = DB::table('shop_discount')
            ->orderBy('id', 'DESC')
            ->get();
        return view('admin.discount.index', compact('Discount'));
    }

    public function Create()
    {
        return view('admin.discount.create');
    }

    public function Store(Request $request)
    {
        $rules = [

            'name' => 'required',
            'description' => 'required',
            'token' => 'unique:shop_discount',
        ];
        $message = [
            'name.required' => 'عنوان کد تخفیف را وارد نمایید',
            'description.required' => 'نوضیحات کد تخفیف را وارد نمایید',
            'token.unique' => ' کد تخفیف تکراری میباشد ',
        ];
        if ($request->input('type_end') == 'count') {
            $rules['permission_count_used'] = 'required';
            $message['permission_count_used.required'] = 'مقدار محدودیت تعداد را وارد نمایید';
        }
        if ($request->input('type_end') == 'date') {
            $rules['ended_at'] = 'required';
            $message['ended_at.required'] = 'تاریخ محدودیت زمانی را وارد نمایید';
        }
        if ($request->input('exampleRadios') == 'vip') {
            $rules['token'] = 'required';
            $message['token.required'] = 'کد تخفیف اختصاصی را وارد نمایید';
        }

        $request->validate(
            $rules
            ,
            $message
        );
        if ($request->input('token') != '') {  /*برای چک کردن ایمکه کدتخفیف اخنصاصی (مانند عید فطر) تکراری وارد نکنیم*/
            $check = DB::table('shop_discount')
                ->where('token', $request->input('token'))
                ->count();
            if ($check >= 1) {
                return back()->withErrors([  /*به ارورهای قبلی یا همون $errors اضافه می شود*/
                    'duplicate' => 'کد تخفیف (توکن تخفیف) وارد شده تکراری می باشد'
                ]);
            }
        }
        if ($request->input('type_end') == 'count') {
            $Discount_id = DB::table('shop_discount')
                ->insertGetId([
                    'name' => $request->input('name'),
                    'description' => $request->input('description'),
                    'type' => $request->input('type'),
                    'permission_count_used' => $request->input('permission_count_used'),
                    'count_used' => 0,
                    'status' => $request->input('status'),
                    'value' => $request->input('value'),
                    'type_end' => $request->input('type_end'),
                    'created_at' => Carbon::now(),
                    'ended_at' => Carbon::now()

                ]);
        } elseif ($request->input('type_end') == 'date') {
            $date = $request->input('ended_at');
            /* $date==Example: 1399/02/28 */
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);
            $day = substr($date, 8, 2); /*برای شمردن ازسمت چپ ممیز نیز به حساب می آید*/

            $jdf = new Jdf();
            $GregorianDate = $jdf->jalali_to_gregorian($year, $month, $day, '-');

            $Discount_id = DB::table('shop_discount')
                ->insertGetId([
                    'name' => $request->input('name'),
                    'description' => $request->input('description'),
                    'type' => $request->input('type'),
                    'permission_count_used' => 0,
                    'count_used' => 0,
                    'status' => $request->input('status'),
                    'value' => $request->input('value'),
                    'type_end' => $request->input('type_end'),
                    'created_at' => Carbon::now(),
                    'ended_at' => Carbon::parse($GregorianDate)->toDateTimeString() /*به خاطر اینکه تاریخ را به صورت درست ثبت نماید و زمان را نیز ثبت نماید*/
                ]);
        }
        if (!is_null($request->input('token'))) {
            $token = $request->input('token');
        } else {
            $token = Hashids::encode($Discount_id);
        }
        DB::table('shop_discount')
            ->where('id', $Discount_id)
            ->update([
                'token' => $token
            ]);
        Session::flash('message', 'کد تخفیف مورد نظر با موفقیت ثبت گردید .');
        return redirect('admin/discount');
    }

    public function Edit($id)
    {
        $Discount = DB::table('shop_discount')
            ->where('id', $id)
            ->first();
        if (!is_null($Discount)) {
            return view('admin.discount.edit', compact('Discount'));
        } else {
            abort(404);
        }
    }

    public function Update(Request $request)
    {
        if ($request->has('token_checkbox')) {  /*برای چک کردن ایمکه کدتخفیف اخنصاصی (مانند عید فطر) تکراری وارد نکنیم*/
            $check = DB::table('shop_discount')
                ->where('token', $request->input('token'))
                ->count();
            if ($check >= 1) {
                return back()->withErrors([  /*به ارورهای قبلی یا همون $errors اضافه می شود*/
                    'duplicate' => 'کد تخفیف (توکن تخفیف) وارد شده تکراری می باشد'
                ]);
            }
        }

        if ($request->input('type_end') == 'count') {
            DB::table('shop_discount')
                ->where('id', $request->input('id'))
                ->update([
                    'name' => $request->input('name'),
                    'status' => $request->input('status'),
                    'type' => $request->input('type'),
                    'value' => $request->input('value'),
                    'type_end' => $request->input('type_end'),
                    'description' => $request->input('description'),
                    'permission_count_used' => $request->input('permission_count_used'),
                    'ended_at' => Carbon::now()

                ]);
        } elseif ($request->input('type_end') == 'date') {
            $date = $request->input('ended_at');
            /* $date==Example: 1399/02/28 */
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);
            $day = substr($date, 8, 2);

            $jdf = new Jdf();
            $GregorianDate = $jdf->jalali_to_gregorian($year, $month, $day, '-');

            DB::table('shop_discount')
                ->where('id', $request->input('id'))
                ->update([
                    'name' => $request->input('name'),
                    'status' => $request->input('status'),
                    'type' => $request->input('type'),
                    'value' => $request->input('value'),
                    'type_end' => $request->input('type_end'),
                    'description' => $request->input('description'),
                    'permission_count_used' => 0,
                    'ended_at' => Carbon::parse($GregorianDate)->toDateTimeString()
                ]);
        }
        if ($request->input('token') != '') {
            $token = $request->input('token');
        }
        else {
            $token = Hashids::encode($request->input('id'));
        }
        DB::table('shop_discount')
            ->where('id', $request->input('id'))
            ->update([
                'token' => $token
            ]);
        Session::flash('message', 'کد تخفیف با موفقیت ویرایش گردید .');
        return redirect('admin/discount');
    }

    public function Destroy($id)
    {
        DB::table('shop_discount')
            ->where('id', '=', $id)
            ->delete();
        Session::flash('destroy_msg', 'کد تخفیف با موفقیت حذف گردید');
        return back();
    }
}
