<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{id}', function ($user, $id) {
    $conversation = Conversation::find($id);

    if (!$conversation) {
        return false;
    }

    if ($user->id === $conversation->candidate_user_id || $user->id === $conversation->employer_user_id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    return false;
});

Broadcast::channel('conversation.{id}.typing', function ($user, $id) {
    $conversation = Conversation::find($id);

    if (!$conversation) {
        return false;
    }

    if ($user->id === $conversation->candidate_user_id || $user->id === $conversation->employer_user_id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    return false;
});
