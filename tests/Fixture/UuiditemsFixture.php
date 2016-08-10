<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class UuiditemFixture
 */
class UuiditemsFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'uuid'],
        'published' => ['type' => 'boolean', 'null' => false],
        'name' => ['type' => 'string', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'published' => 0, 'name' => 'Item 1'],
        ['id' => '48298a29-81c0-4c26-a7fb-413140cf8569', 'published' => 0, 'name' => 'Item 2'],
        ['id' => '482b7756-8da0-419a-b21f-27da40cf8569', 'published' => 0, 'name' => 'Item 3'],
        ['id' => '482cfd4b-0e7c-4ea3-9582-4cec40cf8569', 'published' => 0, 'name' => 'Item 4'],
        ['id' => '4831181b-4020-4983-a29b-131440cf8569', 'published' => 0, 'name' => 'Item 5'],
        ['id' => '483798c8-c7cc-430e-8cf9-4fcc40cf8569', 'published' => 0, 'name' => 'Item 6']
    ];
}
