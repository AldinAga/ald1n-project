<?php

return [
    'enabled' => filter_var(env('RESTORE_CREDENTIALS_ENABLED', false), FILTER_VALIDATE_BOOL),
];
