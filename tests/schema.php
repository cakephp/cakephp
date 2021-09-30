<?php
declare(strict_types=1);

/**
 * Abstract schema for CakePHP tests.
 *
 * This format resembles the existing fixture schema
 * and is converted to SQL via the Schema generation
 * features of the Database package.
 */
return [
    [
        'table' => 'binary_uuid_items',
        'columns' => [
            'id' => [
                'type' => 'binaryuuid',
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'published' => [
                'type' => 'boolean',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'unique_authors',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'first_author_id' => [
                'type' => 'integer',
                'null' => true,
            ],
            'second_author_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
            'nullable_non_nullable_unique' => [
                'type' => 'unique',
                'columns' => [
                    'first_author_id',
                    'second_author_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'articles_more_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => false,
            ],
            'subtitle' => [
                'type' => 'string',
                'null' => false,
            ],
            'body' => 'text',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'locale',
                ],
            ],
        ],
    ],
    [
        'table' => 'users',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'username' => [
                'type' => 'string',
                'null' => true,
            ],
            'password' => [
                'type' => 'string',
                'null' => true,
            ],
            'created' => [
                'type' => 'timestamp',
                'null' => true,
            ],
            'updated' => [
                'type' => 'timestamp',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'featured_tags',
        'columns' => [
            'tag_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'priority' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'tag_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'column_schema_aware_type_values',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'val' => [
                'type' => 'text',
                'null' => false,
                'comment' => 'Fixture comment',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'sections_members',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'section_id' => [
                'type' => 'integer',
            ],
            'member_id' => [
                'type' => 'integer',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'site_articles_tags',
        'columns' => [
            'article_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'site_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'UNIQUE_TAG2' => [
                'type' => 'primary',
                'columns' => [
                    'article_id',
                    'tag_id',
                    'site_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'authors_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'locale',
                ],
            ],
        ],
    ],
    [
        'table' => 'binary_uuid_items_binary_uuid_tags',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'binary_uuid_item_id' => [
                'type' => 'binaryuuid',
                'null' => false,
            ],
            'binary_uuid_tag_id' => [
                'type' => 'binaryuuid',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
            'unique_item_tag' => [
                'type' => 'unique',
                'columns' => [
                    'binary_uuid_item_id',
                    'binary_uuid_tag_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'auth_users',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'username' => [
                'type' => 'string',
                'null' => false,
            ],
            'password' => [
                'type' => 'string',
                'null' => false,
            ],
            'created' => 'datetime',
            'updated' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'counter_cache_categories',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'length' => 255,
                'null' => false,
            ],
            'post_count' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'date_keys',
        'columns' => [
            'id' => [
                'type' => 'date',
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'counter_cache_posts',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'title' => [
                'type' => 'string',
                'length' => 255,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => true,
            ],
            'category_id' => [
                'type' => 'integer',
                'null' => true,
            ],
            'published' => [
                'type' => 'boolean',
                'null' => false,
                'default' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'test_plugin_comments',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'article_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'comment' => 'text',
            'published' => [
                'type' => 'string',
                'length' => 1,
                'default' => 'N',
            ],
            'created' => 'datetime',
            'updated' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'members',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'section_count' => [
                'type' => 'integer',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'uuid_items',
        'columns' => [
            'id' => [
                'type' => 'uuid',
            ],
            'published' => [
                'type' => 'boolean',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'special_tags_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'extra_info' => [
                'type' => 'string',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'locale',
                ],
            ],
        ],
    ],
    [
        'table' => 'articles',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => true,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
            ],
            'body' => 'text',
            'published' => [
                'type' => 'string',
                'length' => 1,
                'default' => 'N',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'articles_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
            ],
            'body' => 'text',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'locale',
                ],
            ],
        ],
    ],
    [
        'table' => 'products',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'category' => [
                'type' => 'integer',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'price' => [
                'type' => 'integer',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'category',
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'orders',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'product_category' => [
                'type' => 'integer',
                'null' => false,
            ],
            'product_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
            'product_category_fk' => [
                'type' => 'foreign',
                'columns' => [
                    'product_category',
                    'product_id',
                ],
                'references' => [
                    'products',
                    [
                        'category',
                        'id',
                    ],
                ],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
        ],
        'indexes' => [
            'product_category' => [
                'type' => 'index',
                'columns' => [
                    'product_category',
                    'product_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'comments',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'article_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'comment' => [
                'type' => 'text',
            ],
            'published' => [
                'type' => 'string',
                'length' => 1,
                'default' => 'N',
            ],
            'created' => [
                'type' => 'datetime',
            ],
            'updated' => [
                'type' => 'datetime',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'datatypes',
        'columns' => [
            'id' => [
                'type' => 'biginteger',
            ],
            'cost' => [
                'type' => 'decimal',
                'length' => 20,
                'precision' => 1,
                'null' => true,
            ],
            'fraction' => [
                'type' => 'decimal',
                'length' => 20,
                'precision' => 19,
                'null' => true,
            ],
            'floaty' => [
                'type' => 'float',
                'null' => true,
            ],
            'small' => [
                'type' => 'smallinteger',
                'null' => true,
            ],
            'tiny' => [
                'type' => 'tinyinteger',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'authors',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'default' => null,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'counter_cache_comments',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'title' => [
                'type' => 'string',
                'length' => 255,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'special_tags',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'article_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'highlighted' => [
                'type' => 'boolean',
                'null' => true,
            ],
            'highlighted_time' => [
                'type' => 'timestamp',
                'null' => true,
            ],
            'extra_info' => [
                'type' => 'string',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
            'UNIQUE_TAG2' => [
                'type' => 'unique',
                'columns' => [
                    'article_id',
                    'tag_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'ordered_uuid_items',
        'columns' => [
            'id' => [
                'type' => 'string',
                'length' => 32,
            ],
            'published' => [
                'type' => 'boolean',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'counter_cache_users',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'length' => 255,
                'null' => false,
            ],
            'post_count' => [
                'type' => 'integer',
                'null' => true,
            ],
            'comment_count' => [
                'type' => 'integer',
                'null' => true,
            ],
            'posts_published' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'tags',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'description' => [
                'type' => 'text',
                'length' => 16777215,
            ],
            'created' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'articles_tags',
        'columns' => [
            'article_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'unique_tag' => [
                'type' => 'primary',
                'columns' => [
                    'article_id',
                    'tag_id',
                ],
            ],
            'tag_id_fk' => [
                'type' => 'foreign',
                'columns' => [
                    'tag_id',
                ],
                'references' => [
                    'tags',
                    'id',
                ],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
        ],
    ],
    [
        'table' => 'profiles',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'null' => false,
                'autoIncrement' => true,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'first_name' => [
                'type' => 'string',
                'null' => true,
            ],
            'last_name' => [
                'type' => 'string',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'boolean',
                'null' => false,
                'default' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'sessions',
        'columns' => [
            'id' => [
                'type' => 'string',
                'length' => 128,
            ],
            'data' => [
                'type' => 'binary',
                'length' => 16777215,
                'null' => true,
            ],
            'expires' => [
                'type' => 'integer',
                'length' => 11,
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'comments_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'comment' => 'text',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'locale',
                ],
            ],
        ],
    ],
    [
        'table' => 'menu_link_trees',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'menu' => [
                'type' => 'string',
                'null' => false,
            ],
            'lft' => [
                'type' => 'integer',
            ],
            'rght' => [
                'type' => 'integer',
            ],
            'parent_id' => 'integer',
            'url' => [
                'type' => 'string',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'polymorphic_tagged',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'tag_id' => [
                'type' => 'integer',
            ],
            'foreign_key' => [
                'type' => 'integer',
            ],
            'foreign_model' => [
                'type' => 'string',
            ],
            'position' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'things',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'title' => [
                'type' => 'string',
                'length' => 20,
            ],
            'body' => [
                'type' => 'string',
                'length' => 50,
            ],
        ],
    ],
    [
        'table' => 'site_articles',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => true,
            ],
            'site_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
            ],
            'body' => 'text',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'site_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'sections_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'locale',
                ],
            ],
        ],
    ],
    [
        'table' => 'authors_tags',
        'columns' => [
            'author_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'unique_tag' => [
                'type' => 'primary',
                'columns' => [
                    'author_id',
                    'tag_id',
                ],
            ],
            'author_id_fk' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
        ],
    ],
    [
        'table' => 'site_authors',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'default' => null,
            ],
            'site_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'site_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'i18n',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'length' => 6,
                'null' => false,
            ],
            'model' => [
                'type' => 'string',
                'null' => false,
            ],
            'foreign_key' => [
                'type' => 'integer',
                'null' => false,
            ],
            'field' => [
                'type' => 'string',
                'null' => false,
            ],
            'content' => [
                'type' => 'text',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'number_trees',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'parent_id' => 'integer',
            'lft' => [
                'type' => 'integer',
            ],
            'rght' => [
                'type' => 'integer',
            ],
            'depth' => [
                'type' => 'integer',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'composite_increments',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'null' => false,
                'autoIncrement' => true,
            ],
            'account_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'default' => null,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'account_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'tags_shadow_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'locale',
                ],
            ],
        ],
    ],
    [
        'table' => 'posts',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => false,
            ],
            'body' => 'text',
            'published' => [
                'type' => 'string',
                'length' => 1,
                'default' => 'N',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'binary_uuid_tags',
        'columns' => [
            'id' => [
                'type' => 'binaryuuid',
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'tags_translations',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'null' => false,
                'autoIncrement' => true,
            ],
            'locale' => [
                'type' => 'string',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'cake_sessions',
        'columns' => [
            'id' => [
                'type' => 'string',
                'length' => 128,
            ],
            'data' => [
                'type' => 'text',
                'null' => true,
            ],
            'expires' => [
                'type' => 'integer',
                'length' => 11,
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'attachments',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'comment_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'attachment' => [
                'type' => 'string',
                'null' => false,
            ],
            'created' => 'datetime',
            'updated' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'categories',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'parent_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'created' => 'datetime',
            'updated' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'sections',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'title' => [
                'type' => 'string',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'counter_cache_user_category_posts',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'category_id' => [
                'type' => 'integer',
            ],
            'user_id' => [
                'type' => 'integer',
            ],
            'post_count' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'site_tags',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'site_id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'site_id',
                ],
            ],
        ],
    ],
    [
        'table' => 'nullable_authors',
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
];
