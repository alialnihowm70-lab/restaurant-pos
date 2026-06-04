<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransaction extends Model
{
    use HasUuids;

    protected $fillable = ['product_id', 'location_id', 'quantity', 'unit_cost', 'source_id', 'type'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'source_id');
    }

    public function childTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'source_id');
    }
}
