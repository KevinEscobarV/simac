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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A teacher of the roll. Retiring one soft deletes it: they leave the roll,
 * the desk and future raffles, but their history stays intact. Their code
 * (only digits, assigned by the union) is what the desk asks for and what
 * the barcode on their card carries; it is never reused, not even after
 * they retire. They belong to a municipality; the school is optional,
 * since the union's roll does not always say it, and when there is one it
 * is in that same municipality.
 *
 * @property int $id
 * @property int|null $school_id
 * @property int $city_id
 * @property string $name
 * @property string $normalized_name
 * @property string $document_number
 * @property string $code
 * @property bool $is_union_member
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $short_name
 * @property-read string $place
 * @property-read School|null $school
 * @property-read City $city
 */
#[Fillable(['school_id', 'city_id', 'name', 'document_number', 'code', 'is_union_member'])]
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
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * The raffles they took part in; the pivot says whether they won.
     *
     * @return BelongsToMany<Raffle, $this, RaffleEntry>
     */
    public function raffles(): BelongsToMany
    {
        return $this->belongsToMany(Raffle::class, 'raffle_entries')
            ->using(RaffleEntry::class)
            ->withPivot('winner_position');
    }

    /**
     * First name and last surname ("María Fernanda Rojas" → "María Rojas"):
     * fits a chip or a slice of the wheel without cutting.
     *
     * @return Attribute<string, never>
     */
    protected function shortName(): Attribute
    {
        return Attribute::get(function (): string {
            $words = explode(' ', $this->name);

            return count($words) > 1 ? $words[0].' '.end($words) : $this->name;
        });
    }

    /**
     * Where they work, in a line: "IE Braulio González · Yopal", or only the
     * municipality when the roll has no school for them.
     *
     * @return Attribute<string, never>
     */
    protected function place(): Attribute
    {
        return Attribute::get(fn (): string => $this->school === null
            ? $this->city->name
            : $this->school->name.' · '.$this->city->name);
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
                ->orWhereHas('school', fn (Builder $school) => $school->whereNameContains($term))
                ->orWhereHas('city', fn (Builder $city) => $city->whereNameContains($term));

            // "1.118.541" and "1118541" are the same ID number. Codes are digits too.
            if (preg_match('/^[\d.\s-]+$/', $term) === 1) {
                $digits = preg_replace('/\D/', '', $term);

                $query->orWhereLike('document_number', '%'.$digits.'%')
                    ->orWhereLike('code', '%'.$digits.'%');
            }
        });
    }

    /**
     * Whoever has exactly this code goes first: at the desk the code is what
     * the teacher is asked for.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function exactCodeFirst(Builder $query, string $term): void
    {
        $query->orderByRaw('case when code = ? then 0 else 1 end', [Str::squish($term)]);
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
        $query->where('city_id', $cityId);
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

    /**
     * Who already won a raffle held during the assembly, or with $won false,
     * who did not.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function wonAt(Builder $query, Assembly $assembly, bool $won = true): void
    {
        $wonThere = fn (Builder $raffles) => $raffles
            ->whereBelongsTo($assembly)
            ->whereNotNull('raffle_entries.winner_position');

        $won ? $query->whereHas('raffles', $wonThere) : $query->whereDoesntHave('raffles', $wonThere);
    }
}
