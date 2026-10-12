<?php

namespace App\Models;

use App\Enums\Semester;
use App\Enums\TermStatus;
use Database\Factories\AcademicTermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicTerm extends Model
{
    /** @use HasFactory<AcademicTermFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academic_year',
        'semester',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'status' => TermStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function label(): string
    {
        return sprintf('%s — Semester %s', $this->academic_year, $this->semester->label());
    }
}
