<?php

namespace App\Models;

use App\Concerns\HasNormalizedName;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A teacher of the roll. Retiring one soft deletes it: they leave the roll,
 * the desk and future raffles, but their history stays intact.
 *
 * @property int $id
 * @property int $school_id
 * @property string $name
 * @property string $normalized_name
 * @property string $document_number
 * @property bool $is_union_member
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $code
 * @property-read School $school
 */
#[Fillable(['school_id', 'name', 'document_number', 'is_union_member'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory, HasNormalizedName, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_union_member' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * The teacher code (SIM-001, SIM-002…) that identifies them at the desk
     * when they do not carry their ID. It is derived from the primary key,
     * so it is unique, never reused and needs no counter.
     *
     * @return Attribute<non-falsy-string, never>
     */
    protected function code(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('SIM-%03d', $this->id));
    }

    /**
     * The id behind a teacher code, accepting "SIM-012", "sim-12" or "SIM12".
     */
    public static function idFromCode(string $code): ?int
    {
        if (preg_match('/^\s*SIM-?0*(\d{1,9})\s*$/i', $code, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1] ?: null;
    }

    /**
     * Name, ID number, code, school or municipality.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $term = Str::squish($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->whereNameContains($term)
                ->orWhereHas('school', fn (Builder $school) => $school
                    ->whereNameContains($term)
                    ->orWhereHas('city', fn (Builder $city) => $city->whereNameContains($term)));

            // "1.118.541" and "1118541" are the same ID number.
            if (preg_match('/^[\d.\s-]+$/', $term) === 1) {
                $query->orWhereLike('document_number', '%'.preg_replace('/\D/', '', $term).'%');
            }

            $id = static::idFromCode($term);

            if ($id !== null) {
                $query->orWhereKey($id);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function unionMembers(Builder $query, bool $members = true): void
    {
        $query->where('is_union_member', $members);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inCity(Builder $query, int $cityId): void
    {
        $query->whereRelation('school', 'city_id', $cityId);
    }

    /**
     * Checked in at the assembly and not checked out.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function presentAt(Builder $query, Assembly $assembly): void
    {
        $query->whereHas('attendances', fn (Builder $attendances) => $attendances
            ->whereBelongsTo($assembly)
            ->whereNull('checked_out_at'));
    }
}
