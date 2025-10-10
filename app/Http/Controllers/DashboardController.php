<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Sale;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Página principal (opcional)
    |--------------------------------------------------------------------------
    | Si ya estás renderizando la vista desde otra ruta/controlador, puedes omitir.
    */
    public function index()
    {
        // Si ya pasas $categorias/$regiones desde otro lado, puedes quitar esto.
        $categorias = \App\Models\Category::orderBy('name')->get(['id','name']);
        $regiones   = \App\Models\Region::orderBy('name')->get(['id','name']);

        return view('dashboard', compact('categorias', 'regiones'));
    }

    /*
    |--------------------------------------------------------------------------
    | Exportación CSV (descarga)
    |--------------------------------------------------------------------------
    | Respeta los mismos filtros del dashboard: start, end, category_id, region_id
    */
    public function exportCsv(Request $req)
    {
        $start = $req->date('start') ?? now()->subDays(30)->startOfDay();
        $end   = $req->date('end')   ?? now()->endOfDay();
        $catId = $req->integer('category_id');
        $regId = $req->integer('region_id');

        $q = Sale::with(['category:id,name', 'region:id,name'])
                 ->whereBetween('sold_at', [$start, $end]);

        if ($catId) $q->where('category_id', $catId);
        if ($regId) $q->where('region_id', $regId);

        $filename = 'reporte_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');
            // Encabezados
            fputcsv($out, ['Fecha', 'Categoría', 'Región', 'Cantidad', 'Monto']);

            // Stream por chunks para no saturar memoria
            $q->chunk(1000, function ($chunk) use ($out) {
                foreach ($chunk as $s) {
                    fputcsv($out, [
                        $s->sold_at->format('Y-m-d'),
                        $s->category->name ?? '',
                        $s->region->name ?? '',
                        $s->quantity,
                        $s->amount,
                    ]);
                }
            });

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }
}
