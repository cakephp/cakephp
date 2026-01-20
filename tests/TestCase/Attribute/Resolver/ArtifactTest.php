<?php
declare(strict_types=1);

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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Attribute\Resolver;

use Attribute;
use Cake\Attribute\Resolver\Artifact;
use Cake\Attribute\Resolver\Enum\AttributeTargetType;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Attribute\Resolver\ValueObject\AttributeTarget;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use stdClass;

class ArtifactTest extends TestCase
{
    protected string $tmpPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpPath = TMP . 'artifact_test_' . uniqid();
        (new Filesystem())->mkdir($this->tmpPath, 0777);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tmpPath)) {
            (new Filesystem())->deleteDir($this->tmpPath);
        }
    }

    /**
     * Test constructor accepts path and validateFiles settings
     */
    public function testConstructor(): void
    {
        $artifact = new Artifact(
            path: $this->tmpPath,
            validateFiles: true,
        );

        $this->assertInstanceOf(Artifact::class, $artifact);
    }

    /**
     * Test set() stores array of AttributeInfo and get() retrieves it
     */
    public function testSetAndGet(): void
    {
        $artifact = new Artifact(
            path: $this->tmpPath . '/test.php',
            validateFiles: false,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\TestClass',
                attributeName: stdClass::class,
                arguments: [],
                filePath: '/app/src/TestClass.php',
                lineNumber: 10,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'TestClass',
                    declaringClass: 'App\\TestClass',
                ),
                pluginName: null,
            ),
        ];

        $artifact->set($attributeInfos);
        $result = $artifact->get();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AttributeInfo::class, $result[0]);
        $this->assertSame('App\\TestClass', $result[0]->className);
    }

    /**
     * Test get() returns null when no cache exists
     */
    public function testGetReturnsNullWhenNoCacheExists(): void
    {
        $artifact = new Artifact(
            path: $this->tmpPath . '/nonexistent.php',
            validateFiles: false,
        );

        $result = $artifact->get();
        $this->assertNull($result);
    }

    /**
     * Test that generated file contains valid PHP code
     */
    public function testGenerateValidPhpCode(): void
    {
        $artifactPath = $this->tmpPath . '/valid_code.php';
        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: false,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\TestClass',
                attributeName: stdClass::class,
                arguments: ['key' => 'value'],
                filePath: '/app/src/TestClass.php',
                lineNumber: 20,
                target: new AttributeTarget(
                    type: AttributeTargetType::METHOD,
                    name: 'testMethod',
                    declaringClass: 'App\\TestClass',
                ),
                pluginName: null,
            ),
        ];

        $artifact->set($attributeInfos);

        $this->assertFileExists($artifactPath);

        // Test that the file contains valid PHP by requiring it
        $loaded = require $artifactPath;
        $this->assertIsArray($loaded);
        $this->assertCount(1, $loaded);
    }

    /**
     * Test generated file exports readonly objects and enums correctly
     */
    public function testExportsReadonlyObjectsAndEnums(): void
    {
        $artifactPath = $this->tmpPath . '/readonly_export.php';
        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: false,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\Constants',
                attributeName: Attribute::class,
                arguments: [],
                filePath: '/app/src/Constants.php',
                lineNumber: 5,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_CONSTANT,
                    name: 'CONSTANT',
                    declaringClass: 'App\\Constants',
                ),
                pluginName: 'MyPlugin',
            ),
        ];

        $artifact->set($attributeInfos);

        $this->assertFileExists($artifactPath);
        $content = (string)file_get_contents($artifactPath);

        // Verify it uses reflection syntax for readonly classes (brick/varexporter behavior)
        $this->assertStringContainsString('\\Cake\\Attribute\\Resolver\\ValueObject\\AttributeInfo', $content);
        $this->assertStringContainsString('\\Cake\\Attribute\\Resolver\\ValueObject\\AttributeTarget', $content);
        $this->assertStringContainsString('AttributeTargetType::CLASS_CONSTANT', $content);

        // Verify the file can be loaded and contains correct data
        $loaded = require $artifactPath;
        $this->assertIsArray($loaded);
        $this->assertCount(1, $loaded);
        $this->assertInstanceOf(AttributeInfo::class, $loaded[0]);
        $this->assertSame('MyPlugin', $loaded[0]->pluginName);
    }

    /**
     * Test atomic file write creates directory and writes safely
     */
    public function testAtomicFileWriteCreatesDirectory(): void
    {
        $nestedPath = $this->tmpPath . '/deeply/nested/directory/artifact.php';
        $artifact = new Artifact(
            path: $nestedPath,
            validateFiles: false,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\Entity',
                attributeName: stdClass::class,
                arguments: [],
                filePath: '/app/src/Entity.php',
                lineNumber: 15,
                target: new AttributeTarget(
                    type: AttributeTargetType::PROPERTY,
                    name: 'property',
                    declaringClass: 'App\\Entity',
                ),
                pluginName: null,
            ),
        ];

        $artifact->set($attributeInfos);

        $this->assertFileExists($nestedPath);
        $this->assertDirectoryExists(dirname($nestedPath));
    }

    /**
     * Test file validation checks modification times when enabled
     */
    public function testFileValidationChecksModificationTimes(): void
    {
        $artifactPath = $this->tmpPath . '/validation.php';
        $sourceFile = $this->tmpPath . '/source.php';

        // Create a source file
        file_put_contents($sourceFile, '<?php class TestClass {}');
        $sourceTime = filemtime($sourceFile);

        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: true,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'TestClass',
                attributeName: stdClass::class,
                arguments: [],
                filePath: $sourceFile,
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'TestClass',
                    declaringClass: 'TestClass',
                ),
                fileTime: (int)$sourceTime,
            ),
        ];

        // Set the artifact
        $artifact->set($attributeInfos);

        // Verify we can get it back
        $result = $artifact->get();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        // Touch the source file to make it newer - use future time to ensure it's definitely newer
        touch($sourceFile, $sourceTime + 2);

        // With validation enabled, get() should return null because source is newer
        $result = $artifact->get();
        $this->assertNull($result);
    }

    /**
     * Test invalid cache returns null and logs warning
     */
    public function testInvalidCacheReturnsNull(): void
    {
        $artifactPath = $this->tmpPath . '/invalid.php';

        // Create an invalid artifact file
        file_put_contents($artifactPath, '<?php return "invalid data";');

        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: false,
        );

        $result = $artifact->get();
        $this->assertNull($result);
    }

    /**
     * Test delete() removes artifact file and returns status
     */
    public function testDelete(): void
    {
        $artifactPath = $this->tmpPath . '/delete.php';
        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: false,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'Test',
                attributeName: stdClass::class,
                arguments: [],
                filePath: '/test.php',
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'Test',
                    declaringClass: 'Test',
                ),
                pluginName: null,
            ),
        ];

        $artifact->set($attributeInfos);
        $this->assertFileExists($artifactPath);

        $deleted = $artifact->delete();
        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($artifactPath);

        // Deleting again should return false
        $deleted = $artifact->delete();
        $this->assertFalse($deleted);
    }

    /**
     * Test in-memory cache speeds up repeated gets
     */
    public function testInMemoryCache(): void
    {
        $artifactPath = $this->tmpPath . '/memory_cache.php';
        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: false,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\Service',
                attributeName: stdClass::class,
                arguments: [],
                filePath: '/app/src/Service.php',
                lineNumber: 10,
                target: new AttributeTarget(
                    type: AttributeTargetType::PARAMETER,
                    name: 'param',
                    declaringClass: 'App\\Service',
                ),
                pluginName: null,
            ),
        ];

        $artifact->set($attributeInfos);

        // First get - loads from file
        $result1 = $artifact->get();
        $this->assertIsArray($result1);

        // Delete the file
        unlink($artifactPath);
        $this->assertFileDoesNotExist($artifactPath);

        // Second get - should still work from memory cache
        $result2 = $artifact->get();
        $this->assertIsArray($result2);
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test complex arguments with enums, class constants, and nested arrays
     */
    public function testComplexArgumentTypes(): void
    {
        $artifactPath = $this->tmpPath . '/complex_args.php';
        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: false,
        );

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\Controller\\UsersController',
                attributeName: 'App\\Routing\\Route',
                arguments: [
                    'path' => '/users/{id}',
                    'methods' => ['GET', 'POST'],
                    'targetType' => AttributeTargetType::METHOD,
                    'options' => [
                        'auth' => true,
                        'roles' => ['admin', 'user'],
                        'limit' => 100,
                        'ratio' => 0.75,
                        'nullable' => null,
                    ],
                ],
                filePath: '/app/src/Controller/UsersController.php',
                lineNumber: 25,
                target: new AttributeTarget(
                    type: AttributeTargetType::METHOD,
                    name: 'show',
                    declaringClass: 'App\\Controller\\UsersController',
                ),
                pluginName: null,
            ),
        ];

        $artifact->set($attributeInfos);
        $loaded = $artifact->get();

        $this->assertIsArray($loaded);
        $this->assertCount(1, $loaded);
        $this->assertSame('App\\Controller\\UsersController', $loaded[0]->className);
        $this->assertSame('/users/{id}', $loaded[0]->arguments['path']);
        $this->assertIsArray($loaded[0]->arguments['methods']);
        $this->assertSame(['GET', 'POST'], $loaded[0]->arguments['methods']);
        $this->assertInstanceOf(AttributeTargetType::class, $loaded[0]->arguments['targetType']);
        $this->assertSame(AttributeTargetType::METHOD, $loaded[0]->arguments['targetType']);
        $this->assertTrue($loaded[0]->arguments['options']['auth']);
        $this->assertSame(100, $loaded[0]->arguments['options']['limit']);
        $this->assertSame(0.75, $loaded[0]->arguments['options']['ratio']);
        $this->assertNull($loaded[0]->arguments['options']['nullable']);
    }

    /**
     * Test arguments with instantiated objects (new expressions)
     */
    public function testArgumentsWithInstantiatedObjects(): void
    {
        $artifactPath = $this->tmpPath . '/instantiated_objects.php';
        $artifact = new Artifact(
            path: $artifactPath,
            validateFiles: false,
        );

        $stdClass = new stdClass();
        $stdClass->property = 'value';
        $stdClass->number = 42;

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\Model\\Entity\\User',
                attributeName: 'App\\Validation\\ValidatedBy',
                arguments: [
                    'validator' => $stdClass,
                    'nested' => [
                        'target' => new AttributeTarget(
                            type: AttributeTargetType::PROPERTY,
                            name: 'testProp',
                            declaringClass: 'TestClass',
                        ),
                    ],
                ],
                filePath: '/app/src/Model/Entity/User.php',
                lineNumber: 15,
                target: new AttributeTarget(
                    type: AttributeTargetType::PROPERTY,
                    name: 'email',
                    declaringClass: 'App\\Model\\Entity\\User',
                ),
                pluginName: null,
            ),
        ];

        $artifact->set($attributeInfos);
        $loaded = $artifact->get();

        $this->assertIsArray($loaded);
        $this->assertCount(1, $loaded);
        $this->assertInstanceOf(stdClass::class, $loaded[0]->arguments['validator']);
        $this->assertSame('value', $loaded[0]->arguments['validator']->property);
        $this->assertSame(42, $loaded[0]->arguments['validator']->number);
        $this->assertInstanceOf(AttributeTarget::class, $loaded[0]->arguments['nested']['target']);
        $this->assertSame('testProp', $loaded[0]->arguments['nested']['target']->name);
    }

    /**
     * Test set() handles write failures gracefully with logging
     */
    #[WithoutErrorHandler]
    public function testSetHandlesWriteFailure(): void
    {
        $this->skipIf(DS === '\\', 'Cant perform operations using permissions on windows.');

        // Create a path that will fail (read-only parent directory simulation)
        $readonlyDir = $this->tmpPath . '/readonly';
        mkdir($readonlyDir, 0555);
        $artifactPath = $readonlyDir . '/subdir/artifact.php';

        $artifact = new Artifact($artifactPath);

        $attributeInfo = new AttributeInfo(
            className: 'TestClass',
            attributeName: 'TestAttribute',
            arguments: [],
            filePath: __FILE__,
            lineNumber: 1,
            target: new AttributeTarget(
                type: AttributeTargetType::CLASS_TYPE,
                name: 'TestClass',
                declaringClass: 'TestClass',
            ),
            pluginName: null,
        );

        // Should not throw, just log warning
        $artifact->set([$attributeInfo]);

        // File should not exist due to write failure
        $this->assertFileDoesNotExist($artifactPath);

        // Clean up
        chmod($readonlyDir, 0755);
    }

    /**
     * Test get() handles corrupted artifact with non-array data
     */
    public function testGetHandlesCorruptedArtifactNonArray(): void
    {
        $artifactPath = $this->tmpPath . '/corrupted.php';

        // Create corrupted artifact that returns a string instead of array
        file_put_contents($artifactPath, "<?php\nreturn 'not an array';");

        $artifact = new Artifact($artifactPath);
        $result = $artifact->get();

        $this->assertNull($result);
    }

    /**
     * Test get() handles artifact with invalid item types
     */
    public function testGetHandlesInvalidItemTypes(): void
    {
        $artifactPath = $this->tmpPath . '/invalid_items.php';

        // Create artifact with wrong object type
        $content = "<?php\nreturn [new stdClass(), new stdClass()];";
        file_put_contents($artifactPath, $content);

        $artifact = new Artifact($artifactPath);
        $result = $artifact->get();

        $this->assertNull($result);
    }

    /**
     * Test get() handles exceptions during file load
     */
    public function testGetHandlesLoadException(): void
    {
        $artifactPath = $this->tmpPath . '/exception.php';

        // Create file that throws when included
        file_put_contents($artifactPath, "<?php\nthrow new Exception('Load error');");

        $artifact = new Artifact($artifactPath);
        $result = $artifact->get();

        $this->assertNull($result);
    }

    /**
     * Test validation detects stale files when validateFiles is enabled
     */
    public function testValidationDetectsStaleFiles(): void
    {
        $sourceFile = $this->tmpPath . '/source.php';
        file_put_contents($sourceFile, '<?php class TestClass {}');

        $artifactPath = $this->tmpPath . '/validated.php';
        $artifact = new Artifact($artifactPath, validateFiles: true);

        $attributeInfo = new AttributeInfo(
            className: 'TestClass',
            attributeName: 'TestAttribute',
            arguments: [],
            filePath: $sourceFile,
            lineNumber: 1,
            target: new AttributeTarget(
                type: AttributeTargetType::CLASS_TYPE,
                name: 'TestClass',
                declaringClass: 'TestClass',
            ),
            fileTime: (int)filemtime($sourceFile),
        );

        $artifact->set([$attributeInfo]);

        // First get should work
        $result = $artifact->get();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        // Modify source file to make it newer
        sleep(1);
        touch($sourceFile);
        clearstatcache(true, $sourceFile);

        // Second get should return null due to stale artifact
        $result = $artifact->get();
        $this->assertNull($result);
    }

    /**
     * Test validation works with non-existent source files
     */
    public function testValidationWithMissingSourceFile(): void
    {
        $artifactPath = $this->tmpPath . '/missing_source.php';
        $artifact = new Artifact($artifactPath, validateFiles: true);

        $attributeInfo = new AttributeInfo(
            className: 'TestClass',
            attributeName: 'TestAttribute',
            arguments: [],
            filePath: '/non/existent/file.php',
            lineNumber: 1,
            target: new AttributeTarget(
                type: AttributeTargetType::CLASS_TYPE,
                name: 'TestClass',
                declaringClass: 'TestClass',
            ),
            fileTime: time(),
        );

        $artifact->set([$attributeInfo]);

        // Should still load successfully even if source file doesn't exist
        $result = $artifact->get();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test validation handles corrupted artifact file gracefully
     */
    public function testValidationWithCorruptedArtifact(): void
    {
        $artifactPath = $this->tmpPath . '/corrupted_validation.php';

        // Create corrupted artifact
        file_put_contents($artifactPath, "<?php\nreturn 'invalid';");

        $artifact = new Artifact($artifactPath, validateFiles: true);
        $result = $artifact->get();

        $this->assertNull($result);
    }
}
