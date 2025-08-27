<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;
    protected $table = 'items'; // Nombre explícito de la tabla
    protected $fillable = [
        'name',
        'sku',
        'description',
        'item_category_id',
        'default_unit_id',
    ];

    /**
     * Un ítem pertenece a una categoría de ítem.
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    /**
     * Un ítem tiene una unidad de medida por defecto.
     */
    public function defaultUnit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'default_unit_id');
    }

    /**
     * Un ítem tiene muchos stocks en almacenes.
     */
    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseItemStock::class);
    }

    /**
     * Un ítem tiene muchos ítems en órdenes de compra.
     */
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Un ítem tiene muchos ítems en órdenes de salida.
     */
    public function outgoingOrderItems()
    {
        return $this->hasMany(OutgoingOrderItem::class);
    }

    /**
     * Un ítem tiene muchos movimientos de stock.
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Un ítem tiene muchos ítems en conteos de stock.
     */
    public function stockCountItems()
    {
        return $this->hasMany(StockCountItem::class);
    }
}

