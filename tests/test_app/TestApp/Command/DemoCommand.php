<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

class DemoCommand extends Command
{
    public static function getDescription(): string
    {
        return 'This is a demo command';
    }

    public function execute(): ?int
    {
        $this->io->quiet('Quiet!');
        $this->io->out('Demo Command!');
        $this->io->verbose('Verbose!');
        if ($this->args->hasArgumentAt(0)) {
            $this->io->out($this->args->getArgumentAt(0));
        }

        return null;
    }
}
