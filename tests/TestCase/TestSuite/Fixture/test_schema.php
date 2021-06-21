<?php
declare(strict_types=1);

return [
    [
        'table' => 'schema_generator_comment',
        'columns' => [
            'id' => ['type' => 'integer'],
            'title' => ['type' => 'string', 'null' => true],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ],
    [
        'table' => 'schema_generator',
        'columns' => [
            'id' => ['type' => 'integer'],
            'relation_id' => ['type' => 'integer'],
            'title' => ['type' => 'string', 'null' => true],
            'body' => 'text',
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'relation_fk' => [
                'type' => 'foreign',
                'columns' => ['relation_id'],
                'references' => ['schema_generator_comment', 'id'],
            ],
        ],
        'indexes' => [
            'title_idx' => [
                'type' => 'index',
                'columns' => ['title'],
            ],
        ],
    ],
];
