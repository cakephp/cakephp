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

/**
 * Wrap trusted markup for rendering in HTML content positions without escaping it.
 *
 * This function marks markup as trusted; it does not sanitize it.
 *
 * @param string $html The trusted HTML.
 * @return \Cake\View\HtmlString
 */
function html(string $html): HtmlString
{
    return new HtmlString($html);
}
