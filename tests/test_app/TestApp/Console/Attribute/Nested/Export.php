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
namespace TestApp\Console\Attribute\Nested;

use Cake\Command\Command;
use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use stdClass;

/**
 * Nested command requiring a service, discovered without construction.
 */
#[AsCommand('attribute export', description: 'Export attributed data.')]
#[Argument('destination', required: true)]
#[Option('format', default: 'csv', choices: ['csv', 'json'])]
class Export extends Command
{
    /**
     * Require a dependency to exercise lazy discovery and factory injection.
     *
     * @param \stdClass $service Injected service.
     */
    public function __construct(public stdClass $service)
    {
        parent::__construct();
    }

    /**
     * Emit the parsed format to verify parser integration.
     *
     * @return int
     */
    public function execute(): int
    {
        $this->io->out($this->args->getArgument('destination') . ':' . $this->args->getOption('format'));

        return static::CODE_SUCCESS;
    }
}
