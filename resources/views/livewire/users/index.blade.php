<div>
    <x-page-header :title="__('Users')" :description="__('Who can sign in to SIMAC and what each person can do.')">
        <x-slot:actions>
            <flux:button variant="primary" icon="user-plus" wire:click="create">
                {{ __('New user') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2.5">
        <div class="min-w-56 flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Search by name or email…')"
                :aria-label="__('Search users')"
                clearable
            />
        </div>

        <div class="w-full sm:w-52">
            <flux:select wire:model.live="role" :aria-label="__('Filter by role')">
                <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
                @foreach ($roles as $roleOption)
                    <flux:select.option :value="$roleOption->value">{{ $roleOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
        @if ($users->isEmpty())
            <x-empty-state
                icon="users"
                :title="__('No users found')"
                :message="__('No user matches the search or the selected role.')"
            />
        @else
            <div class="px-5">
                <flux:table :paginate="$users" pagination:class="py-3">
                    <flux:table.columns>
                        <flux:table.column>{{ __('User') }}</flux:table.column>
                        <flux:table.column class="max-md:hidden">{{ __('Role') }}</flux:table.column>
                        <flux:table.column class="max-md:hidden">{{ __('Status') }}</flux:table.column>
                        <flux:table.column class="w-12"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" />

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                                <span class="truncate">{{ $user->name }}</span>
                                                @if ($user->is(auth()->user()))
                                                    <flux:badge size="sm" color="zinc">{{ __('You') }}</flux:badge>
                                                @endif
                                            </div>
                                            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>

                                            {{-- On narrow screens role and status live under the name. --}}
                                            <div class="mt-1.5 flex flex-wrap items-center gap-2 md:hidden">
                                                <x-users.role-badge :role="$user->assignedRole()" />
                                                <x-users.status-badge :user="$user" />
                                            </div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="max-md:hidden">
                                    <x-users.role-badge :role="$user->assignedRole()" />
                                </flux:table.cell>

                                <flux:table.cell class="max-md:hidden">
                                    <x-users.status-badge :user="$user" />
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="ellipsis-horizontal"
                                            inset="top bottom"
                                            :aria-label="__('Actions for :name', ['name' => $user->name])"
                                        />

                                        <flux:menu>
                                            <flux:menu.item icon="pencil-square" wire:click="edit({{ $user->id }})">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                            <flux:menu.item icon="key" wire:click="editPassword({{ $user->id }})">
                                                {{ __('Change password') }}
                                            </flux:menu.item>

                                            @can('deactivate', $user)
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger" icon="no-symbol" wire:click="confirmDeactivation({{ $user->id }})">
                                                    {{ __('Deactivate') }}
                                                </flux:menu.item>
                                            @endcan

                                            @can('reactivate', $user)
                                                <flux:menu.separator />
                                                <flux:menu.item icon="arrow-path" wire:click="reactivate({{ $user->id }})">
                                                    {{ __('Reactivate') }}
                                                </flux:menu.item>
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>

    {{-- Create / edit --}}
    <flux:modal name="user-form" class="w-full md:w-120">
        @php($editingSelf = $form->user?->is(auth()->user()) ?? false)

        <form wire:submit="save" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg" class="font-display">{{ $form->isEditing() ? __('Edit user') : __('New user') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $form->isEditing() ? __('Update their details and role.') : __('They will sign in with this email and password.') }}
                </flux:text>
            </div>

            <flux:input wire:model="form.name" :label="__('Name')" required autocomplete="off" />

            <flux:input wire:model="form.email" :label="__('Email address')" type="email" required autocomplete="off" />

            <flux:radio.group
                wire:model="form.role"
                :label="__('Role')"
                variant="cards"
                class="flex-col"
                :disabled="$editingSelf"
            >
                @foreach ($roles as $roleOption)
                    <flux:radio
                        :value="$roleOption->value"
                        :label="$roleOption->label()"
                        :description="$roleOption->description()"
                    />
                @endforeach
            </flux:radio.group>

            @if ($editingSelf)
                <flux:text size="sm" class="-mt-3">{{ __('You cannot change your own role.') }}</flux:text>
            @endif

            @unless ($form->isEditing())
                <flux:input
                    wire:model="form.password"
                    :label="__('Password')"
                    type="password"
                    required
                    viewable
                    autocomplete="new-password"
                />

                <flux:input
                    wire:model="form.password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    viewable
                    autocomplete="new-password"
                />
            @endunless

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">
                    {{ $form->isEditing() ? __('Save changes') : __('Create user') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Change password --}}
    <flux:modal name="user-password" class="w-full md:w-md">
        <form wire:submit="updatePassword" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Change password') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Set a new password for :name and share it with them in person.', ['name' => $passwordForm->user?->name]) }}
                </flux:text>
            </div>

            <flux:input
                wire:model="passwordForm.password"
                :label="__('New password')"
                type="password"
                required
                viewable
                autocomplete="new-password"
            />

            <flux:input
                wire:model="passwordForm.password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                viewable
                autocomplete="new-password"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Update password') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Deactivate --}}
    <flux:modal name="user-deactivate" class="w-full md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Deactivate :name?', ['name' => $deactivating?->name]) }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('They will be signed out and will not be able to sign in again until reactivated. Everything they recorded is kept.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deactivate">{{ __('Deactivate') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
