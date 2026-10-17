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
use Cake\Console\Helper\ProgressHelper;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\Database\Type\Attribute\Label;
use Cake\I18n\Extractor\EnumLabelExtractor;
use Cake\I18n\Extractor\ExtractedMessage;
use Cake\I18n\Extractor\ExtractionDiagnostic;
use Cake\I18n\Extractor\FunctionExtractor;
use Cake\I18n\Extractor\TranslatableMessageExtractor;
use Cake\Utility\Filesystem;
use Cake\Utility\Fs\Finder;
use Cake\Utility\Inflector;

/**
 * Language string extractor
 */
class I18nExtractCommand extends Command
{
    /**
     * Paths to use when looking for strings
     *
     * @var array<string>
     */
    protected array $paths = [];

    /**
     * Files from where to extract
     *
     * @var array<string>
     */
    protected array $files = [];

    /**
     * Merge all domain strings into the default.pot file
     *
     * @var bool
     */
    protected bool $merge = false;

    /**
     * Contains all content waiting to be written
     *
     * @var array<string, mixed>
     */
    protected array $storage = [];

    /**
     * Extracted strings indexed by domain.
     *
     * @var array<string, mixed>
     */
    protected array $translations = [];

    /**
     * Destination path
     *
     * @var string
     */
    protected string $output = '';

    /**
     * An array of directories to exclude.
     *
     * @var array<string>
     */
    protected array $exclude = [];

    /**
     * Holds whether this call should extract the CakePHP Lib messages
     *
     * @var bool
     */
    protected bool $extractCore = false;

    /**
     * Displays marker error(s) if true
     *
     * @var bool
     */
    protected bool $markerError = false;

    /**
     * Count number of marker errors found
     *
     * @var int
     */
    protected int $countMarkerError = 0;

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'i18n extract';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Extract i18n POT files from application source files.';
    }

    /**
     * Method to interact with the user and get path selections.
     *
     * @return void
     */
    protected function getPaths(): void
    {
        $defaultPaths = array_merge(
            [APP],
            array_values(App::path('templates')),
            ['D'], // This is required to break the loop below
        );
        $defaultPathIndex = 0;
        while (true) {
            $currentPaths = $this->paths === [] ? ['None'] : $this->paths;
            $message = sprintf(
                "Current paths: %s\nWhat is the path you would like to extract?\n[Q]uit [D]one",
                implode(', ', $currentPaths),
            );
            $response = $this->io->ask($message, $defaultPaths[$defaultPathIndex] ?? 'D');
            if (strtoupper($response) === 'Q') {
                $this->io->error('Extract Aborted');
                $this->abort();
            }
            if (strtoupper($response) === 'D' && count($this->paths)) {
                $this->io->out();

                return;
            }
            if (strtoupper($response) === 'D') {
                $this->io->warning('No directories selected. Please choose a directory.');
            } elseif (is_dir($response)) {
                $this->paths[] = $response;
                $defaultPathIndex++;
            } else {
                $this->io->error('The directory path you supplied was not found. Please try again.');
            }
            $this->io->out();
        }
    }

    /**
     * Execute the command
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $plugin = '';
        if ($this->args->getOption('exclude')) {
            $this->exclude = explode(',', (string)$this->args->getOption('exclude'));
        }
        if ($this->args->getOption('files')) {
            $this->files = explode(',', (string)$this->args->getOption('files'));
        }
        if ($this->args->getOption('paths')) {
            $this->paths = explode(',', (string)$this->args->getOption('paths'));
        }
        if ($this->args->getOption('plugin')) {
            $plugin = Inflector::camelize((string)$this->args->getOption('plugin'));
            if ($this->paths === []) {
                $this->paths = [Plugin::classPath($plugin), Plugin::templatePath($plugin)];
            }
        } elseif (!$this->args->getOption('paths')) {
            $this->getPaths();
        }

        if ($this->args->hasOption('extract-core')) {
            $this->extractCore = strtolower((string)$this->args->getOption('extract-core')) !== 'no';
        } else {
            $response = $this->io->askChoice(
                'Would you like to extract the messages from the CakePHP core?',
                ['y', 'n'],
                'n',
            );
            $this->extractCore = strtolower($response) === 'y';
        }

        if ($this->args->hasOption('exclude-plugins') && $this->isExtractingApp()) {
            $this->exclude = array_merge($this->exclude, array_values(App::path('plugins')));
        }

        if ($this->extractCore) {
            $this->paths[] = CAKE;
        }

        if ($this->args->hasOption('output')) {
            $this->output = (string)$this->args->getOption('output');
        } elseif ($this->args->hasOption('plugin')) {
            $this->output = Plugin::path($plugin)
                . 'resources' . DIRECTORY_SEPARATOR
                . 'locales' . DIRECTORY_SEPARATOR;
        } else {
            $message = "What is the path you would like to output?\n[Q]uit";
            $localePaths = array_values(App::path('locales'));
            if (!$localePaths) {
                $localePaths[] = ROOT . DIRECTORY_SEPARATOR
                    . 'resources' . DIRECTORY_SEPARATOR
                    . 'locales' . DIRECTORY_SEPARATOR;
            }
            while (true) {
                $response = $this->io->ask(
                    $message,
                    $localePaths[0],
                );
                if (strtoupper($response) === 'Q') {
                    $this->io->error('Extract Aborted');

                    return static::CODE_ERROR;
                }
                if ($this->isPathUsable($response)) {
                    $this->output = $response . DIRECTORY_SEPARATOR;
                    break;
                }

                $this->io->err('');
                $this->io->error(
                    'The directory path you supplied was ' .
                    'not found. Please try again.',
                );
                $this->io->err('');
            }
        }

        if ($this->args->hasOption('merge')) {
            $this->merge = strtolower((string)$this->args->getOption('merge')) !== 'no';
        } else {
            $this->io->out();
            $response = $this->io->askChoice(
                'Would you like to merge all domain strings into the default.pot file?',
                ['y', 'n'],
                'n',
            );
            $this->merge = strtolower($response) === 'y';
        }

        $this->markerError = (bool)$this->args->getOption('marker-error');

        if (!$this->files) {
            $this->searchFiles();
        }

        $this->output = rtrim($this->output, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!$this->isPathUsable($this->output)) {
            $this->io->error(sprintf('The output directory `%s` was not found or writable.', $this->output));

            return static::CODE_ERROR;
        }

        $this->extract();

        return static::CODE_SUCCESS;
    }

    /**
     * Add a translation to the internal translations property
     *
     * Takes care of duplicate translations
     *
     * @param string $domain The domain
     * @param string $msgid The message string
     * @param array<string, mixed> $details Context and plural form if any, file and line references
     * @return void
     */
    protected function addTranslation(string $domain, string $msgid, array $details = []): void
    {
        $escape = static fn(string $text): string => addcslashes(str_replace("\r\n", "\n", $text), "\0..\37\\\"");
        $msgid = $escape($msgid);
        foreach (['msgctxt', 'msgid_plural'] as $key) {
            if (isset($details[$key])) {
                $details[$key] = $escape($details[$key]);
            }
        }

        $context = $details['msgctxt'] ?? '';

        if (empty($this->translations[$domain][$msgid][$context])) {
            $this->translations[$domain][$msgid][$context] = [
                'msgid_plural' => false,
            ];
        }

        if (isset($details['msgid_plural'])) {
            $this->translations[$domain][$msgid][$context]['msgid_plural'] = $details['msgid_plural'];
        }

        if (isset($details['file'])) {
            $line = $details['line'] ?? 0;
            $this->translations[$domain][$msgid][$context]['references'][$details['file']][] = $line;
        }
    }

    /**
     * Extract text
     *
     * @return void
     */
    protected function extract(): void
    {
        $this->io->out();
        $this->io->out();
        $this->io->out('Extracting...');
        $this->io->hr();
        $this->io->out('Paths:');
        foreach ($this->paths as $path) {
            $this->io->out('   ' . $path);
        }
        $this->io->out('Output Directory: ' . $this->output);
        $this->io->hr();
        $this->extractTokens();
        $this->buildFiles();
        $this->writeFiles();
        $this->paths = [];
        $this->files = [];
        $this->storage = [];
        $this->translations = [];
        $this->io->out();
        if ($this->countMarkerError) {
            $this->io->error("{$this->countMarkerError} marker error(s) detected.");
            $this->io->err(' => Use the --marker-error option to display errors.');
        }

        $this->io->out('Done.');
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to configure
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription([
            static::getDescription(),
            'Source files are parsed and string literal format strings ' .
            'provided to the <info>__</info> family of functions, enum labels, and ' .
            '<info>TranslatableMessage</info> constructors are extracted.',
        ])->addOption('app', [
            'help' => 'Directory where your application is located.',
        ])->addOption('paths', [
            'help' => 'Comma separated list of paths that are searched for source files.',
        ])->addOption('merge', [
            'help' => 'Merge all domain strings into a single default.po file.',
            'default' => 'no',
            'choices' => ['yes', 'no'],
        ])->addOption('output', [
            'help' => 'Full path to output directory.',
        ])->addOption('files', [
            'help' => 'Comma separated list of files to parse.',
        ])->addOption('exclude-plugins', [
            'boolean' => true,
            'default' => true,
            'help' => 'Ignores all files in plugins if this command is run inside from the same app directory.',
        ])->addOption('plugin', [
            'help' => 'Extracts tokens only from the plugin specified and '
                . "puts the result in the plugin's `locales` directory.",
            'short' => 'p',
        ])->addOption('exclude', [
            'help' => 'Comma separated list of directories to exclude.' .
                ' Any path containing a path segment with the provided values will be skipped. E.g. test,vendors',
        ])->addOption('overwrite', [
            'boolean' => true,
            'default' => false,
            'help' => 'Always overwrite existing .pot files.',
        ])->addOption('extract-core', [
            'help' => 'Extract messages from the CakePHP core libraries.',
            'choices' => ['yes', 'no'],
        ])->addOption('no-location', [
            'boolean' => true,
            'default' => false,
            'help' => 'Do not write file locations for each extracted message.',
        ])->addOption('marker-error', [
            'boolean' => true,
            'default' => false,
            'help' => 'Do not display marker error.',
        ]);

        return $parser;
    }

    /**
     * Orchestrate extraction, diagnostics, and catalog collection for each file.
     *
     * @return void
     */
    protected function extractTokens(): void
    {
        $progress = $this->io->helper('progress');
        assert($progress instanceof ProgressHelper);
        $progress->init(['total' => count($this->files)]);
        $isVerbose = $this->args->getOption('verbose');
        $extractors = [
            new FunctionExtractor(),
            new EnumLabelExtractor(
                Label::class,
                static fn(Label $label): ExtractedMessage => new ExtractedMessage(
                    $label->label,
                    $label->domain,
                    $label->context,
                ),
            ),
            new TranslatableMessageExtractor(),
        ];

        foreach ($this->files as $file) {
            if ($isVerbose) {
                $this->io->verbose(sprintf('Processing %s...', $file));
            }
            $code = (string)file_get_contents($file);
            foreach ($extractors as $extractor) {
                foreach ($extractor->extract($file, $code) as $result) {
                    if ($result instanceof ExtractionDiagnostic) {
                        $this->reportDiagnostic($file, $result);
                        continue;
                    }
                    $this->addTranslation($result->domain, $result->message, [
                        'file' => '.' . str_replace(ROOT, '', $file),
                        'line' => $result->line,
                        'msgctxt' => $result->context,
                        'msgid_plural' => $result->plural,
                    ]);
                }
            }
            if (!$isVerbose) {
                $progress->increment(1);
                $progress->draw();
            }
        }
    }

    /**
     * Report extractor diagnostics while respecting the marker-error option.
     *
     * @param string $file Source filename.
     * @param \Cake\I18n\Extractor\ExtractionDiagnostic $diagnostic Problem reported by an extractor.
     * @return void
     */
    protected function reportDiagnostic(string $file, ExtractionDiagnostic $diagnostic): void
    {
        if (!$diagnostic->invalidMarker) {
            $this->io->warning($diagnostic->message);

            return;
        }
        if (!str_contains($file, CAKE_CORE_INCLUDE_PATH)) {
            $this->countMarkerError++;
        }
        if ($this->markerError) {
            $this->io->error($diagnostic->message);
        }
    }

    /**
     * Build the translate template file contents out of obtained strings
     *
     * @return void
     */
    protected function buildFiles(): void
    {
        $paths = $this->paths;
        $paths[] = realpath(APP) . DIRECTORY_SEPARATOR;

        usort($paths, function (string $a, string $b) {
            return strlen($a) - strlen($b);
        });

        foreach ($this->translations as $domain => $translations) {
            foreach ($translations as $msgid => $contexts) {
                foreach ($contexts as $context => $details) {
                    $plural = $details['msgid_plural'];
                    $files = $details['references'];
                    $header = '';

                    if (!$this->args->getOption('no-location')) {
                        $occurrences = [];
                        foreach ($files as $file => $lines) {
                            $lines = array_unique($lines);
                            foreach ($lines as $line) {
                                $occurrences[] = $file . ':' . $line;
                            }
                        }
                        $occurrences = implode("\n#: ", $occurrences);

                        $header = '#: '
                            . str_replace(DIRECTORY_SEPARATOR, '/', $occurrences)
                            . "\n";
                    }

                    $sentence = '';
                    if ($context !== '') {
                        $sentence .= "msgctxt \"{$context}\"\n";
                    }
                    if ($plural === false) {
                        $sentence .= "msgid \"{$msgid}\"\n";
                        $sentence .= "msgstr \"\"\n\n";
                    } else {
                        $sentence .= "msgid \"{$msgid}\"\n";
                        $sentence .= "msgid_plural \"{$plural}\"\n";
                        $sentence .= "msgstr[0] \"\"\n";
                        $sentence .= "msgstr[1] \"\"\n\n";
                    }

                    if ($domain !== 'default' && $this->merge) {
                        $this->store('default', $header, $sentence);
                    } else {
                        $this->store($domain, $header, $sentence);
                    }
                }
            }
        }
    }

    /**
     * Prepare a file to be stored
     *
     * @param string $domain The domain
     * @param string $header The header content.
     * @param string $sentence The sentence to store.
     * @return void
     */
    protected function store(string $domain, string $header, string $sentence): void
    {
        $this->storage[$domain] ??= [];

        if (!isset($this->storage[$domain][$sentence])) {
            $this->storage[$domain][$sentence] = $header;
        } else {
            $this->storage[$domain][$sentence] .= $header;
        }
    }

    /**
     * Write the files that need to be stored
     *
     * @return void
     */
    protected function writeFiles(): void
    {
        $this->io->out();
        $overwriteAll = false;
        if ($this->args->getOption('overwrite')) {
            $overwriteAll = true;
        }
        foreach ($this->storage as $domain => $sentences) {
            $output = $this->writeHeader($domain);
            $headerLength = strlen($output);
            foreach ($sentences as $sentence => $header) {
                $output .= $header . $sentence;
            }

            $filename = str_replace('/', '_', $domain) . '.pot';
            $outputPath = $this->output . $filename;

            if ($this->checkUnchanged($outputPath, $headerLength, $output)) {
                $this->io->out($filename . ' is unchanged. Skipping.');
                continue;
            }

            $response = '';
            while ($overwriteAll === false && file_exists($outputPath) && strtoupper($response) !== 'Y') {
                $this->io->out();
                $response = $this->io->askChoice(
                    sprintf('Error: %s already exists in this location. Overwrite? [Y]es, [N]o, [A]ll', $filename),
                    ['y', 'n', 'a'],
                    'y',
                );
                if (strtoupper($response) === 'N') {
                    $response = '';
                    while (!$response) {
                        $response = $this->io->ask('What would you like to name this file?', 'new_' . $filename);
                        $filename = $response;
                    }
                } elseif (strtoupper($response) === 'A') {
                    $overwriteAll = true;
                }
            }
            $fs = new Filesystem();
            $fs->dumpFile($this->output . $filename, $output);
        }
    }

    /**
     * Build the translation template header
     *
     * @param string $domain Domain
     * @return string Translation template header
     */
    protected function writeHeader(string $domain): string
    {
        $projectIdVersion = $domain === 'cake' ? 'CakePHP ' . Configure::version() : 'PROJECT VERSION';

        $output = "# LANGUAGE translation of CakePHP Application\n";
        $output .= "# Copyright YEAR NAME <EMAIL@ADDRESS>\n";
        $output .= "#\n";
        $output .= "#, fuzzy\n";
        $output .= "msgid \"\"\n";
        $output .= "msgstr \"\"\n";
        $output .= '"Project-Id-Version: ' . $projectIdVersion . "\\n\"\n";
        $output .= '"POT-Creation-Date: ' . date('Y-m-d H:iO') . "\\n\"\n";
        $output .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
        $output .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
        $output .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
        $output .= "\"MIME-Version: 1.0\\n\"\n";
        $output .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
        $output .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
        $output .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n\n";

        return $output;
    }

    /**
     * Check whether the old and new output are the same, thus unchanged
     *
     * Compares the sha1 hashes of the old and new file without header.
     *
     * @param string $oldFile The existing file.
     * @param int $headerLength The length of the file header in bytes.
     * @param string $newFileContent The content of the new file.
     * @return bool Whether the old and new file are unchanged.
     */
    protected function checkUnchanged(string $oldFile, int $headerLength, string $newFileContent): bool
    {
        if (!file_exists($oldFile)) {
            return false;
        }
        $oldFileContent = file_get_contents($oldFile);
        if ($oldFileContent === false) {
            throw new CakeException(sprintf('Cannot read file content of `%s`', $oldFile));
        }

        $oldChecksum = sha1(substr($oldFileContent, $headerLength));
        $newChecksum = sha1(substr($newFileContent, $headerLength));

        return $oldChecksum === $newChecksum;
    }

    /**
     * Search files that may contain translatable strings
     *
     * @return void
     */
    protected function searchFiles(): void
    {
        $pattern = false;
        if ($this->exclude) {
            $exclude = [];
            foreach ($this->exclude as $e) {
                if (DIRECTORY_SEPARATOR !== '\\' && !str_starts_with($e, DIRECTORY_SEPARATOR)) {
                    $e = DIRECTORY_SEPARATOR . $e;
                }
                $exclude[] = preg_quote($e, '/');
            }
            $pattern = '/' . implode('|', $exclude) . '/';
        }

        foreach ($this->paths as $path) {
            $path = realpath($path);
            if ($path === false) {
                continue;
            }
            $path .= DIRECTORY_SEPARATOR;
            $files = new Finder()
                ->in($path)
                ->name('*.php')
                ->files();
            foreach ($files as $file) {
                $this->files[] = $file->getPathname();
            }
        }
        $this->files = array_unique($this->files);
        sort($this->files);
        if ($pattern) {
            $this->files = preg_grep($pattern, $this->files, PREG_GREP_INVERT) ?: [];
            $this->files = array_values($this->files);
        }
        $this->files = array_unique($this->files);
    }

    /**
     * Returns whether this execution is meant to extract string only from directories in folder represented by the
     * APP constant, i.e. this task is extracting strings from same application.
     *
     * @return bool
     */
    protected function isExtractingApp(): bool
    {
        return $this->paths === [APP];
    }

    /**
     * Checks whether a given path is usable for writing.
     *
     * @param string $path Path to folder
     * @return bool true if it exists and is writable, false otherwise
     */
    protected function isPathUsable(string $path): bool
    {
        if (!is_dir($path)) {
            mkdir($path, 0o777 ^ umask(), true);
        }

        return is_dir($path) && is_writable($path);
    }
}
