<?php

return [

    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),

    'expires_days' => (int) env('USER_INVITATION_EXPIRES_DAYS', 7),

    'accept_path' => '/invitations/accept',

    'reset_path' => '/reset-password',

];
