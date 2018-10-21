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
 * Referrer-Policy Header Values
 */
interface ReferrerPolicy
{
    /** @var string Referrer-Policy no-referrer */
    const NO_REFERRER = 'no-referrer';

    /** @var string Referrer-Policy no-referrer-when-downgrade */
    const NO_REFERRER_WHEN_DOWNGRADE = 'no-referrer-when-downgrade';

    /** @var string Referrer-Policy origin */
    const ORIGIN = 'origin';

    /** @var string Referrer-Policy origin-when-cross-origin */
    const ORIGIN_WHEN_CROSS_ORIGIN = 'origin-when-cross-origin';

    /** @var string Referrer-Policy same-origin */
    const SAME_ORIGIN = 'same-origin';

    /** @var string Referrer-Policy strict-origin */
    const STRICT_ORIGIN = 'strict-origin';

    /** @var string Referrer-Policy strict-origin-when-cross-origin */
    const STRICT_ORIGIN_WHEN_CROSS_ORIGIN = 'strict-origin-when-cross-origin';

    /** @var string Referrer-Policy unsafe-url */
    const UNSAFE_URL = 'unsafe-url';
}
