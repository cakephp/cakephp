<?php
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
namespace Cake\Shell;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use DirectoryIterator;

/**
 * Shell for I18N management.
 *
 * @property \Cake\Shell\Task\ExtractTask $Extract
 */
class I18nShell extends Shell
{

    /**
     * Contains tasks to load and instantiate
     *
     * @var array
     */
    public $tasks = ['Extract'];

    /**
     * @var string[]
     */
    protected $_paths;

    /**
     * Override main() for help message hook
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \Cake\Core\Exception\MissingPluginException
     * @throws \Cake\Console\Exception\StopException
     */
    public function main()
    {
        $this->out('<info>I18n Shell</info>');
        $this->hr();
        $this->out('[E]xtract POT file from sources');
        $this->out('[I]nitialize a language from POT file');
        $this->out('[H]elp');
        $this->out('[Q]uit');

        $choice = strtolower($this->in('What would you like to do?', ['E', 'I', 'H', 'Q']));
        switch ($choice) {
            case 'e':
                $this->Extract->main();
                break;
            case 'i':
                $this->init();
                break;
            case 'h':
                $this->out($this->OptionParser->help());
                break;
            case 'q':
                $this->_stop();

                return;
            default:
                $this->out('You have made an invalid selection. Please choose a command to execute by entering E, I, H, or Q.');
        }
        $this->hr();
        $this->main();
    }

    /**
     * Inits PO file from POT file.
     *
     * @param string|null $language Language code to use.
     * @return void
     * @throws \Cake\Console\Exception\StopException
     */
    public function init($language = null)
    {
        if (!$language) {
            $language = $this->in('Please specify language code, e.g. `en`, `eng`, `en_US` etc.');
        }
        if (strlen($language) < 2) {
            $this->abort('Invalid language code. Valid is `en`, `eng`, `en_US` etc.');
        }

        $this->_paths = App::path('Locale');
        if ($this->param('plugin')) {
            $plugin = Inflector::camelize($this->param('plugin'));
            $this->_paths = App::path('Locale', $plugin);
        }

        $response = $this->in('What folder?', null, rtrim($this->_paths[0], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $sourceFolder = rtrim($response, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $targetFolder = $sourceFolder . $language . DIRECTORY_SEPARATOR;
        if (!is_dir($targetFolder)) {
            mkdir($targetFolder, 0775, true);
        }

        $count = 0;
        $iterator = new DirectoryIterator($sourceFolder);
        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }
            $filename = $fileinfo->getFilename();
            $newFilename = $fileinfo->getBasename('.pot');
            $newFilename .= '.po';

            $this->createFile($targetFolder . $newFilename, file_get_contents($sourceFolder . $filename));
            $count++;
        }

        $this->out('Generated ' . $count . ' PO files in ' . $targetFolder);
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     * @throws \Cake\Console\Exception\ConsoleException
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $initParser = [
            'options' => [
                'plugin' => [
                    'help' => 'Plugin name.',
                    'short' => 'p'
                ],
                'force' => [
                    'help' => 'Force overwriting.',
                    'short' => 'f',
                    'boolean' => true
                ]
            ],
            'arguments' => [
                'language' => [
                    'help' => 'Two-letter language code.'
                ]
            ]
        ];

        $parser->setDescription(
            'I18n Shell generates .pot files(s) with translations.'
        )->addSubcommand('extract', [
            'help' => 'Extract the po translations from your application',
            'parser' => $this->Extract->getOptionParser()
        ])
        ->addSubcommand('init', [
            'help' => 'Init PO language file from POT file',
            'parser' => $initParser
        ]);

        return $parser;
    }
}
