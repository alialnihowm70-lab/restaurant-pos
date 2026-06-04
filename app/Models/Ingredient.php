<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['name', 'unit', 'alert_threshold'];

    protected $casts = [
        'alert_threshold' => 'decimal:2',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredient')
            ->withPivot('quantity_needed');
    }
}
