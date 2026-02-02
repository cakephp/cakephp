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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Attribute\Resolver;

use Cake\Attribute\Resolver\Enum\AttributeTargetType;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Attribute\Resolver\ValueObject\AttributeTarget;
use Generator;
use PhpToken;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use SplFileInfo;
use Throwable;

class Parser
{
    /**
     * Cache for attribute constructor reflections.
     *
     * @var array<string, \ReflectionMethod|null>
     */
    private array $constructorCache = [];

    /**
     * @param array<string> $excludeAttributes
     */
    public function __construct(
        protected array $excludeAttributes = [],
    ) {
    }

    /**
     * Parse a PHP file and extract all attributes.
     *
     * @param \SplFileInfo $file File object to parse
     * @param string|null $pluginName Plugin name if file belongs to a plugin
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    public function parseFile(SplFileInfo $file, ?string $pluginName = null): Generator
    {
        $realFilePath = $file->getRealPath();
        if ($realFilePath === false) {
            return;
        }

        try {
            $fileTime = $file->getMTime();
            $classes = $this->getClassesFromFile($realFilePath);

            foreach ($classes as $className) {
                try {
                    if (!$this->isTypeLoaded($className)) {
                        continue;
                    }
                    assert(
                        class_exists($className) || interface_exists($className) ||
                        trait_exists($className) || enum_exists($className),
                    );
                    $reflection = new ReflectionClass($className);

                    // Skip classes not from this file
                    $reflectionFile = $reflection->getFileName();
                    if ($reflectionFile === false || realpath($reflectionFile) !== $realFilePath) {
                        continue;
                    }

                    yield from $this->parseClass($reflection, $realFilePath, $fileTime, $pluginName);
                } catch (Throwable) {
                    // Skip classes that fail reflection
                }
            }
        } catch (Throwable) {
            // Skip files that fail parsing
        }
    }

    /**
     * Extract class names from a PHP file.
     *
     * Uses token parsing to safely detect classes, interfaces, traits, and enums.
     * Then loads them either via autoloader or direct file inclusion.
     *
     * @param string $filePath File path (should be normalized with realpath)
     * @return array<string>
     */
    protected function getClassesFromFile(string $filePath): array
    {
        $classNames = $this->getClassNamesFromTokens($filePath);

        // Try to load classes via autoloader (PSR-4 compliant only)
        foreach ($classNames as $className) {
            if ($this->isTypeLoaded($className)) {
                continue;
            }

            $this->loadType($className);
        }

        return $classNames;
    }

    /**
     * Extract class name from PHP file using token parsing.
     *
     * Leverages PSR-4 constraint: one class per file. Returns immediately
     * when the first class/interface/trait/enum is found.
     *
     * @param string $filePath File path to parse
     * @return array<string> Fully qualified class name (single element array)
     */
    protected function getClassNamesFromTokens(string $filePath): array
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            return [];
        }

        $tokens = PhpToken::tokenize($code);
        $namespace = '';
        $waitingForNamespace = false;
        $waitingForClass = false;

        foreach ($tokens as $i => $token) {
            // Detect namespace declaration
            if ($token->id === T_NAMESPACE) {
                $waitingForNamespace = true;
                continue;
            }

            // Capture namespace name
            if ($waitingForNamespace && ($token->id === T_NAME_QUALIFIED || $token->id === T_STRING)) {
                $namespace = $token->text;
                $waitingForNamespace = false;
                continue;
            }

            // Detect class/interface/trait/enum declaration
            if (in_array($token->id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                // Skip anonymous classes
                if ($token->id === T_CLASS && $this->isAnonymousClass($tokens, $i)) {
                    continue;
                }
                $waitingForClass = true;
                continue;
            }

            // Capture class name and return immediately (PSR-4: one class per file)
            if ($waitingForClass && $token->id === T_STRING) {
                $className = $token->text;
                $fullyQualifiedName = $namespace !== '' ? $namespace . '\\' . $className : $className;

                return [$fullyQualifiedName];
            }
        }

        return [];
    }

    /**
     * Check if T_CLASS token represents an anonymous class.
     *
     * Anonymous classes are preceded by the 'new' keyword.
     *
     * @param array<\PhpToken> $tokens All tokens from PhpToken::tokenize()
     * @param int $currentIndex Current token index
     * @return bool True if anonymous class
     */
    protected function isAnonymousClass(array $tokens, int $currentIndex): bool
    {
        // Look backward for 'new' keyword (skip whitespace/comments)
        for ($i = $currentIndex - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if ($token->id === T_NEW) {
                return true;
            }
            if (!in_array($token->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return false;
            }
        }

        return false;
    }

    /**
     * Check if a type (class/interface/trait/enum) is already loaded.
     *
     * @param string $typeName Type name to check
     * @return bool True if type is loaded
     */
    protected function isTypeLoaded(string $typeName): bool
    {
        return class_exists($typeName, false)
            || interface_exists($typeName, false)
            || trait_exists($typeName, false)
            || enum_exists($typeName, false);
    }

    /**
     * Try to load a type via autoloader.
     *
     * @param string $typeName Type name to load
     * @return bool True if type was loaded
     */
    protected function loadType(string $typeName): bool
    {
        return class_exists($typeName)
            || interface_exists($typeName)
            || trait_exists($typeName)
            || enum_exists($typeName);
    }

    /**
     * Parse attributes from a class and its members.
     *
     * @param \ReflectionClass $reflection Class reflection
     * @param string $filePath File path
     * @param int $fileTime File modification time
     * @param string|null $pluginName Plugin name
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    protected function parseClass(
        ReflectionClass $reflection,
        string $filePath,
        int $fileTime,
        ?string $pluginName,
    ): Generator {
        $className = $reflection->getName();
        $startLine = $reflection->getStartLine();

        // Parse class-level attributes
        yield from $this->parseAttributes(
            $reflection->getAttributes(),
            $className,
            $filePath,
            $startLine === false ? 0 : $startLine,
            $fileTime,
            new AttributeTarget(AttributeTargetType::CLASS_TYPE, $className),
            $pluginName,
        );

        // Parse method attributes
        foreach ($reflection->getMethods() as $method) {
            yield from $this->parseMethod($method, $filePath, $fileTime, $className, $pluginName);
        }

        // Parse property attributes
        foreach ($reflection->getProperties() as $property) {
            yield from $this->parseProperty($property, $filePath, $fileTime, $className, $pluginName);
        }

        // Parse constant attributes
        foreach ($reflection->getReflectionConstants() as $constant) {
            yield from $this->parseConstant($constant, $filePath, $fileTime, $className, $pluginName);
        }
    }

    /**
     * Parse method attributes.
     *
     * @param \ReflectionMethod $method Method reflection
     * @param string $filePath File path
     * @param int $fileTime File modification time
     * @param string $className Declaring class name
     * @param string|null $pluginName Plugin name
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    protected function parseMethod(
        ReflectionMethod $method,
        string $filePath,
        int $fileTime,
        string $className,
        ?string $pluginName,
    ): Generator {
        $startLine = $method->getStartLine();
        $target = new AttributeTarget(
            AttributeTargetType::METHOD,
            $method->getName(),
            $className,
        );

        yield from $this->parseAttributes(
            $method->getAttributes(),
            $className,
            $filePath,
            $startLine === false ? 0 : $startLine,
            $fileTime,
            $target,
            $pluginName,
        );

        foreach ($method->getParameters() as $parameter) {
            yield from $this->parseParameter(
                $parameter,
                $filePath,
                $fileTime,
                $className,
                $method->getName(),
                $pluginName,
            );
        }
    }

    /**
     * Parse property attributes.
     *
     * @param \ReflectionProperty $property Property reflection
     * @param string $filePath File path
     * @param int $fileTime File modification time
     * @param string $className Declaring class name
     * @param string|null $pluginName Plugin name
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    protected function parseProperty(
        ReflectionProperty $property,
        string $filePath,
        int $fileTime,
        string $className,
        ?string $pluginName,
    ): Generator {
        $target = new AttributeTarget(
            AttributeTargetType::PROPERTY,
            $property->getName(),
            $className,
        );

        yield from $this->parseAttributes(
            $property->getAttributes(),
            $className,
            $filePath,
            0, // Properties don't have reliable line numbers
            $fileTime,
            $target,
            $pluginName,
        );
    }

    /**
     * Parse parameter attributes.
     *
     * @param \ReflectionParameter $parameter Parameter reflection
     * @param string $filePath File path
     * @param int $fileTime File modification time
     * @param string $className Declaring class name
     * @param string $methodName Method name
     * @param string|null $pluginName Plugin name
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    protected function parseParameter(
        ReflectionParameter $parameter,
        string $filePath,
        int $fileTime,
        string $className,
        string $methodName,
        ?string $pluginName,
    ): Generator {
        $declaringFunction = $parameter->getDeclaringFunction();
        $startLine = $declaringFunction instanceof ReflectionMethod ? $declaringFunction->getStartLine() : false;

        $target = new AttributeTarget(
            AttributeTargetType::PARAMETER,
            $parameter->getName(),
            $className . '::' . $methodName,
        );

        yield from $this->parseAttributes(
            $parameter->getAttributes(),
            $className,
            $filePath,
            $startLine === false ? 0 : $startLine,
            $fileTime,
            $target,
            $pluginName,
        );
    }

    /**
     * Parse class constant attributes.
     *
     * @param \ReflectionClassConstant $constant Constant reflection
     * @param string $filePath File path
     * @param int $fileTime File modification time
     * @param string $className Declaring class name
     * @param string|null $pluginName Plugin name
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    protected function parseConstant(
        ReflectionClassConstant $constant,
        string $filePath,
        int $fileTime,
        string $className,
        ?string $pluginName,
    ): Generator {
        $target = new AttributeTarget(
            AttributeTargetType::CONSTANT,
            $constant->getName(),
            $className,
        );

        yield from $this->parseAttributes(
            $constant->getAttributes(),
            $className,
            $filePath,
            0, // Constants don't have reliable line numbers
            $fileTime,
            $target,
            $pluginName,
        );
    }

    /**
     * Parse reflection attributes and convert to AttributeInfo.
     *
     * @param array<\ReflectionAttribute> $attributes Reflection attributes
     * @param string $className Class name
     * @param string $filePath File path
     * @param int $lineNumber Line number
     * @param int $fileTime File modification time
     * @param \Cake\Attribute\Resolver\ValueObject\AttributeTarget $target Attribute target
     * @param string|null $pluginName Plugin name
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    protected function parseAttributes(
        array $attributes,
        string $className,
        string $filePath,
        int $lineNumber,
        int $fileTime,
        AttributeTarget $target,
        ?string $pluginName,
    ): Generator {
        foreach ($attributes as $attribute) {
            $attributeName = ltrim($attribute->getName(), '\\');

            if ($this->shouldExclude($attributeName)) {
                continue;
            }

            yield new AttributeInfo(
                className: $className,
                attributeName: $attributeName,
                arguments: $this->extractAttributeArguments($attribute),
                filePath: $filePath,
                lineNumber: $lineNumber,
                target: $target,
                fileTime: $fileTime,
                pluginName: $pluginName,
            );
        }
    }

    /**
     * Extract named arguments from a reflection attribute.
     *
     * @param \ReflectionAttribute $attribute Reflection attribute
     * @return array<string, mixed> Named arguments array
     */
    protected function extractAttributeArguments(ReflectionAttribute $attribute): array
    {
        try {
            $rawArgs = $attribute->getArguments();
            $constructor = $this->getAttributeConstructor($attribute->getName());

            if (!$constructor instanceof ReflectionMethod) {
                return $rawArgs;
            }

            $parameters = $constructor->getParameters();
            $namedArgs = [];

            // Map arguments to parameter names
            foreach ($rawArgs as $index => $value) {
                if (is_string($index)) {
                    // Already a named argument
                    $namedArgs[$index] = $value;
                } elseif (isset($parameters[$index])) {
                    // Positional argument - map to parameter name
                    $namedArgs[$parameters[$index]->getName()] = $value;
                }
            }

            return $namedArgs;
        } catch (Throwable) {
            // Fallback to raw arguments
            return $attribute->getArguments();
        }
    }

    /**
     * Get the constructor for an attribute class (cached).
     *
     * @param string $attributeName Attribute class name
     * @return \ReflectionMethod|null Constructor or null if none exists
     */
    protected function getAttributeConstructor(string $attributeName): ?ReflectionMethod
    {
        if (!array_key_exists($attributeName, $this->constructorCache)) {
            try {
                if (!class_exists($attributeName)) {
                    $this->constructorCache[$attributeName] = null;

                    return null;
                }
                $attributeClass = new ReflectionClass($attributeName);
                $this->constructorCache[$attributeName] = $attributeClass->getConstructor();
            } catch (Throwable) {
                $this->constructorCache[$attributeName] = null;
            }
        }

        return $this->constructorCache[$attributeName];
    }

    /**
     * Check if an attribute should be excluded.
     *
     * @param string $attributeName Attribute name
     * @return bool True if should be excluded
     */
    protected function shouldExclude(string $attributeName): bool
    {
        return array_any($this->excludeAttributes, function (string $pattern) use ($attributeName): bool {
            // Exact match
            if ($pattern === $attributeName) {
                return true;
            }

            // Wildcard prefix match: "App\Internal\*" or "App\Attribute*" matches with prefix
            if (str_ends_with($pattern, '*')) {
                $prefix = substr($pattern, 0, -1); // Remove "*"
                if (str_starts_with($attributeName, $prefix)) {
                    return true;
                }
            }

            return false;
        });
    }
}
