<?php
return [
    'columns' => [
        'name' => [
            'show' => true,
        ],
        'family' => [
            'show' => true,
        ],
        'id' => [
            'show' => true,
            'enableEdit' => false,
            'type' => 'number',
            'enableSort' => false,
        ],
        'country' => [
            'show' => true,
            'type' => 'select',
            'options' => [
                'usa' => 'США',
                'france' => 'Франция',
                'russia' => 'Россия',
                'canada' => 'Канада',
            ],
        ],
        'town' => [
            'show' => true,
            'type' => "string",
        ],
    ],    
    'tablename' => 'test',
    'tableKey' => 'id'
];
