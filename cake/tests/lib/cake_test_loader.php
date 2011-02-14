<?php

class CakeTestLoader implements PHPUnit_Runner_TestSuiteLoader {

/**
 * Load a file and find the first test case / suite in that file.
 *
 * @param string $filePath 
 * @param string $params 
 * @return ReflectionClass
 */
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

/**
 * Reload method.
 *
 * @param ReflectionClass $aClass 
 * @return void
 */
	public function reload(ReflectionClass $aClass) {
		return $aClass;
	}

/**
 * Convert path fragments used by Cake's test runner to absolute paths that can be fed to PHPUnit.
 *
 * @return void
 */
	protected function _resolveTestFile($filePath, $params) {
		$basePath = $this->_basePath($params) . DS . $filePath;
		$ending = '.test.php';
		return (strpos($basePath, $ending) === (strlen($basePath) - strlen($ending))) ? $basePath : $basePath . $ending;
	}

/**
 * Generates the base path to a set of tests based on the parameters.
 *
 * @param array $params 
 * @return string The base path.
 */
	protected static function _basePath($params) {
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

/**
 * Get the list of files for the test listing.
 *
 * @return void
 */
	public static function generateTestList($params) {
		$directory = self::_basePath($params);
		$fileList = self::_getRecursiveFileList($directory);

		$testCases = array();
		foreach ($fileList as $testCaseFile) {
			$testCases[$testCaseFile] = str_replace($directory . DS, '', $testCaseFile);
		}
		return $testCases;
	}

/**
 * Gets a recursive list of files from a given directory and matches then against
 * a given fileTestFunction, like isTestCaseFile()
 *
 * @param string $directory The directory to scan for files.
 * @param mixed $fileTestFunction
 */
	protected static function _getRecursiveFileList($directory = '.') {
		$fileList = array();
		if (!is_dir($directory)) {
			return $fileList;
		}

		$files = new RegexIterator(
			new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)),
			'/.*\.test.php$/'
		);

		foreach ($files as $file) {
			$fileList[] = $file->getPathname();
		}
		return $fileList;
	}

}
