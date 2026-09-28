<?php

namespace App\Models;

use App\Concerns\HasNormalizedName;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Its name is unique within its municipality.
 *
 * @property int $id
 * @property int $city_id
 * @property string $name
 * @property string $normalized_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read City $city
 * @property-read int|null $teachers_count
 */
#[Fillable(['city_id', 'name'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, HasNormalizedName;

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<Teacher, $this>
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
}
