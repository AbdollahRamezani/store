<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use function GuzzleHttp\Psr7\str;
use Illuminate\Support\Facades\Session;

class MemberController extends Controller
{
    public function index(Request $request) /*اینجا ما بخاطر سرچ کردن باید request را بنویسیم*/
    {
        $member = DB::table('member');
        $member->orderBy('id', 'DESC');
        if ($request->has('search')) {
            $member->where('name', 'LIKE', '%' . $request->input('search') . '%');
            $member->orWhere('family', 'LIKE', '%' . $request->input('search') . '%');
            $member->orWhere('mobile', 'LIKE', '%' . $request->input('search') . '%');
            $member->orWhere('email', 'LIKE', '%' . $request->input('search') . '%');
        }
        $member = $member->paginate(15);
        return view('admin.member.index', compact('member'));
    }

    public function create()
    {
        $city = DB::table('city')
            ->pluck('name', 'id')->toArray();
        return view('admin.member.create', compact('city'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'family' => 'required',
                'mobile' => 'required|unique:member',
               // 'img' => 'mimes:jpeg,jpg',
                'email' => 'required|unique:member|email',
                'password' => 'required',
                'confirm_password' => 'required|same:password',
                'g-recaptcha-response' => 'required'
            ],
            [
                'name.required' => 'لطفا نام کاربر را وارد نمایید',
                'g-recaptcha-response.required' => 'لطفا تیک من ربات نیستم را بزنید',
                'password.required'=>'لطفا کلمه عبور را وارد نمایید',
                'confirm_password.required'=>'لطفا تکرار کلمه عبور را وارد نمایید',
                'confirm_password.same' =>'کلمه عبور و تکرار کلمه عبور یکسان نیست',
                'email.email' =>'لطفا ایمیل را به صورت فرمت ایمیل وارد نمایید(exam@Exam.com)'
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], 400);
        } else {
            $token = $request->input('g-recaptcha-response');
            $img = null;
            if (strlen($token) > 0) {
                if ($request->hasFile('img')) {
                    $img = $this->UploadFile($request->file('img'), 'uploads/img/');
                }
                DB::table('member')
                    ->insert([
                        'name' => $request->input('name'),
                        'family' => $request->input('family'),
                        'mobile' => $request->input('mobile'),
                        'city' => $request->input('city'),
                        'email' => $request->input('email'),
                        'img' => $img,
                        'password' => bcrypt($request->input('password')),
                    ]);
                return response()->json([
                    'msg' =>'کاربر جدید با موفقیت ذخیره گردید.'
                ], 200);
            }
        }
    }

    public function edit($id)
    {
        $member = DB::table('member')
            ->where('id', $id)
            ->first();

        $city = DB::table('city')
            ->pluck('name', 'id')->toArray();

        if (!is_null($member)) {
            return view('admin.member.edit', compact('member','city'));
        } else {
            return view('404');
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'family' => 'required',
                'mobile' => 'required',
                'email' => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], 400);
        } else {
            if ($request->has('active')) {
                $active = 1;
            } else {
                $active = 0;
            }
            $img = $request->input('oldimg');
            if ($request->hasFile('img')) {
                Storage::disk('publicPath')->delete($request->input('oldimg'));
                $img = $this->UploadFile($request->file('img'), 'uploads/img/');
            }
            if ($request->input('password') != '') {
                DB::table('member')
                    ->where('id', $request->input('id'))
                    ->update([
                        'name' => $request->input('name'),
                        'family' => $request->input('family'),
                        'mobile' => $request->input('mobile'),
                        'city' => $request->input('city'),
                        'email' => $request->input('email'),
                        'img' => $img,
                        'password' => bcrypt($request->input('password')),
                    ]);
            } else {
                DB::table('member')
                    ->where('id', $request->input('id'))
                    ->update([
                        'name' => $request->input('name'),
                        'family' => $request->input('family'),
                        'mobile' => $request->input('mobile'),
                        'city' => $request->input('city'),
                        'email' => $request->input('email'),
                        'active' => $request->input('active'),
                        'active' => $active,
                        'img' => $img,
                    ]);
            }
        }
        return response()->json([], 200);
    }

    public function destroy($id)
    {
        $member = DB::table('member')
            ->where('id', '=', $id)
            ->first();

        Storage::disk('publicPath')->delete($member->img);

        DB::table('member')
            ->where('id', '=', $id)
            ->delete();
        Session::flash('alert', 'کاربر با موفقیت حذف گردید');
        return redirect('admin/members')->with(['message' => 'کاربر با موفقیت حذف گردید روش دوم']);
    }

}
