<?php

namespace App\Livewire\Forms;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * An administrator sets a new password for another user, for when they forget
 * it and there is no email to recover it with.
 */
class UserPasswordForm extends Form
{
    use PasswordValidationRules;

    #[Locked]
    public ?User $user = null;

    public string $password = '';

    public string $password_confirmation = '';

    public function for(User $user): void
    {
        $this->resetErrorBag();

        $this->user = $user;
        $this->password = '';
        $this->password_confirmation = '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'password' => $this->passwordRules(),
        ];
    }
}
