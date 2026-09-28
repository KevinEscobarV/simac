<?php

namespace App\Livewire\Forms;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Creates a user, or edits one when $user is set. The password is only asked
 * for on creation: afterwards it is changed with its own dialog.
 */
class UserForm extends Form
{
    use PasswordValidationRules, ProfileValidationRules;

    #[Locked]
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function edit(User $user): void
    {
        $this->resetErrorBag();

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->assignedRole()->value ?? '';
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function isEditing(): bool
    {
        return $this->user !== null;
    }

    public function roleChanged(): bool
    {
        return $this->isEditing() && $this->user->assignedRole()?->value !== $this->role;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($this->user?->id),
            'role' => ['required', Rule::enum(Role::class)],
            'password' => $this->isEditing() ? ['exclude'] : $this->passwordRules(),
        ];
    }
}
