<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'booking_passengers';

    protected $fillable = [
        'tenant_id',
        'reservation_id',
        'first_name',
        'last_name',
        'doc_type',
        'doc_number',
        'nationality',
        'birth_date',
        'passenger_type',
        'is_lead',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_lead' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }
}