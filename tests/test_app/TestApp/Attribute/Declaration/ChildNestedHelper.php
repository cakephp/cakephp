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

use Cake\View\Attribute\UseHelper;
use TestApp\View\Helper\TestBeforeAfterHelper;

/**
 * Overrides an inherited dependency and requests its callbacks when supported.
 */
#[UseHelper(TestBeforeAfterHelper::class, alias: 'Observer', config: ['flag' => 'child', 'enabled' => true])]
class ChildNestedHelper extends NestedHelper
{
}
