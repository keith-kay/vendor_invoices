<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = ['name', 'kra_pin', 'vendor_code'];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
