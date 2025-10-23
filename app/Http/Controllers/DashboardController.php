<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Sale;
use App\Models\Category;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Support\Str;

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

    /**
     * Importación CSV (subida)
     * Espera el mismo formato que la exportación: Fecha, Categoría, Región, Cantidad, Monto
     */
    public function importCsv(Request $req)
    {
        $req->validate([
            'csv' => 'required|file|mimes:csv,txt,text/plain,text/csv|max:10240',
        ], [
            'csv.required' => 'Selecciona un archivo CSV para importar.',
            'csv.file' => 'El archivo seleccionado no es válido.',
            'csv.mimes' => 'El archivo debe ser un CSV (extensión .csv).',
        ]);

        $file = $req->file('csv');

        if (!$file->isValid()) {
            return redirect()->back()->withErrors(['csv' => 'No se pudo cargar el archivo.']);
        }

        $imported = 0;
        $errors = [];
        $lineNumber = 0;

        $categoryCache = Category::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($category) => [Str::lower(trim($category->name)) => (int) $category->id])
            ->all();

        $regionCache = Region::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($region) => [Str::lower(trim($region->name)) => (int) $region->id])
            ->all();

        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return redirect()->back()->withErrors(['csv' => 'No se pudo leer el archivo subido.']);
        }

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $lineNumber++;

            if ($lineNumber === 1 && $this->looksLikeHeaderRow($row)) {
                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            if (count($row) < 5) {
                $errors[] = "Línea {$lineNumber}: formato incorrecto (se esperaban 5 columnas).";
                continue;
            }

            [$dateStr, $categoryName, $regionName, $quantityStr, $amountStr] = array_map('trim', array_slice($row, 0, 5));

            if ($categoryName === '' || $regionName === '') {
                $errors[] = "Línea {$lineNumber}: la categoría y la región son obligatorias.";
                continue;
            }

            try {
                $soldAt = Carbon::parse($dateStr)->startOfDay();
            } catch (\Throwable $e) {
                $errors[] = "Línea {$lineNumber}: fecha inválida ({$dateStr}).";
                continue;
            }

            $quantity = filter_var($quantityStr, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

            if ($quantity === false) {
                $errors[] = "Línea {$lineNumber}: la cantidad debe ser un número entero.";
                continue;
            }

            $amount = $this->normalizeAmount($amountStr);

            if (!is_numeric($amount)) {
                $errors[] = "Línea {$lineNumber}: el monto debe ser numérico.";
                continue;
            }

            $categoryKey = Str::lower($categoryName);
            $regionKey = Str::lower($regionName);

            if (!isset($categoryCache[$categoryKey])) {
                $category = Category::create(['name' => $categoryName]);
                $categoryCache[$categoryKey] = $category->id;
            }

            if (!isset($regionCache[$regionKey])) {
                $region = Region::create(['name' => $regionName]);
                $regionCache[$regionKey] = $region->id;
            }

            Sale::create([
                'sold_at' => $soldAt,
                'category_id' => $categoryCache[$categoryKey],
                'region_id' => $regionCache[$regionKey],
                'quantity' => $quantity,
                'amount' => $amount,
            ]);

            $imported++;
        }

        fclose($handle);

        $message = $imported === 1
            ? '1 venta importada correctamente.'
            : "{$imported} ventas importadas correctamente.";

        if ($imported === 0) {
            $message = 'No se importaron ventas.';
        }

        if ($errors) {
            $message .= ' Revisa los detalles del archivo.';
        }

        $redirect = redirect()->route('dashboard')->with('ok', $message);

        if (!empty($errors)) {
            $redirect = $redirect->with('import_errors', $errors);
        }

        return $redirect;
    }

    private function looksLikeHeaderRow(array $row): bool
    {
        $first = Str::lower($row[0] ?? '');
        $second = Str::lower($row[1] ?? '');

        return Str::contains($first, 'fecha') && (Str::contains($second, 'cat') || Str::contains($second, 'categor'));
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeAmount(string $raw): ?float
    {
        $raw = trim(str_replace(['$', ' '], '', $raw));

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^-?\d{1,3}(\.\d{3})*,\d+$/', $raw)) {
            $raw = str_replace(['.', ','], ['', '.'], $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }

        if (!is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }
}
