<?php

namespace App\Concerns;

use App\Enums\QuorumType;
use App\Support\Quorum;
use Illuminate\Validation\Rule;

/**
 * The quorum fields of the assembly forms: `quorum_type` is "none" or a
 * QuorumType value, and `quorum_value` is a number of members or a
 * percentage of them.
 */
trait HasQuorumFields
{
    public string $quorum_type = 'percentage';

    public string $quorum_value = '50';

    /**
     * Members the typed quorum would require, for the hint under the field.
     */
    public function requiredMembers(int $unionMembers): ?int
    {
        $type = QuorumType::tryFrom($this->quorum_type);

        if ($type === null || ! is_numeric($this->quorum_value) || (float) $this->quorum_value <= 0) {
            return null;
        }

        return Quorum::requiredFor($type, (float) $this->quorum_value, $unionMembers);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function quorumRules(): array
    {
        return [
            'quorum_type' => ['required', Rule::in(['none', ...array_column(QuorumType::cases(), 'value')])],
            'quorum_value' => match (QuorumType::tryFrom($this->quorum_type)) {
                QuorumType::Count => ['required', 'integer', 'min:1'],
                QuorumType::Percentage => ['required', 'numeric', 'gt:0', 'max:100'],
                null => ['exclude'],
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{quorum_type: QuorumType|null, quorum_value: float|null}
     */
    protected function quorumFrom(array $validated): array
    {
        $type = QuorumType::tryFrom((string) $validated['quorum_type']);

        return [
            'quorum_type' => $type,
            'quorum_value' => $type === null ? null : (float) $validated['quorum_value'],
        ];
    }
}
