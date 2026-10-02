<?php

return [
    'enabled' => filter_var(env('ROULETTE_ENABLED', false), FILTER_VALIDATE_BOOL),
];
