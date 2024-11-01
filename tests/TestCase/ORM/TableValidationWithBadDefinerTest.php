<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use TypeError;

class TableValidationWithBadDefinerTest extends TestCase
{
    /**
     * Tests that a InvalidArgumentException is thrown if the custom validator does not return an Validator instance
     */
    public function testValidationWithBadDefiner(): void
    {
        $table = new ValidationWithBadDefinerTable();
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            'Cake\ORM\Table::buildValidator(): Argument #2 ($validator) must be of type Cake\Validation\Validator, string given',
        );

        $table->getValidator('bad');
    }
}

// phpcs:disable
class ValidationWithBadDefinerTable extends Table
{
    public function validationBad($validator): string
    {
        return '';
    }
}
// phpcs:enable
