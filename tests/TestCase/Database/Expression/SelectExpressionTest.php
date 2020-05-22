<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\SelectExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use DateTime;

/**
 * Tests SelectExpression class
 */
class SelectExpressionTest extends TestCase
{
    /**
     * Tests generating a select expression
     *
     * @return void
     */
    public function testSelectExpression()
    {
        $se = (new SelectExpression([]))->add(
            ['test' => 'literal', 'string', new DateTime('2020-05-21')],
            [null, 'string', 'date']
        );
        $this->assertSame('test, :se0, :se1', $se->sql(new ValueBinder()));
    }

    /**
     * Tests generating a select expression
     *
     * @return void
     */
    public function testSelectExpressionModifiers()
    {
        $se = (new SelectExpression([]))->add(
            ['test' => 'literal', 'string', new DateTime('2020-05-21')],
            [null, 'string', 'date']
        )->addModifier('DISTINCT')->addModifier('HIGH_PRIORITY');
        $this->assertSame('DISTINCT HIGH_PRIORITY test, :se0, :se1', $se->sql(new ValueBinder()));

        $se->removeModifier('HIGH_PRIORITY');
        $this->assertSame(['DISTINCT'], $se->getModifiers());

        $se->addModifier('SQL_CALC_FOUND_ROWS');
        $this->assertSame(['DISTINCT' => true,'SQL_CALC_FOUND_ROWS' => true], $se->getModifiers(false));
    }
}
