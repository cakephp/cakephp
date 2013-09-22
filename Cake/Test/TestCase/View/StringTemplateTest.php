<?php

namespace Cake\Test\View;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;

class StringTemplateTest extends TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->template = new StringTemplate();
	}

/**
 * test adding templates.
 *
 * @return void
 */
	public function testAdd() {
		$templates = [
			'link' => '<a href="{{url}}">{{text}}</a>'
		];
		$result = $this->template->add($templates);
		$this->assertNull($result, 'No return');

		$this->assertEquals($templates['link'], $this->template->get('link'));
	}

/**
 * Test remove.
 *
 * @return void
 */
	public function testRemove() {
		$templates = [
			'link' => '<a href="{{url}}">{{text}}</a>'
		];
		$this->template->add($templates);
		$this->assertNull($this->template->remove('link'), 'No return');
		$this->assertNull($this->template->get('link'), 'Template should be gone.');
	}

/**
 * Test formatting strings.
 *
 * @return void
 */
	public function testFormat() {
		$templates = [
			'link' => '<a href="{{url}}">{{text}}</a>'
		];
		$this->template->add($templates);

		$result = $this->template->format('not there', []);
		$this->assertSame('', $result);

		$result = $this->template->format('link', [
			'url' => '/',
			'text' => 'example'
		]);
		$this->assertEquals('<a href="/">example</a>', $result);
	}

/**
 * Test loading templates files in the app.
 *
 * @return void
 */
	public function testLoad() {
		$this->assertEquals([], $this->template->get());
		$this->assertNull($this->template->load('test_templates'));
		$this->assertEquals('<a href="{{url}}">{{text}}</a>', $this->template->get('link'));
	}

/**
 * Test loading templates files from a plugin
 *
 * @return void
 */
	public function testLoadPlugin() {
		Plugin::load('TestPlugin');
		$this->assertNull($this->template->load('TestPlugin.test_templates'));
		$this->assertEquals('<em>{{text}}</em>', $this->template->get('italic'));
	}

/**
 * Test that loading non-existing templates causes errors.
 *
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage Could not load configuration file
 */
	public function testLoadErrorNoFile() {
		$this->template->load('no_such_file');
	}

}
