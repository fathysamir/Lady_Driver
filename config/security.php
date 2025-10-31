<?php

return [
    'max_attempts' => env('SECURITY_MAX_ATTEMPTS', 5),
    'lock_minutes' => env('SECURITY_LOCK_MINUTES',15),
    'log_channel'  => env('SECURITY_LOG_CHANNEL', 'auth'),
];
