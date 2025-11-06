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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use function Cake\Core\deprecationWarning;

/**
 * Fixture strategy that truncates all fixture tables at the end of test.
 *
 * @deprecated 5.2.10 Use {@link \Cake\TestSuite\Fixture\TruncateStrategy} instead.
 *   Will be removed in 5.3.0.
 */
class TruncateFixtureStrategy extends TruncateStrategy
{
    /**
     * Initialize strategy.
     */
    public function __construct()
    {
        deprecationWarning(
            '5.2.10',
            'TruncateFixtureStrategy is deprecated. Use TruncateStrategy instead.',
        );

        parent::__construct();
    }
}
