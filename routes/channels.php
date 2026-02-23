<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
// Public channel for transaction updates - all authenticated users can listen
Broadcast::channel('transactions', function ($user) {
    return $user !== null; // Any authenticated user can listen
});