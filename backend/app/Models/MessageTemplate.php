<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'msg_templates';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'channel',
        'subject',
        'body_template',
        'variables',
        'status',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(MessageLog::class, 'template_id');
    }
}