<?php

namespace App\Models;

use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A teacher's check-in at an assembly. While there is no check-out they
 * count as present.
 *
 * @property int $id
 * @property int $assembly_id
 * @property int $teacher_id
 * @property Carbon $checked_in_at
 * @property Carbon|null $checked_out_at
 * @property int|null $registered_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Assembly $assembly
 * @property-read Teacher $teacher
 */
#[Fillable(['assembly_id', 'teacher_id', 'checked_in_at', 'checked_out_at', 'registered_by'])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Assembly, $this>
     */
    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    /**
     * Retired teachers included: the record is history and keeps its teacher.
     *
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function isPresent(): bool
    {
        return $this->checked_out_at === null;
    }
}
