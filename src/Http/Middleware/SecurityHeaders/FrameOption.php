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
 * X-Frame-Option Header Values
 */
interface FrameOption
{
    /** @var string X-Frame-Option deny */
    const DENY = 'deny';

    /** @var string X-Frame-Option sameorigin */
    const SAMEORIGIN = 'sameorigin';

    /** @var string X-Frame-Option allow-from */
    const ALLOW_FROM = 'allow-from';
}
