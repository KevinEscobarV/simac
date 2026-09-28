<?php

use App\Models\Assembly;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('assemblies', fn (User $user): bool => $user->can('followLive', Assembly::class));
