<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    public function deviceModels(): HasMany
    {
        return $this->hasMany(DeviceModel::class);
    }

    /** Alle Exemplare dieser Kategorie, ueber die Geraetetypen hinweg. */
    public function devices(): HasManyThrough
    {
        return $this->hasManyThrough(Device::class, DeviceModel::class);
    }
}
