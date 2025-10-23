<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DashboardImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_import_sales_from_csv(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'Fecha,Categoría,Región,Cantidad,Monto',
            '2024-01-01,Electrónica,Norte,5,150.25',
            '2024-01-02,Hogar,Sur,3,80.10',
        ]);

        $file = UploadedFile::fake()->createWithContent('ventas.csv', $csv);

        $response = $this->actingAs($user)->post(route('reportes.import'), [
            'csv' => $file,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseHas('sales', [
            'quantity' => 5,
            'amount' => 150.25,
        ]);
        $this->assertDatabaseHas('categories', ['name' => 'Electrónica']);
        $this->assertDatabaseHas('regions', ['name' => 'Norte']);
    }

    public function test_invalid_rows_are_reported(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'Fecha,Categoría,Región,Cantidad,Monto',
            '2024-01-01,Electrónica,,5,150.25',
            'not-a-date,Hogar,Sur,abc,80.10',
        ]);

        $file = UploadedFile::fake()->createWithContent('ventas.csv', $csv);

        $response = $this->actingAs($user)->post(route('reportes.import'), [
            'csv' => $file,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('import_errors');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_amount_formats_with_currency_and_commas_are_supported(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'Fecha,Categoría,Región,Cantidad,Monto',
            '2024-02-01,Electrónica,Norte,2,"$1,200.50"',
            '2024-02-02,Hogar,Sur,1,"1.234,56"',
        ]);

        $file = UploadedFile::fake()->createWithContent('ventas.csv', $csv);

        $response = $this->actingAs($user)->post(route('reportes.import'), [
            'csv' => $file,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('sales', ['amount' => 1200.50]);
        $this->assertDatabaseHas('sales', ['amount' => 1234.56]);
    }

    public function test_existing_catalogs_are_reused_case_insensitively(): void
    {
        $user = User::factory()->create();

        $category = Category::create(['name' => 'Electrónica']);
        $region = Region::create(['name' => 'Norte']);

        $csv = implode("\n", [
            'Fecha,Categoría,Región,Cantidad,Monto',
            '2024-03-05,ELECTRÓNICA,NORTE,1,50',
        ]);

        $file = UploadedFile::fake()->createWithContent('ventas.csv', $csv);

        $response = $this->actingAs($user)->post(route('reportes.import'), [
            'csv' => $file,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('regions', 1);
        $this->assertDatabaseHas('sales', [
            'category_id' => $category->id,
            'region_id' => $region->id,
            'quantity' => 1,
            'amount' => 50.0,
        ]);
    }
}
