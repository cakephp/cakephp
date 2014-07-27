<?php
/**
 * I18nTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Aura\Intl\FormatterLocator;
use Aura\Intl\Package;
use Aura\Intl\PackageLocator;
use Aura\Intl\TranslatorFactory;
use Aura\Intl\TranslatorLocator;
use Cake\I18n\Formatter\SprintfFormatter;
use Cake\I18n\TranslatorRegistry;
use Cake\TestSuite\TestCase;

/**
 * TranslatorRegistry test
 */
class TranslatorRegistryTest extends TestCase {

/**
 * Tests merging fetched translators from one locator to another
 * 
 * @return void
 */
	public function testMerge() {
		$packages = new PackageLocator([
			'first' => [
				'en_US' => function() {
					$package = new Package;
					$package->setMessages(['foo' => 'bar']);
					return $package;
				},
				'fr_FR' => function() {
					$package = new Package;
					$package->setMessages(['foo' => 'bar fr']);
					return $package;
				}
			]
		]);

		$formatters = new FormatterLocator([
			'basic' => function () { return new SprintfFormatter; },
		]);

		$factory = new TranslatorFactory;
		$translators1 = new TranslatorRegistry(
			$packages,
			$formatters,
			$factory,
			'en_US'
		);

		$packages = new PackageLocator([
			'first' => [
				'pt_PT' => function() {
					$package = new Package;
					$package->setMessages(['foo' => 'bar pt']);
					return $package;
				},
			],
			'second' => [
				'pt_PT' => function() {
					$package = new Package;
					$package->setMessages(['foo' => 'second pt']);
					return $package;
				},
			]
		]);

		$translators2 = new TranslatorRegistry(
			$packages,
			$formatters,
			$factory,
			'pt_PT'
		);

		$firstEn = $translators1->get('first');
		$firstPT = $translators2->get('first');
		$secondPT = $translators2->get('second');
		$translators1->merge($translators2);

		$this->assertSame($firstPT, $translators1->get('first', 'pt_PT'));
		$this->assertSame($secondPT, $translators1->get('second', 'pt_PT'));
		$this->assertSame($firstEn, $translators1->get('first'));
	}
}
