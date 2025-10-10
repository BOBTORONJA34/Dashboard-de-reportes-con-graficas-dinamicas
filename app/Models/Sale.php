<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 👈 importa el trait

class Sale extends Model
{
    use HasFactory; // 👈 habilita Sale::factory()

    protected $fillable = ['category_id','region_id','sold_at','quantity','amount'];
    protected $casts = ['sold_at' => 'date'];

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }

    public function region(): BelongsTo {
        return $this->belongsTo(Region::class);
    }
}
