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
use Cake\ORM\Attribute\HasMany;

/**
 * Replaces an inherited declaration while keeping its original position.
 */
#[BelongsTo(UsersTable::class, foreignKey: 'child_user_id')]
#[HasMany(UsersTable::class, alias: 'Editors')]
class ChildTable extends BaseTable
{
}
