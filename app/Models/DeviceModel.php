<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Geraetetyp - alles, was fuer alle baugleichen Exemplare gilt.
 * Das physische Geraet ist ein Device.
 */
class DeviceModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'manufacturer',
        'image',
        'accessories',
        'default_loan_days',
    ];

    protected $casts = [
        'default_loan_days' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function availableDevices(): HasMany
    {
        return $this->devices()->where('active', true)->whereDoesntHave('openLoan');
    }
}
