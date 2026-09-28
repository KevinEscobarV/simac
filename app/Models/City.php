<?php

namespace App\Models;

use App\Concerns\HasNormalizedName;
use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * A municipality of Casanare ("municipio" in the interface). Schools, and
 * through them the teachers of the roll, belong to one.
 *
 * @property int $id
 * @property string $name
 * @property string $normalized_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $schools_count
 * @property-read int|null $teachers_count
 */
#[Fillable(['name'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory, HasNormalizedName;

    /**
     * @return HasMany<School, $this>
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    /**
     * @return HasManyThrough<Teacher, School, $this>
     */
    public function teachers(): HasManyThrough
    {
        return $this->hasManyThrough(Teacher::class, School::class);
    }
}
