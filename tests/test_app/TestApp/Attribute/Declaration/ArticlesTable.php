<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Attribute\Declaration;

use Cake\ORM\Attribute\BelongsTo;
use Cake\ORM\Attribute\BelongsToMany;
use Cake\ORM\Attribute\HasMany;
use Cake\ORM\Attribute\HasOne;
use Cake\ORM\Attribute\UseBehavior;
use Cake\ORM\Table;

/**
 * Exercises all association types and behavior construction order.
 */
#[BelongsTo(UsersTable::class, foreignKey: ['tenant_id', 'user_id'], bindingKey: ['tenant_id', 'id'])]
#[HasOne(UsersTable::class, alias: 'Profile', foreignKey: 'profile_id', dependent: false)]
#[HasMany(self::class, alias: 'Children', foreignKey: false, conditions: ['Children.active' => true], sort: ['id' => 'DESC'])]
#[BelongsToMany(UsersTable::class, alias: 'Tags', saveStrategy: 'append', through: LinksTable::class, targetForeignKey: 'tag_id')]
#[UseBehavior(ObservedBehavior::class)]
class ArticlesTable extends Table
{
    /**
     * Whether declarations were unexpectedly available during initialization.
     *
     * @var bool
     */
    public bool $associationDuringInitialize = false;

    /**
     * Sets table configuration before declarations are applied.
     *
     * @param array<string, mixed> $config Table construction options.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('declaration_articles');
        $this->setPrimaryKey('record_id');
        $this->associationDuringInitialize = $this->hasAssociation('Users');
    }
}
