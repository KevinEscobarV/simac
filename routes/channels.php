<?php

use App\Models\Assembly;
use App\Models\Projection;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('assemblies', fn (User $user): bool => $user->can('followLive', Assembly::class));

Broadcast::channel('projection', fn (User $user): bool => $user->can('watch', Projection::class));
