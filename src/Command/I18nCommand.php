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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\ConsoleOptionParser;

/**
 * Command for interactive I18N management.
 */
class I18nCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'I18n commands let you generate .pot files to power translations in your application.';
    }

    /**
     * Execute interactive mode
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $this->io->out('<info>I18n Command</info>');
        $this->io->hr();
        $this->io->out('[E]xtract POT file from sources');
        $this->io->out('[I]nitialize a language from POT file');
        $this->io->out('[H]elp');
        $this->io->out('[Q]uit');

        do {
            $choice = strtolower($this->io->askChoice('What would you like to do?', ['E', 'I', 'H', 'Q']));
            $code = null;
            switch ($choice) {
                case 'e':
                    $code = $this->executeCommand(I18nExtractCommand::class, []);
                    break;
                case 'i':
                    $code = $this->executeCommand(I18nInitCommand::class, []);
                    break;
                case 'h':
                    $this->io->out($this->getOptionParser()->help());
                    break;
                case 'q':
                    // Do nothing
                    break;
                default:
                    $this->io->err(
                        'You have made an invalid selection. ' .
                        'Please choose a command to execute by entering E, I, H, or Q.',
                    );
            }
            if ($code === static::CODE_ERROR) {
                $this->abort();
            }
        } while ($choice !== 'q');

        return static::CODE_SUCCESS;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(static::getDescription());

        return $parser;
    }
}
