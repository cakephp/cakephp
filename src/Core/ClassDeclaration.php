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
namespace Cake\Core;

use InvalidArgumentException;

/**
 * Describes a class-backed dependency without constructing its runtime instance.
 *
 * @internal
 */
abstract readonly class ClassDeclaration
{
    /**
     * Stores the target class, optional registry alias, and construction options.
     *
     * @param class-string $className Target implementation class.
     * @param string|null $alias Explicit registry alias.
     * @param array<string, mixed> $config Construction options.
     */
    public function __construct(
        public string $className,
        public ?string $alias = null,
        public array $config = [],
    ) {
    }

    /**
     * Returns the explicit alias or infers it from the target's short class name.
     *
     * @return string
     */
    public function getAlias(): string
    {
        if ($this->alias !== null) {
            return $this->alias;
        }

        [, $name] = namespaceSplit($this->className);
        $suffix = $this->getSuffix();

        return str_ends_with($name, $suffix) ? substr($name, 0, -strlen($suffix)) : $name;
    }

    /**
     * Returns options for the existing registry or association loader.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return ['className' => $this->className] + $this->config;
    }

    /**
     * Rejects empty aliases and conflicting options without loading the target class.
     *
     * @return void
     */
    public function validate(): void
    {
        if ($this->getAlias() === '') {
            throw new InvalidArgumentException('A declaration must have a non-empty alias.');
        }
        if (isset($this->config['className']) && $this->config['className'] !== $this->className) {
            throw new InvalidArgumentException('The config className conflicts with the declared target class.');
        }
    }

    /**
     * Returns the owner types that can consume this declaration.
     *
     * @return list<class-string>
     */
    abstract public function getOwnerClasses(): array;

    /**
     * Returns the registry domain in which aliases must be unique.
     *
     * @return string
     */
    abstract public function getDomain(): string;

    /**
     * Returns the conventional class suffix removed when inferring an alias.
     *
     * @return string
     */
    abstract protected function getSuffix(): string;
}
