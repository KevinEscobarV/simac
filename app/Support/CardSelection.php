<?php

namespace App\Support;

use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which cards to print: one teacher, or a batch by municipality, school and
 * union membership. The same selection counts the cards on the page and
 * lists them on the sheet to print, where it travels in the URL.
 */
final readonly class CardSelection
{
    /** Cards that fit on a letter sheet: two columns of four. */
    public const int PER_SHEET = 8;

    public function __construct(
        public ?int $cityId = null,
        public ?int $schoolId = null,
        public ?bool $unionMembers = null,
        public ?int $teacherId = null,
    ) {}

    /**
     * From the query string of the page or the sheet. Anything that is not a
     * valid value is left out, so a bad link prints a wider batch instead of
     * failing.
     *
     * @param  array<array-key, mixed>  $query
     */
    public static function fromQuery(array $query): self
    {
        $id = fn (string $key): ?int => is_string($query[$key] ?? null) && ctype_digit($query[$key]) ? (int) $query[$key] : null;

        return new self(
            cityId: $id('municipio'),
            schoolId: $id('colegio'),
            unionMembers: match ($query['afiliacion'] ?? null) {
                'si' => true,
                'no' => false,
                default => null,
            },
            teacherId: $id('docente'),
        );
    }

    /**
     * The query string that brings the same selection back.
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        return array_filter([
            'municipio' => $this->cityId,
            'colegio' => $this->schoolId,
            'afiliacion' => match ($this->unionMembers) {
                true => 'si',
                false => 'no',
                null => null,
            },
            'docente' => $this->teacherId,
        ], fn (string|int|null $value): bool => $value !== null);
    }

    /**
     * The active teachers selected, sorted to be handed out: by municipality,
     * then school, then name.
     *
     * @return Builder<Teacher>
     */
    public function teachers(): Builder
    {
        return Teacher::query()
            ->with(['school', 'city'])
            ->when($this->teacherId !== null, fn (Builder $query) => $query->whereKey($this->teacherId))
            ->when($this->cityId !== null, fn (Builder $query) => $query->inCity((int) $this->cityId))
            ->when($this->schoolId !== null, fn (Builder $query) => $query->where('school_id', $this->schoolId))
            ->when($this->unionMembers !== null, fn (Builder $query) => $query->unionMembers((bool) $this->unionMembers))
            ->orderBy(City::query()
                ->select('normalized_name')
                ->whereColumn('cities.id', 'teachers.city_id'))
            ->orderBy(School::query()
                ->select('normalized_name')
                ->whereColumn('schools.id', 'teachers.school_id'))
            ->orderByName();
    }
}
