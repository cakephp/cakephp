<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Package;
use Cake\TestSuite\TestCase;

/**
 * PackageTest class
 */
class PackageTest extends TestCase
{
    /**
     * Test adding messages.
     */
    public function testAddMessage(): void
    {
        $package = new Package();

        $messages = [
            'string' => 'translation',
            'string2' => [
                'translation singular',
                'translation plural',
            ],
        ];

        $package->setMessages(['string' => $messages['string']]);
        $package->addMessage('string2', $messages['string2']);

        $this->assertEquals($messages, $package->getMessages());
    }
}
