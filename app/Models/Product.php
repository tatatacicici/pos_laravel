<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'image',
        'type',
        'base_price',
        'cost_price',
        'track_stock',
        'current_stock',
        'alert_low_stock',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'base_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'track_stock' => 'boolean',
        'current_stock' => 'integer',
        'alert_low_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->current_stock <= $this->alert_low_stock;
    }
}
