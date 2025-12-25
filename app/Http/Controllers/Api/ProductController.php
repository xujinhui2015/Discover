<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductModel;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * 物料异步搜索接口
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $keyword = $request->input('q', '');
        $page = (int) $request->input('page', 1);
        $perPage = 10;

        $query = ProductModel::query();

        // 如果有搜索关键词，进行模糊搜索
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('py_code', 'like', "%{$keyword}%")
                    ->orWhere('item_no', 'like', "%{$keyword}%");
            });
        } else {
            // 如果没有关键词，只显示最近创建的10条
            $query->orderBy('id', 'desc');
        }

        // 分页查询
        $products = $query->paginate($perPage, ['id', 'name'], 'page', $page);

        // 格式化返回数据
        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->name,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
        ]);
    }

    /**
     * 获取最近的物料列表（默认10条）
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recent()
    {
        $products = ProductModel::query()
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get(['id', 'name']);

        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->name,
            ];
        });

        return response()->json($data);
    }
}
