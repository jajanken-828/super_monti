<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'manufacturing_order_id',
        'sales_order_id',          // NEW — linked when knitting operator marks JO done
        'machine_id',
        'yarn_type',
        'weight',
        'remarks',
        'operator_id',
        'shift',
        'processed_at',
        'status',
    ];

    protected $casts = [
        'weight'       => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function manufacturingOrder()
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    /**
     * The Sales Order / Job Order this fabric was produced for.
     * Set by the knitting operator when marking a JO as knitting-done.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
}