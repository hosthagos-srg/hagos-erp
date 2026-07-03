<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $guarded = [];

    // Log bersifat append-only; tidak ada updated_at.
    const UPDATED_AT = null;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /** Label modul yang lebih ramah dari nama class. */
    public function getModelLabelAttribute(): string
    {
        return class_basename($this->auditable_type);
    }
}
