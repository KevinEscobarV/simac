<?php

namespace App\Livewire\Users;

use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Enums\Role;
use App\Livewire\Forms\UserForm;
use App\Livewire\Forms\UserPasswordForm;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    public UserForm $form;

    public UserPasswordForm $passwordForm;

    #[Locked]
    public ?User $deactivating = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', User::class);

        $this->form->reset();
        $this->form->resetErrorBag();

        Flux::modal('user-form')->show();
    }

    public function edit(User $user): void
    {
        $this->authorize('update', $user);

        $this->form->edit($user);

        Flux::modal('user-form')->show();
    }

    public function save(CreateUser $createUser, UpdateUser $updateUser): void
    {
        $editing = $this->form->user;

        if ($editing) {
            $this->authorize('update', $editing);

            if ($this->form->roleChanged()) {
                $this->authorize('changeRole', $editing);
            }
        } else {
            $this->authorize('create', User::class);
        }

        $validated = $this->form->validate();
        $role = Role::from($validated['role']);

        if ($editing) {
            $updateUser->handle($editing, $validated['name'], $validated['email'], $role);
        } else {
            $createUser->handle($validated['name'], $validated['email'], $validated['password'], $role);
        }

        Flux::modal('user-form')->close();
        Flux::toast(variant: 'success', text: $editing ? __('User updated.') : __('User created.'));

        $this->form->reset();
    }

    public function editPassword(User $user): void
    {
        $this->authorize('update', $user);

        $this->passwordForm->for($user);

        Flux::modal('user-password')->show();
    }

    public function updatePassword(): void
    {
        $user = $this->passwordForm->user;

        $this->authorize('update', $user);

        $validated = $this->passwordForm->validate();

        $user->update(['password' => $validated['password']]);

        Flux::modal('user-password')->close();
        Flux::toast(variant: 'success', text: __('Password updated.'));

        $this->passwordForm->reset();
    }

    public function confirmDeactivation(User $user): void
    {
        $this->authorize('deactivate', $user);

        $this->deactivating = $user;

        Flux::modal('user-deactivate')->show();
    }

    public function deactivate(): void
    {
        $this->authorize('deactivate', $this->deactivating);

        $this->deactivating->deactivate();

        Flux::modal('user-deactivate')->close();
        Flux::toast(text: __(':name can no longer sign in.', ['name' => $this->deactivating->name]));

        $this->deactivating = null;
    }

    public function reactivate(User $user): void
    {
        $this->authorize('reactivate', $user);

        $user->reactivate();

        Flux::toast(variant: 'success', text: __(':name can sign in again.', ['name' => $user->name]));
    }

    public function render(): View
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('name', "%{$this->search}%")
                    ->orWhereLike('email', "%{$this->search}%"),
            ))
            ->when(Role::tryFrom($this->role), fn (Builder $query, Role $role) => $query->role($role))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::cases(),
        ])->title(__('Users'));
    }
}
