<?php

namespace Cake\Test\View;

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

}
