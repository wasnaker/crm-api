<?php

use Spine\Activators\DatabaseActivator;

return [
    'activator' => 'database',
    'activators' => [
        'database' => [
            'class' => DatabaseActivator::class,
        ],
    ],
];
