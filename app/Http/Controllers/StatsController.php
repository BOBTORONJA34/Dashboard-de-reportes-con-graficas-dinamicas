<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Filtros: categorías (id, name)
     */
    public function categories()
    {
        // Tabla "categories" (nuevo esquema). Si tuvieras soft deletes, ajusta aquí.
        return DB::table('categories')->orderBy('name')->get(['id','name']);
    }

    /**
     * Filtros: regiones (id, name)
     */
    public function regions()
    {
        return DB::table('regions')->orderBy('name')->get(['id','name']);
    }

    /**
     * KPIs: revenue, tickets, avg_ticket
     * Acepta filtros: start, end, category_id, region_id, amount_min, amount_max
     */
    public function kpis(Request $req)
    {
        [$start, $end, $catId, $regId, $min, $max] = $this->readFilters($req);

        $q = DB::table('sales')
            ->when($catId, fn($qq) => $qq->where('category_id', $catId))
            ->when($regId, fn($qq) => $qq->where('region_id',   $regId))
            ->whereBetween('sold_at', [$start, $end]);

        if ($min !== null) $q->where('amount', '>=', (float)$min);
        if ($max !== null) $q->where('amount', '<=', (float)$max);

        $agg = $q->selectRaw('
            COALESCE(SUM(amount),0) as revenue,
            COUNT(*)                as tickets,
            COALESCE(SUM(amount)/NULLIF(COUNT(*),0),0) as avg_ticket
        ')->first();

        return response()->json([
            'revenue'    => (float) $agg->revenue,
            'tickets'    => (int)   $agg->tickets,
            'avg_ticket' => (float) $agg->avg_ticket,
        ]);
    }

    /**
     * Serie mensual: ventas por mes (labels YYYY-MM + data)
     * MySQL: DATE_FORMAT
     */
    public function salesByMonth(Request $req)
    {
        [$start, $end, $catId, $regId, $min, $max] = $this->readFilters($req);

        $rows = DB::table('sales')
            ->when($catId, fn($q) => $q->where('category_id', $catId))
            ->when($regId, fn($q) => $q->where('region_id',   $regId))
            ->whereBetween('sold_at', [$start, $end])
            ->when($min !== null, fn($q) => $q->where('amount', '>=', (float)$min))
            ->when($max !== null, fn($q) => $q->where('amount', '<=', (float)$max))
            ->selectRaw("DATE_FORMAT(sold_at, '%Y-%m-01') as m, SUM(amount) as total")
            ->groupBy('m')
            ->orderBy('m')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('m')->map(fn($d) => date('Y-m', strtotime($d))), // YYYY-MM
            'values' => $rows->pluck('total')->map(fn($v) => (float) $v),
        ]);
    }

    /**
     * Pie: participación por categoría (suma de amount)
     */
    public function salesShareByCategory(Request $req)
    {
        [$start, $end, $catId, $regId, $min, $max] = $this->readFilters($req);

        $rows = DB::table('sales')
            ->join('categories','categories.id','=','sales.category_id')
            ->when($catId, fn($q) => $q->where('sales.category_id', $catId)) // opcional: mostrar solo esa
            ->when($regId, fn($q) => $q->where('sales.region_id',   $regId))
            ->whereBetween('sold_at', [$start, $end])
            ->when($min !== null, fn($q) => $q->where('amount', '>=', (float)$min))
            ->when($max !== null, fn($q) => $q->where('amount', '<=', (float)$max))
            ->selectRaw('categories.name as category, SUM(amount) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('category'),
            'data'   => $rows->pluck('total')->map(fn($v) => (float) $v),
        ]);
    }

    /**
     * Export CSV (misma semántica de filtros)
     */
    public function exportCsv(Request $req)
    {
        [$start, $end, $catId, $regId, $min, $max] = $this->readFilters($req);

        $q = DB::table('sales')
            ->join('categories','categories.id','=','sales.category_id')
            ->join('regions','regions.id','=','sales.region_id')
            ->when($catId, fn($qq) => $qq->where('sales.category_id', $catId))
            ->when($regId, fn($qq) => $qq->where('sales.region_id',   $regId))
            ->whereBetween('sold_at', [$start, $end])
            ->when($min !== null, fn($q2) => $q2->where('amount','>=',(float)$min))
            ->when($max !== null, fn($q2) => $q2->where('amount','<=',(float)$max))
            ->select([
                'sales.id',
                'categories.name as category',
                'regions.name as region',
                'sales.quantity',
                'sales.amount',
                'sales.sold_at',
            ])
            ->orderBy('sold_at');

        $filename = "ventas_{$start}_a_{$end}.csv";

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Categoría','Región','Cantidad','Monto','Fecha']);
            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [$r->id,$r->category,$r->region,$r->quantity,$r->amount,$r->sold_at]);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Lee y normaliza filtros compartidos
     */
    private function readFilters(Request $req): array
    {
        $start = $req->input('start') ? date('Y-m-d', strtotime($req->input('start'))) : now()->subMonths(11)->startOfMonth()->toDateString();
        $end   = $req->input('end')   ? date('Y-m-d', strtotime($req->input('end')))   : now()->endOfMonth()->toDateString();

        // FKs
        $catId = $req->integer('category_id') ?: null;
        $regId = $req->integer('region_id')   ?: null;

        // Filtros de monto (pueden venir vacíos)
        $min = $req->input('amount_min', null);
        $max = $req->input('amount_max', null);

        return [$start, $end, $catId, $regId, $min, $max];
    }
}
