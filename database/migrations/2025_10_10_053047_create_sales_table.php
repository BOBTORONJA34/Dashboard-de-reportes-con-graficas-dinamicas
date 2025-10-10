<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
    $table->id();

    // 👇 Referencian a BIGINT UNSIGNED 'id' en categories/regions
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('region_id')->constrained()->cascadeOnDelete();

    $table->date('sold_at')->index();
    $table->unsignedInteger('quantity')->default(1);
    $table->decimal('amount', 12, 2);

    $table->timestamps();

    $table->index(['category_id', 'sold_at']);
    $table->index(['region_id', 'sold_at']);
});
    }
    public function down(): void {
        Schema::dropIfExists('sales');
    }
};
