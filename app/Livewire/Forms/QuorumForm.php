<?php

namespace App\Livewire\Forms;

use App\Concerns\HasQuorumFields;
use App\Enums\QuorumType;
use App\Models\Assembly;
use Illuminate\Support\Number;
use Livewire\Form;

/**
 * Adjusts the quorum of an open assembly: the figure may arrive after the
 * assembly started, or change on the way.
 */
class QuorumForm extends Form
{
    use HasQuorumFields;

    public function edit(Assembly $assembly): void
    {
        $this->resetErrorBag();

        $this->quorum_type = $assembly->quorum_type->value ?? 'none';
        $this->quorum_value = $assembly->quorum_value !== null ? (string) Number::trim($assembly->quorum_value) : '50';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{quorum_type: QuorumType|null, quorum_value: float|null}
     */
    public function attributesFrom(array $validated): array
    {
        return $this->quorumFrom($validated);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return $this->quorumRules();
    }
}
