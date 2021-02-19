<?php

namespace App\Http\Controllers\Admin;

use App\lib\Jdf;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = DB::table('shop_category')
            ->orderBy('sort', 'ASC')
            ->paginate(1);

        return view('admin.category.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                // 'sort' => 'required',
                'img' => 'required',
                // 'video' => 'required',
            ],
            [
                'name.required' => 'لطفا نام دسته را انتخاب کنید',
                'sort.required' => 'لطفاترتیب بندی را وارد کنید',
                'img.required' => 'لطفا تصویر شاخص راانتخاب کنید',
                //'video.required' => 'لطفا فیلم راانتخاب کنید',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'errors' => $validator->errors()->first()
            ], 200);
        } else {

            $img = null;
            $video = null;
            if ($request->hasFile('img')) {
                $img = $this->UploadFile($request->file('img'), 'uploads/img/');
            }
            if ($request->hasFile('video')) {
                $video = $this->UploadFile($request->file('video'), 'uploads/videos/');
            }
            $sort = DB::table('shop_category')
                ->select('shop_category.sort')
                ->orderBy('id', 'DESC')
                ->first()->sort;
            DB::table('shop_category')
                ->insert([
                    'name' => $request->input('name'),
                    'sort' => $sort + 1,
                    'img' => $img,
                    'video' => $video,
                    'parent' => 0,
                    'has_next' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

            return response()->json([
                'status' => 1
            ], 200);
        }
    }

    public function destroy($id)
    {
        $category = DB::table('shop_category')
            ->where('id',  $id)
            ->first();

        Storage::disk('publicPath')->delete($category->img);
        Storage::disk('publicPath')->delete($category->video);

        DB::table('shop_category')
            ->where('id',  $id)
            ->delete();
        Session::flash('alert', 'دسته بندی با موفقیت حذف گردید');
        return back();
    }

    public function edit($id)
    {
        $category = DB::table('shop_category')
            ->where('id', $id)
            ->first();
        if (!is_null($category)) {
            return view('admin.category.edit', compact('category'));
        } else {
            abort(404);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'sort' => 'required',
            ],
            [
                'name.required' => 'لطفا نام دسته را انتخاب کنید',
                'sort.required' => 'لطفاترتیب بندی را وارد کنید',
            ]

        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'errors' => $validator->errors()->first()
            ], 200);
        } else {

            $img = $request->input('oldImage');
            $video = $request->input('oldVideo');
            if ($request->hasFile('img')) {
                Storage::disk('publicPath')->delete($request->input('oldImage'));
                $img = $this->UploadFile($request->file('img'), 'uploads/img/');
            }
            if ($request->hasFile('video')) {
                Storage::disk('publicPath')->delete($request->input('oldVideo'));
                $video = $this->UploadFile($request->file('video'), 'uploads/videos/');
            }

            DB::table('shop_category')
                ->where('id', $request->input('id_category'))
                ->update([
                    'name' => $request->input('name'),
                    'sort' => $request->input('sort'),
                    'img' => $img,
                    'video' => $video,
                    'updated_at' => Carbon::now()
                ]);


            return response()->json([
                'status' => 1
            ], 200);
        }

    }

}
