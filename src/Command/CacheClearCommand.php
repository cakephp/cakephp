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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Cache\Cache;
use Cake\Cache\Engine\ApcuEngine;
use Cake\Cache\Exception\InvalidArgumentException;
use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;

/**
 * CacheClear command.
 */
#[AsCommand('cache clear', description: 'Clear all data in a single cache engine.')]
#[Argument(
    'engine',
    help: 'The cache engine to clear.' .
        'For example, `cake cache clear _cake_model_` will clear the model cache.' .
        ' Use `cake cache list` to list available engines.',
    required: true,
)]
class CacheClearCommand extends Command
{
    /**
     * Implement this method with your command's logic.
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $name = (string)$this->args->getArgument('engine');
        try {
            $this->io->out("Clearing {$name}");

            $engine = Cache::pool($name);
            Cache::clear($name);
            if ($engine instanceof ApcuEngine) {
                $this->io->warning("ApcuEngine detected: Cleared {$name} CLI cache successfully " .
                    "but {$name} web cache must be cleared separately.");
            } else {
                $this->io->out("<success>Cleared {$name} cache</success>");
            }
        } catch (InvalidArgumentException $e) {
            $this->io->error($e->getMessage());
            $this->abort();
        }

        return static::CODE_SUCCESS;
    }
}
