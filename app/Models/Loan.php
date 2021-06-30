<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $casts = ['status' => 'boolean'];

    protected $guarded = [];

    /**
     * @return HasMany
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class);
    }

    /**
     * Get remaining Amount to be paid
     *
     * @return float
     */
    public function getRemainingAmountAttribute(): float
    {
        return (float)$this->attributes['total_amount'] - $this->attributes['total_amount_paid'];
    }
}
