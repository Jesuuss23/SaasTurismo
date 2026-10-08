<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'booking_reservations';

    protected $fillable = [
        'tenant_id',
        'code',
        'client_id',
        'quote_id',
        'owner_id',
        'status',
        'travel_date_start',
        'travel_date_end',
        'pax',
        'currency',
        'fx_rate',
        'total',
        'paid_amount',
        'balance',
        'notes',
        'lock_version',
    ];

    protected $casts = [
        'travel_date_start' => 'date',
        'travel_date_end' => 'date',
        'fx_rate' => 'decimal:6',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'pax' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class, 'reservation_id');
    }
}