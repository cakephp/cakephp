<?= '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<phpunit
	colors="true"
	processIsolation="false"
	stopOnFailure="false"
	syntaxCheck="false"
	bootstrap="./tests/bootstrap.php"
	>
	<php>
		<ini name="memory_limit" value="-1"/>
		<ini name="apc.enable_cli" value="1"/>
	</php>

	<!-- Add any additional test suites you want to run here -->
	<testsuites>
		<testsuite name="<?= $plugin ?> Test Suite">
			<directory>./tests/TestCase</directory>
		</testsuite>
	</testsuites>

	<!-- Setup a listener for fixtures -->
	<listeners>
		<listener
		class="\Cake\TestSuite\Fixture\FixtureInjector"
		file="./vendor/cakephp/cakephp/src/TestSuite/Fixture/FixtureInjector.php">
			<arguments>
				<object class="\Cake\TestSuite\Fixture\FixtureManager" />
			</arguments>
		</listener>
	</listeners>

</phpunit>
