<?php

class CakeTestLoader implements PHPUnit_Runner_TestSuiteLoader {

	public function load($filePath, $params = '') {
		$file = $this->_resolveTestFile($filePath, $params);

		PHPUnit_Util_Class::collectStart();
		PHPUnit_Util_Fileloader::checkAndLoad($file, false);
		$loadedClasses = PHPUnit_Util_Class::collectEnd();

		if (!empty($loadedClasses)) {
			$testCaseClass = 'PHPUnit_Framework_TestCase';

			foreach ($loadedClasses as $loadedClass) {
				$class = new ReflectionClass($loadedClass);
				$classFile = $class->getFileName();

				if ($class->isSubclassOf($testCaseClass) &&
					!$class->isAbstract()) {
					$suiteClassName = $loadedClass;
					$testCaseClass = $loadedClass;

					if ($classFile == realpath($file)) {
						break;
					}
				}

				if ($class->hasMethod('suite')) {
					$method = $class->getMethod('suite');

					if (!$method->isAbstract() &&
						$method->isPublic() &&
						$method->isStatic()) {
						$suiteClassName = $loadedClass;

						if ($classFile == realpath($file)) {
							break;
						}
					}
				}
			}
		}

		if (class_exists($suiteClassName, FALSE)) {
			$class = new ReflectionClass($suiteClassName);

			if ($class->getFileName() == realpath($file)) {
				return $class;
			}
		}
	}
	
	public function reload(ReflectionClass $aClass) {
		return $aClass;
	}

/**
 * Convert path fragments used by Cake's test runner to absolute paths that can be fed to PHPUnit.
 *
 * @return void
 */
	protected function _resolveTestFile($filePath, $params) {
		$basePath = $this->_basePath($params);
	 	return $basePath . DS . $filePath . '.test.php';
	}
	
	protected function _basePath($params) {
		$result = null;
		if (!empty($params['core'])) {
			$result = CORE_TEST_CASES;
		} elseif (!empty($params['app'])) {
			$result = APP_TEST_CASES;
		} else if (!empty($params['plugin'])) {
			$pluginPath = App::pluginPath($params['plugin']);
			$result = $pluginPath . 'tests' . DS . 'cases';
		}
		return $result;
	}
}
