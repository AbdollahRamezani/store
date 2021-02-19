<?php

namespace App\Http\Controllers\Admin;

use App\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index()
    {
        $List = Admin::all();
        return view('admin.admins.index', compact('List'));
    }

    public function create()
    {
        $Roles = Role::all();
        $Permission = Permission::all();
        return view('admin.admins.create', compact('Roles', 'Permission'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'family' => 'required',
                'email' => 'required|unique:admin',
                'password' => 'required|min:8',
            ],
            [
                'name.required' => 'لطفا نام خود را وارد نمایید',
                'family.required' => 'لطفا نام خانوادگی را وارد نمایید',
                'email.required' => 'لطفا ایمیل را وارد نمایید',
                'email.unique' => 'این ایمیل قبلا استفاده شده لطفا ایمیل جدید وارد نمایید',
                'password.required' => 'لطفا کلمه عبور را وارد نمایید',
                'password.min' => 'کلمه عبور کمتر از هشت کاراکتر میباشد لطفا دوباره وارد نمایید',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], 400);
        } else {
            $img = null;
            if ($request->hasFile('img')) {
                $img = $this->UploadFile($request->file('img'), 'uploads/img/');
            }

            $Admin = DB::table('admin')// ذخیره کاربر در دیتابیس
            ->insertGetId([
                'name' => $request->input('name'),
                'family' => $request->input('family'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
                'img' => $img,
                'updated_at' => Carbon::now()
            ]);
            DB::table('model_has_roles')// دادن رول به کاربر
            ->insert([
                'role_id' => intval($request->input('roles')),
                'model_type' => 'App\Admin',
                'model_id' => $Admin
            ]);
            if ($request->has('permissions')) {  //دادن پرمیژن ها به کاربر
                foreach ($request->input('permissions') as $value) {
                    DB::table('model_has_permissions')
                        ->insert([
                            'permission_id' => intval($value),
                            'model_type'=>'App\Admin',
                            'model_id'=>intval($Admin)

                        ]);
                }
            }
        }
        return response()->json([],200);
    }
}
