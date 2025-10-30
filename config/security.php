<?php

return [

    'attempts_field' => 'login_attempts',
    'max_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
    'lock_minutes' => env('LOCKOUT_DURATION', 15),
    

];
