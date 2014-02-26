<?php
namespace TestPlugin\Datasource;


class TestSource {

/**
 * Settings
 *
 * @var array
 */
	public $settings;

/**
 * Constructor
 */
	public function __construct(array $settings) {
		$this->settings = $settings;
	}

}
