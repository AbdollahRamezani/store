<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = DB::table('shop_product');
        $products->join('shop_category', 'shop_product.category', 'shop_category.id');
        $products->select('shop_product.*', 'shop_category.name as category_name');
        $products->orderBy('shop_product.id', 'DESC');

        if ($request->has('search')) {
            $products->where('title', 'LIKE', '%' . $request->input('search') . '%');
            $products->orWhere('price', 'LIKE', '%' . $request->input('search') . '%');
            $products->orWhere('name', 'LIKE', '%' . $request->input('search') . '%');
        }
        $products = $products->paginate(3);
        return view('admin.product.index', compact('products'));
    }

    public function create()
    {
        $category = DB::table('shop_category')
            ->get();
        return view('admin.product.create', compact('category'));
    }

    public function store(Request $request)
    {
        $sort = DB::table('shop_product')
            ->select('shop_product.sort')
            ->orderBy('id', 'DESC')
            ->first()->sort; /*بعد از first هرمقداری که از جدول خواسته باشیم میتوانیم دریافت کنیم که ما اینجا sort دریافت کردیم*/

        $validator = Validator::make(
            $request->all(),
            [
                'product' => 'required',
                'description_change' => 'required',
                'category' => 'required',
                'status' => 'required',
                'count' => 'required',
                //'sort' => 'required',
                'unit' => 'required',
                'price' => 'required',
                //'dis_price' => 'required',
                'special' => 'required',
                'delivery_type' => 'required',
                'min' => 'required',
            ],
            [
                'product.required' => 'لطفا نام محصول را وارد کنید',
                'description_change.required' => 'لطفا توضیحات محصول را وارد کنید',
                'category.required' => 'لطفا دسته بندی محصول را وارد کنید',
                'count.required' => 'لطفا تعداد محصول را وارد کنید',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'errors' => $validator->errors()->all()
            ], 400);
        }

        if ($request->has('discount')) {
            $discount = 1;
            $dis_price = $request->input('dis_price');
        } else {
            $discount = 0;
            $dis_price = null;
        }
        $product_id = DB::table('shop_product')// Dastor "insertGetId" id mahsooli ke zakhire karde ra dakhel product_id mirizeh
        ->insertGetId([
            'title' => $request->input('product'),
            'description' => $request->input('description_change'),
            'category' => $request->input('category'),
            'img' => $this->UploadFile($request->file('img'), 'uploads/img/'),
            'status' => $request->input('status'),
            'count' => $request->input('count'),
            'sort' => $sort + 1,
            'unit' => $request->input('unit'),
            'discount' => $discount,
            'price' => $request->input('price'),
            // 'dis_price' => $request->input('dis_price'),
            'dis_price' => $dis_price,
            'special' => $request->input('special'),
            'min' => $request->input('min'),
            'count_buy' => 0,
            'delivery_type' => $request->input('delivery_type'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        if ($request->has('detailTitle')) {
            foreach ($request->input('detailTitle') as $key => $value) {
                DB::table('shop_product_detail')
                    ->insert([
                        'name' => $value,
                        'sort' => $sort_detail + 1,
                        'value' => $request->input('detailData')[$key],
                        'id_post' => $product_id
                    ]);

            }
        }
        if ($request->has('gallery')) {
            foreach ($request->file('gallery') as $value) {
                DB::table('shop_product_gallery')
                    ->insert([
                        'post_id' => $product_id,
                        'img' => $this->UploadFile($value, 'uploads/img/')
                    ]);
            }
        }
        return response()->json([
            'status' => 1
        ], 200);

    }

    public function edit($id)
    {
        $product = DB::table('shop_product')
            ->where('id', $id)
            ->first();
        if (!is_null($product)) {
            $detail = DB::table('shop_product_detail')
                ->where('id_post', $product->id)
                ->get();

            $Gallery = DB::table('shop_product_gallery')
                ->where('post_id', $product->id)
                ->get();

            $category = DB::table('shop_category')
                ->get();

            return view('admin.product.edit', compact('product','detail', 'Gallery', 'category'));
        } else {
            abort(404);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'product' => 'required',
                'description_change' => 'required',
                'category' => 'required',
                'status' => 'required',
                'count' => 'required',
                'sort' => 'required',
                'unit' => 'required',
                'price' => 'required',
                //'dis_price' => 'required',
                'special' => 'required',
                'delivery_type' => 'required',
                'min' => 'required',
            ],
            [
                'product.required' => 'لطفا نام محصول را وارد کنید',
                'description_change.required' => 'لطفا توضیحات محصول را وارد کنید',
                'category.required' => 'لطفا دسته بندی محصول را وارد کنید',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'errors' => $validator->errors()->first()
            ], 200);
        }
        if ($request->hasFile('img')) {
            Storage::disk('publicPath')->delete($request->input('oldImg'));
            $img = $this->UploadFile($request->file('img'), 'uploads/img/');
        } else {
            $img = $request->input('oldImg');
        }

        if ($request->has('discount')) {
            $discount = 1;
        } else {
            $discount = 0;
        }
        DB::table('shop_product')
            ->where('id', $request->input('id'))
            ->update([
                'title' => $request->input('product'),
                'description' => $request->input('description_change'),
                'category' => $request->input('category'),
                'img' => $img,
                'status' => $request->input('status'),
                'count' => $request->input('count'),
                'sort' => $request->input('sort'),
                'unit' => $request->input('unit'),
                'discount' => $discount,
                'price' => $request->input('price'),
                'dis_price' => $request->input('dis_price'),
                'special' => $request->input('special'),
                'min' => $request->input('min'),
                'delivery_type' => $request->input('delivery_type'),

                'updated_at' => Carbon::now(),
            ]);

        DB::table('shop_product_detail')
            ->where('id_post', $request->input('id'))
            ->delete();
        /*قبل از هرچیز تمامی جزئیات محصول را حذف میکنیم تا دوباره جزئیات محصول به صورت تکراری ذخیره نشودو چون
          داخل ولیوو اینپوتهای جزئیات محصول مقدار داریم,جزئیات محصول دوباره از اول ذخیره میشود  */

        foreach ($request->input('detailTitle') as $key => $value) {
            DB::table('shop_product_detail')
                ->insert([
                    'name' => $value,
                    'value' => $request->input('detailData')[$key],
                    'id_post' => $request->input('id')
                ]);
        }

        $images = DB::table('shop_product_gallery')
            ->where('post_id', $request->input('id'))
            ->pluck('img')->toArray();

        DB::table('shop_product_gallery')
            ->where('post_id', $request->input('id'))
            ->delete();
        /* قبل از چیزی تمامی گالری را حذف میکنیم تا تکراری ذخیره نشود و چون
         دوتا اینپوت یکی اینپوت هیدن فایلهای قدیم و یکی اینپوت فایهای جدید داریم پس تمامی گالری از اول ذخیره میشود*/

        if ($request->has('oldGalleryfiles')) {
            foreach ($request->input('oldGalleryfiles') as $value) {
                if (($key = array_search($value, $images)) !== false) {
                    unset($images[$key]);
                }
                DB::table('shop_product_gallery')
                    ->insert([
                        'post_id' => $request->input('id'),
                        'img' => $value
                    ]);
            }
        }

        foreach ($images as $value) {
            Storage::disk('publicPath')->delete($value);
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $value) {
                DB::table('shop_product_gallery')
                    ->insert([
                        'post_id' => $request->input('id'),
                        'img' => $this->UploadFile($value, 'uploads/img/')
                    ]);
            }
        }
        return response()->json([
            'status' => 1
        ], 200);
    }


    public function destroy($id)
    {
        DB::table('shop_product_detail')
            ->where('id_post', $id)
            ->delete();

        $images = DB::table('shop_product_gallery')
            ->where('post_id', $id)
            ->get();
        foreach ($images as $value) {
            Storage::disk('publicPath')->delete($value->img);
        }
        DB::table('shop_product_gallery')
            ->where('post_id', $id)
            ->delete();

        $image = DB::table('shop_product')
            ->where('id', '=', $id)
            ->first();
        Storage::disk('publicPath')->delete($image->img);

        DB::table('shop_product')
            ->where('id', $id)
            ->delete();

        Session::flash('alert', 'محصول با موفقیت حذف گردید');
        return back();
    }
}
