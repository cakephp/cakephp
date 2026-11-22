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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Datasource\RulesAwareTrait;
use Cake\Datasource\RulesChecker;
use Cake\TestSuite\TestCase;

/**
 * Tests RulesAwareTrait without EventDispatcherTrait
 */
class RulesAwareTraitTest extends TestCase
{
    /**
     * Building rules must not require an event dispatcher.
     */
    public function testRulesCheckerWithoutEventDispatcher(): void
    {
        $subject = new class {
            use RulesAwareTrait;
        };

        $rules = $subject->rulesChecker();
        $this->assertInstanceOf(RulesChecker::class, $rules);
        $this->assertSame($rules, $subject->rulesChecker());
    }
}
