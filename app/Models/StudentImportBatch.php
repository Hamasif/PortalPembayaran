<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentImportBatch extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'created_by',
        'original_filename',
        'stored_path',
        'total_rows',
        'success_count',
        'failed_count',
        'status',
        'errors',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
