<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\ConsoleOptionParser;

class IntegrationCommand extends Command
{
    public function execute()
    {
        $this->io->out('arg: ' . $this->args->getArgument('arg'));
        $this->io->out('opt: ' . $this->args->getOption('opt'));
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
