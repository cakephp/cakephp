<%
/**
 * Test Case bake template
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Utility\Inflector;

$isController = strtolower($type) === 'controller';
%>
<?php
namespace <%= $baseNamespace; %>\Test\TestCase\<%= $subNamespace %>;

<% foreach ($uses as $dependency): %>
use <%= $dependency; %>;
<% endforeach; %>
<% if ($isController): %>
use Cake\TestSuite\IntegrationTestCase;
<% else: %>
use Cake\TestSuite\TestCase;
<% endif; %>

/**
 * <%= $fullClassName %> Test Case
 */
<% if ($isController): %>
class <%= $className %>Test extends IntegrationTestCase {
<% else: %>
class <%= $className %>Test extends TestCase {
<% endif; %>

<% if (!empty($fixtures)): %>
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = [<%= $this->Bake->stringifyList($fixtures) %>];

<% endif; %>
<% if (!empty($construction)): %>
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
<%= $preConstruct ? "\t\t" . $preConstruct : '' %>
		$this-><%= $subject . ' = ' . $construction %>
<%= $postConstruct ? "\t\t" . $postConstruct : '' %>
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this-><%= $subject %>);

		parent::tearDown();
	}

<% endif; %>
<% foreach ($methods as $method): %>
/**
 * Test <%= $method %> method
 *
 * @return void
 */
	public function test<%= Inflector::camelize($method) %>() {
		$this->markTestIncomplete('Not implemented yet.');
	}

<% endforeach; %>
<% if (empty($methods)): %>
/**
 * Test initial setup
 *
 * @return void
 */
	public function testInitialization() {
		$this->markTestIncomplete('Not implemented yet.');
	}

<% endif; %>
}
