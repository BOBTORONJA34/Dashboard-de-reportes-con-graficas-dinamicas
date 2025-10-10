<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Category;
use App\Models\Region;

class SaleController extends Controller
{
    public function create()
    {
        return view('sales.create', [
            'categorias' => Category::orderBy('name')->get(['id','name']),
            'regiones'   => Region::orderBy('name')->get(['id','name']),
            'hoy'        => now()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required','exists:categories,id'],
            'region_id'   => ['required','exists:regions,id'],
            'sold_at'     => ['required','date'],
            'quantity'    => ['required','integer','min:1'],
            'amount'      => ['required','numeric','min:0'],
        ]);

        Sale::create($data);

        return redirect()
            ->route('dashboard')
            ->with('ok', 'Venta registrada correctamente.');
    }
}
