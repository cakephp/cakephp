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
 * @since         3.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Routing\Url;
use Cake\TestSuite\TestCase;

/**
 * UrlTest class
 */
class UrlTest extends TestCase
{

    /**
     * testUrl
     *
     * @return void
     */
    public function testUrl()
    {
        $url = (new Url())
            ->setAction('index')
            ->setController('Users');

        $expected = [
            'action' => 'index',
            'controller' => 'Users'
        ];
        $this->assertEquals($expected, $url->toArray());
        $this->assertEquals('/Users', ((string)($url)));

        $url->absolute(true);
        $this->assertEquals('http://localhost/Users', ((string)($url)));

        $url->absolute(false);
        $url->setQueryParams(['foo' => 'bar', 'one' => 'two']);
        $this->assertEquals('/Users?foo=bar&one=two', ((string)($url)));
    }
}
