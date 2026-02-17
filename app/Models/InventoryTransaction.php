<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'goods_donation_id',
        'source_item_id',
        'project_id',
        'type',
        'quantity',
        'occurred_at',
        'source_name',
        'unit',
        'notes',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function goodsDonation()
    {
        return $this->belongsTo(GoodsDonation::class);
    }

    public function sourceItem()
    {
        return $this->belongsTo(Item::class, 'source_item_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

