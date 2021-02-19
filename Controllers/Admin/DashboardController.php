<?php

namespace App\Http\Controllers\Admin;

use App\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Spatie\Permission\Models\Role;              // این خط خودم برای پرمیژن ها اضافه کردم
use Spatie\Permission\Models\Permission;       // این خط خودم برای پرمیژن ها اضافه کردم

class DashboardController extends Controller
{
    public function index()
    {
        //$role = Role::create(['name' => 'writer']);    // ساختن یک رول مثلا رول نویسنده

        // $permission = Permission::create(['name' => 'view product']);   // ساختن پرمیژن مثلا دیدن لیست محصولات
        // $permission = Permission::create(['name' => 'create product']);  // ساختن پرمیژن مثلا ساختن محصولات
        // $permission = Permission::create(['name' => 'edit product']);    // ساختن پرمیژن مثلا ویرایش محصولات
        // $permission = Permission::create(['name' => 'delete product']);   // ساختن پرمیژن مثلا حذف محصولات


        // $role = Role::find(3);  /*پیدا کردن رول مورد نظر از طریق آیدی همون رول*/
        //$permission=permission::find(11);     /*پیدا کردن پرمیژن موردنظر از طریق آیدی همون پرمیژن*/
        // $role->givePermissionTo($permission);  // به رول مورد نظر پرمیژن مورد نظر را میدهیم که داخل جدول role_has_permissions ذخیره میشود

        //$role = Role::find(3);       // انتخاب یک رول از روی آیدی که اینجا آیدی سه رول سوپر ادمین است
        // $permissions=Permission::all();      // نتخاب تمام پرمیژن ها
        // $role->syncPermissions($permissions);      // دادن تمامی پرمیژن ها به یک رول از این دستور به صورت مولتی استفاده میشود

        // Admin::find(1)->assignRole('SuperAdmin');    //دادن رول به آیدی یک کاربر

        return view('admin.index');
    }
}
