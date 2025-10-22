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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\ConsoleOptionParser;

/**
 * Command for updating counter cache.
 */
class CounterCacheCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'counter_cache';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Update counter cache for a model.';
    }

    /**
     * Execute the command.
     *
     * Updates the counter cache for the specified model and association based
     * on the model's counter cache behavior's configuration.
     *
     * @return int The exit code or null for success
     */
    public function execute(): int
    {
        $table = $this->fetchTable($this->args->getArgument('model'));

        if (!$table->hasBehavior('CounterCache')) {
            $this->io->error('The specified model does not have the CounterCache behavior attached.');

            return static::CODE_ERROR;
        }

        $methodArgs = [];
        if ($this->args->hasOption('assoc')) {
            $methodArgs['assocName'] = $this->args->getOption('assoc');
        }
        if ($this->args->hasOption('limit')) {
            $methodArgs['limit'] = (int)$this->args->getOption('limit');
        }
        if ($this->args->hasOption('page')) {
            $methodArgs['page'] = (int)$this->args->getOption('page');
        }

        /** @var \Cake\ORM\Table<array{CounterCache: \Cake\ORM\Behavior\CounterCacheBehavior}> $table */
        $table->getBehavior('CounterCache')->updateCounterCache(...$methodArgs);

        $this->io->success('Counter cache updated successfully.');

        return static::CODE_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(static::getDescription())
            ->addArgument('model', [
                'help' => 'The model to update the counter cache for.',
                'required' => true,
            ])->addOption('assoc', [
                'help' => 'The association to update the counter cache for. By default all associations are updated.',
                'short' => 'a',
                'default' => null,
            ])
            ->addOption('limit', [
                'help' => 'The number of records to update per page/iteration',
                'short' => 'l',
                'default' => null,
            ])
            ->addOption('page', [
                'help' => 'The page/iteration number. By default all records will be updated one page at a time.',
                'short' => 'p',
                'default' => null,
            ]);

        return $parser;
    }
}
