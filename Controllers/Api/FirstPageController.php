<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class FirstPageController extends Controller
{
    public $result_json = ['success' => 0, 'error' => 0, 'error_code' => 0, 'message' => ''];

    public function index()
    {
        $Category = DB::table('shop_category')
            ->orderBy('sort', 'ASC')
            ->get();
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
        $LastProduct = DB::table('shop_product')
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->take(16)
            ->get();
        $this->result_json['success']=1;
        $this->result_json['data']['category']=$Category;
        $this->result_json['data']['BestProduct']=$BestProduct;
        $this->result_json['data']['SpecialProduct']=$SpecialProduct;
        $this->result_json['data']['LastProduct']=$LastProduct;
        return response()->json($this->result_json, 200);  /*ما در وب سرویسها فقط استاتوس 200 داریم و استاتوس 400 نداریم*/
    }

    public function SingleProduct(Request $request)
    {
        /*اینجا آیدی محصول را به صورت اینپوت دریافت میکنیم چون از متد پست استفاده کردیم زمانیکه آیدی محصول را ته یوآرال میفرستیم از متد گت استفاده میکینم*/
        $id = $request->input('id_product');
        $product = DB::table('shop_product')
            ->where('id', $id)
            ->first();
        if (!is_null($product)){
            $category = DB::table('shop_category')
                ->where('id', $product->category)
                ->first();
            $comments = DB::table('shop_comments')
                ->where('product_id', $id)
                ->get();
            $this->result_json['success']=1;
            $this->result_json['data']['product']=$product;
            $this->result_json['data']['category']=$category;
            $this->result_json['data']['comments']=$comments;
        }else{
            $this->result_json['error']=1;
            $this->result_json['error_code']=1;
            $this->result_json['message']='محصول مورد نظر شما یافت نشد';
        }
        return response()->json($this->result_json, 200);
    }

    public function ProductListBycategory(Request $request)
    {
        $id = $request->input('category_id');
        $product = DB::table('shop_product')
            ->where('category', $id)
            ->paginate($request->input('perpage'));  /*در اینجا اومدیم تعدادی که قرار هست در هر صفحه نمایش داده شود را دینامیک کردیم و به صورت اینپوت perpage دریافت میکنیم*/
        $category = DB::table('shop_category')
            ->where('id', $id)
            ->first();
        return response()->json([
            'Products' => $product,
            'category' => $category
        ]);

    }
}
