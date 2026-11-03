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
use Cake\ORM\Attribute\UseBehavior;
use Cake\ORM\Table;

/**
 * Explicit setup takes precedence over declarations for the same aliases.
 */
#[BelongsTo(UsersTable::class, foreignKey: 'declared_id')]
#[UseBehavior(ObservedBehavior::class, config: ['flag' => 'declared'])]
class ExplicitTable extends Table
{
    /**
     * Registers explicit associations and behaviors before attribute processing.
     *
     * @param array<string, mixed> $config Table construction options.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->belongsTo('Users', ['className' => UsersTable::class, 'foreignKey' => 'explicit_id']);
        $this->addBehavior('Observed', ['className' => ObservedBehavior::class, 'flag' => 'explicit']);
    }
}
