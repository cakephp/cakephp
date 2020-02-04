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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Form;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\Form\ContextFactory;

/**
 * ContextFactory test case.
 */
class ContextFactoryTest extends TestCase
{
    public function testGetException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No context provider found for value of type `boolean`.'
            . ' Use `null` as 1st argument of FormHelper::create() to create a context-less form.'
        );

        $factory = new ContextFactory();
        $factory->get(new ServerRequest(), ['entity' => false]);
    }
}
