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
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link https://cakephp.org CakePHP(tm) Project
 * @since 6.0.0
 * @license https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Stringable;

/**
 * Marks trusted markup that can be rendered verbatim in HTML content positions.
 *
 * Implementations must escape any untrusted values included in their markup.
 * This contract does not bypass attribute escaping or explicit calls to h().
 */
interface HtmlStringable extends Stringable
{
}
