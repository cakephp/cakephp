<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Filter;

use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\Routing\Filter\LocaleSelectorFilter;
use Cake\TestSuite\TestCase;
use Locale;

/**
 * Locale selector filter test.
 */
class LocaleSelectorFilterTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->locale = Locale::getDefault();
    }

    /**
     * Resets the default locale
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Locale::setDefault($this->locale);
    }

    /**
     * Tests selecting a language from a http header
     *
     * @return void
     * @triggers name null, [request => $request])
     * @triggers name null, [request => $request])
     * @triggers name null, [request => $request])
     */
    public function testSimpleSelection()
    {
        $filter = new LocaleSelectorFilter();
        $request = new ServerRequest([
            'environment' => ['HTTP_ACCEPT_LANGUAGE' => 'en-GB,en;q=0.8,es;q=0.6,da;q=0.4']
        ]);
        $filter->beforeDispatch(new Event('name', null, ['request' => $request]));
        $this->assertEquals('en_GB', I18n::locale());

        $request = new ServerRequest([
            'environment' => ['HTTP_ACCEPT_LANGUAGE' => 'es_VE,en;q=0.8,es;q=0.6,da;q=0.4']
        ]);
        $filter->beforeDispatch(new Event('name', null, ['request' => $request]));
        $this->assertEquals('es_VE', I18n::locale());

        $request = new ServerRequest([
            'environment' => ['HTTP_ACCEPT_LANGUAGE' => 'en;q=0.4,es;q=0.6,da;q=0.8']
        ]);
        $filter->beforeDispatch(new Event('name', null, ['request' => $request]));
        $this->assertEquals('da', I18n::locale());
    }

    /**
     * Tests selecting a language from a http header and filtering by a whitelist
     *
     * @return void
     * @triggers name null, [request => $request])
     * @triggers name null, [request => $request])
     */
    public function testWithWhitelist()
    {
        Locale::setDefault('en_US');
        $filter = new LocaleSelectorFilter([
            'locales' => ['en_CA', 'en_IN', 'es_VE']
        ]);
        $request = new ServerRequest([
            'environment' => [
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB;q=0.8,es-VE;q=0.9,da-DK;q=0.4'
            ]
        ]);
        $filter->beforeDispatch(new Event('name', null, ['request' => $request]));
        $this->assertEquals('es_VE', I18n::locale());

        Locale::setDefault('en_US');
        $request = new ServerRequest([
            'environment' => [
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB;q=0.8,es-ES;q=0.9,da-DK;q=0.4'
            ]
        ]);
        $filter->beforeDispatch(new Event('name', null, ['request' => $request]));
        $this->assertEquals('en_US', I18n::locale());
    }
}
