<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class IntegrationCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('arg: ' . $args->getArgument('arg'));
        $io->out('opt: ' . $args->getOption('opt'));
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->addArgument('arg', [
                'required' => true,
            ])
            ->addOption('opt', [
                'short' => 'o',
            ]);

        return $parser;
    }
}
