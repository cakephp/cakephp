<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware\SecurityHeaders;

/**
 * X-XSS-Protection Header Values
 */
interface XssProtection
{
    /** @var string X-XSS-Protection block, sets enabled with block */
    const BLOCK = 'block';

    /** @var string X-XSS-Protection enabled with block */
    const ENABLED_BLOCK = '1; mode=block';

    /** @var string X-XSS-Protection enabled */
    const ENABLED = '1';

    /** @var string X-XSS-Protection disabled */
    const DISABLED = '0';
}
