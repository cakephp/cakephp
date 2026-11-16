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

use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use DirectoryIterator;

/**
 * Command for interactive I18N management.
 */
#[AsCommand('i18n init', description: 'Initialize a language PO file from the POT file.')]
#[Option('plugin', short: 'p', help: 'The plugin to create a PO file in.')]
#[Argument('language', help: 'Two-letter language code to create PO files for.')]
class I18nInitCommand extends Command
{
    /**
     * Execute the command
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $language = $this->args->getArgument('language');
        if (!$language) {
            $language = $this->io->ask('Please specify language code, e.g. `en`, `eng`, `en_US` etc.');
        }
        if (strlen($language) < 2) {
            $this->io->error('Invalid language code. Valid is `en`, `eng`, `en_US` etc.');

            return static::CODE_ERROR;
        }

        $paths = array_values(App::path('locales'));
        if ($this->args->hasOption('plugin')) {
            $plugin = Inflector::camelize((string)$this->args->getOption('plugin'));
            $paths = [Plugin::path($plugin) . 'resources' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR];
        }

        $response = $this->io->ask('What folder?', rtrim($paths[0], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $sourceFolder = rtrim($response, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $targetFolder = $sourceFolder . $language . DIRECTORY_SEPARATOR;
        if (!is_dir($targetFolder)) {
            mkdir($targetFolder, 0o777 ^ umask(), true);
        }

        $count = 0;
        $iterator = new DirectoryIterator($sourceFolder);
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $filename = $fileInfo->getFilename();
            $newFilename = $fileInfo->getBasename('.pot');
            $newFilename .= '.po';

            $content = file_get_contents($sourceFolder . $filename);
            if ($content === false) {
                throw new CakeException(sprintf('Cannot read file content of `%s`', $sourceFolder . $filename));
            }
            $this->io->createFile($targetFolder . $newFilename, $content);
            $count++;
        }

        $this->io->out('Generated ' . $count . ' PO files in ' . $targetFolder);

        return static::CODE_SUCCESS;
    }
}
