<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = DB::table('product_category')->get();
        return ProductCategoryResource::collection($categories);
    }

    public function store(ProductCategoryRequest $request)
    {
        try {
            $data = $request->all();

            DB::table('product_category')->insert([
                'name' =>  Arr::get($data, 'name'),
                'type' => Arr::get($data, 'type'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $category =  DB::table('product_category')->get()->last();
            return new ProductCategoryResource($category);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao cadastrar categoria.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }

    public function update(ProductCategoryRequest $request, string $id)
    {

        try {
            $data = $request->all();

            DB::table('product_category')->where('id', $id)
                ->update([
                    'name' => Arr::get($data, 'name'),
                    'type' => Arr::get($data, 'type'),
                    'updated_at' => now(),
                ]);
            $category = DB::table('product_category')->where('id', $id)->first();
            // dd($category);
            return new ProductCategoryResource($category);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao atualizar categoria.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }
}
