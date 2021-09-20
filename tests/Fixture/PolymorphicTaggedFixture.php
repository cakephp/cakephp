<?php
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
 * @since         3.0.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PolymorphicTaggedFixture extends TestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public string $table = 'polymorphic_tagged';

    /**
     * records property
     *
     * @var array
     */
    public array $records = [
        ['tag_id' => 1, 'foreign_key' => 1, 'foreign_model' => 'Posts', 'position' => 1],
        ['tag_id' => 1, 'foreign_key' => 1, 'foreign_model' => 'Articles', 'position' => 1],
    ];
}
