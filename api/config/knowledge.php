<?php

return [
    /*
    | Public article routes are a future application concern. Hanab keeps this
    | disabled, so a "public" visibility value never opens an anonymous route.
    */
    'public_access_enabled' => false,

    'default_visibility' => 'authenticated',

    'allow_public_visibility_selection' => false,

    'revisions_enabled' => true,

    'attachments_enabled' => true,

    'max_attachment_kilobytes' => 10240,

    'attachment_extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'txt', 'csv', 'docx', 'xlsx'],
];
