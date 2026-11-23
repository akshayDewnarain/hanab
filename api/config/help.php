<?php

return [
    /*
    | Public help routes are a future application concern. Hanab keeps this
    | disabled, so a "public" visibility value never opens an anonymous route.
    */
    'public_access_enabled' => false,

    'default_visibility' => 'authenticated',

    'allow_public_visibility_selection' => false,

    'revisions_enabled' => true,

    'attachments_enabled' => true,

    'max_attachment_kilobytes' => 5120,

    'image_extensions' => ['png', 'jpg', 'jpeg', 'webp'],

    /*
    | Context keys are registered by the application. An empty list accepts any
    | well-formed key. The Help Center domain does not know Hanab routes.
    */
    'contexts' => [
        'auth.sign-in' => ['feature_path' => '/login'],
        'employees.directory' => ['feature_path' => '/admin/employees'],
        'employees.profile' => ['feature_path' => '/admin/employees'],
        'employees.edit' => ['feature_path' => '/admin/employees'],
        'employees.import' => ['feature_path' => '/admin/employees'],
        'employees.export' => ['feature_path' => '/admin/employees'],
        'organogram.management' => ['feature_path' => '/admin/organogram'],
        'organogram.location' => ['feature_path' => '/admin/organogram'],
        'skills.certificates' => ['feature_path' => '/admin/skills'],
        'messages.compose' => ['feature_path' => '/admin/messages'],
        'settings.users' => ['feature_path' => '/admin/settings/users'],
    ],
];
