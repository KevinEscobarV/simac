<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

/**
 * Keeps a lowercase, accent-free copy of the name in `normalized_name`, so
 * searching "hector" finds "Héctor" and "Támara" sorts before "Tauramena"
 * on every database engine.
 *
 * It is set by the `name` mutator rather than a model event, so it is
 * filled in seeders that run without events too.
 */
trait HasNormalizedName
{
    public static function normalizeName(string $value): string
    {
        return Str::lower(Str::ascii(Str::squish($value)));
    }

    /**
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::set(fn (string $value): array => [
            'name' => Str::squish($value),
            'normalized_name' => static::normalizeName($value),
        ]);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function orderByName(Builder $query): void
    {
        $query->orderBy($this->qualifyColumn('normalized_name'))->orderBy($this->getQualifiedKeyName());
    }

    /**
     * Every word of the term must appear in the name, in any order: "hector
     * nino" finds "Héctor Fabio Niño".
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function whereNameContains(Builder $query, string $term): void
    {
        $query->where(function (Builder $query) use ($term): void {
            foreach (explode(' ', static::normalizeName($term)) as $word) {
                $query->whereLike($this->qualifyColumn('normalized_name'), "%{$word}%");
            }
        });
    }
}
