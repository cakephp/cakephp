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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Cake\Validation\ValidatorAwareInterface;
use Cake\Validation\ValidatorAwareTrait;

class ValidatorAwareTest extends TestCase
{
    public function testStandaloneObjectCanUseDefaultValidatorWithoutProvider(): void
    {
        $object = new class implements ValidatorAwareInterface {
            use ValidatorAwareTrait;

            public function validationDefault(Validator $validator): Validator
            {
                return $validator->notEmptyString('title');
            }
        };
        $validator = $object->getValidator();
        $this->assertSame($validator, $object->getValidator('default'));
        $this->assertNotEmpty($validator->validate(['title' => '']));
        $this->assertSame([], $validator->validate(['title' => 'Cake']));
    }
}
