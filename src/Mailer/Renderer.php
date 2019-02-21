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
namespace Cake\Mailer;

use Cake\Core\Configure;
use Cake\Http\Client\FormDataPart;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Cake\Utility\Text;
use Cake\View\ViewVarsTrait;

/**
 * Class for rendering email message.
 */
class Renderer
{
    use ViewVarsTrait;

    /**
     * Line length - no should more - RFC 2822 - 2.1.1
     *
     * @var int
     */
    public const LINE_LENGTH_SHOULD = 78;

    /**
     * Line length - no must more - RFC 2822 - 2.1.1
     *
     * @var int
     */
    public const LINE_LENGTH_MUST = 998;

    /**
     * Constant for folder name containing email templates.
     *
     * @var string
     */
    public const TEMPLATE_FOLDER = 'email';

    /**
     * The application wide charset, used to encode headers and body
     *
     * @var string|null
     */
    protected $appCharset;

    /**
     * If set, boundary to use for multipart mime messages
     *
     * @var string
     */
    protected $boundary;

    /**
     * Email instance.
     *
     * @var \Cake\Mailer\Email
     */
    protected $email;

    /**
     * Constructor.
     *
     * @param string|null $appCharset Application's character set.
     */
    public function __construct(?string $appCharset = null)
    {
        $this->appCharset = $appCharset ?: Configure::read('App.encoding');
    }

    /**
     * Render the body of the email.
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @param string|null $content Content to render.
     * @return array Email Body ready to be sent
     */
    public function render(Email $email, ?string $content = null): array
    {
        $rendered = $this->renderTemplates($email, $content);

        $this->email = $email;
        $textMessage = $htmlMessage = '';

        $this->createBoundary();
        $msg = [];

        $contentIds = array_filter((array)Hash::extract($email->getAttachments(), '{s}.contentId'));
        $hasInlineAttachments = count($contentIds) > 0;
        $hasAttachments = !empty($email->getAttachments());
        $hasMultipleTypes = count($rendered) > 1;
        $multiPart = ($hasAttachments || $hasMultipleTypes);

        $boundary = $relBoundary = $textBoundary = $this->boundary;

        if ($hasInlineAttachments) {
            $msg[] = '--' . $boundary;
            $msg[] = 'Content-Type: multipart/related; boundary="rel-' . $boundary . '"';
            $msg[] = '';
            $relBoundary = $textBoundary = 'rel-' . $boundary;
        }

        if ($hasMultipleTypes && $hasAttachments) {
            $msg[] = '--' . $relBoundary;
            $msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $boundary . '"';
            $msg[] = '';
            $textBoundary = 'alt-' . $boundary;
        }

        if (isset($rendered[Email::MESSAGE_TEXT])) {
            if ($multiPart) {
                $msg[] = '--' . $textBoundary;
                $msg[] = 'Content-Type: text/plain; charset=' . $email->getContentTypeCharset();
                $msg[] = 'Content-Transfer-Encoding: ' . $email->getContentTransferEncoding();
                $msg[] = '';
            }
            $textMessage = $rendered[Email::MESSAGE_TEXT];
            $content = explode("\n", $textMessage);
            $msg = array_merge($msg, $content);
            $msg[] = '';
            $msg[] = '';
        }

        if (isset($rendered[Email::MESSAGE_HTML])) {
            if ($multiPart) {
                $msg[] = '--' . $textBoundary;
                $msg[] = 'Content-Type: text/html; charset=' . $email->getContentTypeCharset();
                $msg[] = 'Content-Transfer-Encoding: ' . $email->getContentTransferEncoding();
                $msg[] = '';
            }
            $htmlMessage = $rendered[Email::MESSAGE_HTML];
            $content = explode("\n", $htmlMessage);
            $msg = array_merge($msg, $content);
            $msg[] = '';
            $msg[] = '';
        }

        if ($textBoundary !== $relBoundary) {
            $msg[] = '--' . $textBoundary . '--';
            $msg[] = '';
        }

        if ($hasInlineAttachments) {
            $attachments = $this->attachInlineFiles($relBoundary);
            $msg = array_merge($msg, $attachments);
            $msg[] = '';
            $msg[] = '--' . $relBoundary . '--';
            $msg[] = '';
        }

        if ($hasAttachments) {
            $attachments = $this->attachFiles($boundary);
            $msg = array_merge($msg, $attachments);
        }
        if ($hasAttachments || $hasMultipleTypes) {
            $msg[] = '';
            $msg[] = '--' . $boundary . '--';
            $msg[] = '';
        }

        return [
            'message' => $msg,
            'boundary' => $boundary,
            'textMessage' => $textMessage,
            'htmlMessage' => $htmlMessage,
        ];
    }

    /**
     * Translates a string for one charset to another if the App.encoding value
     * differs and the mb_convert_encoding function exists
     *
     * @param string $text The text to be converted
     * @param string $charset the target encoding
     * @return string
     */
    protected function encodeString(string $text, string $charset): string
    {
        if ($this->appCharset === $charset) {
            return $text;
        }

        return mb_convert_encoding($text, $charset, $this->appCharset);
    }

    /**
     * Wrap the message to follow the RFC 2822 - 2.1.1
     *
     * @param string|null $message Message to wrap
     * @param int $wrapLength The line length
     * @return array Wrapped message
     */
    protected function wrap(?string $message = null, int $wrapLength = self::LINE_LENGTH_MUST): array
    {
        if ($message === null || strlen($message) === 0) {
            return [''];
        }
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $lines = explode("\n", $message);
        $formatted = [];
        $cut = ($wrapLength === static::LINE_LENGTH_MUST);

        foreach ($lines as $line) {
            if (empty($line) && $line !== '0') {
                $formatted[] = '';
                continue;
            }
            if (strlen($line) < $wrapLength) {
                $formatted[] = $line;
                continue;
            }
            if (!preg_match('/<[a-z]+.*>/i', $line)) {
                $formatted = array_merge(
                    $formatted,
                    explode("\n", Text::wordWrap($line, $wrapLength, "\n", $cut))
                );
                continue;
            }

            $tagOpen = false;
            $tmpLine = $tag = '';
            $tmpLineLength = 0;
            for ($i = 0, $count = strlen($line); $i < $count; $i++) {
                $char = $line[$i];
                if ($tagOpen) {
                    $tag .= $char;
                    if ($char === '>') {
                        $tagLength = strlen($tag);
                        if ($tagLength + $tmpLineLength < $wrapLength) {
                            $tmpLine .= $tag;
                            $tmpLineLength += $tagLength;
                        } else {
                            if ($tmpLineLength > 0) {
                                $formatted = array_merge(
                                    $formatted,
                                    explode("\n", Text::wordWrap(trim($tmpLine), $wrapLength, "\n", $cut))
                                );
                                $tmpLine = '';
                                $tmpLineLength = 0;
                            }
                            if ($tagLength > $wrapLength) {
                                $formatted[] = $tag;
                            } else {
                                $tmpLine = $tag;
                                $tmpLineLength = $tagLength;
                            }
                        }
                        $tag = '';
                        $tagOpen = false;
                    }
                    continue;
                }
                if ($char === '<') {
                    $tagOpen = true;
                    $tag = '<';
                    continue;
                }
                if ($char === ' ' && $tmpLineLength >= $wrapLength) {
                    $formatted[] = $tmpLine;
                    $tmpLineLength = 0;
                    continue;
                }
                $tmpLine .= $char;
                $tmpLineLength++;
                if ($tmpLineLength === $wrapLength) {
                    $nextChar = $line[$i + 1];
                    if ($nextChar === ' ' || $nextChar === '<') {
                        $formatted[] = trim($tmpLine);
                        $tmpLine = '';
                        $tmpLineLength = 0;
                        if ($nextChar === ' ') {
                            $i++;
                        }
                    } else {
                        $lastSpace = strrpos($tmpLine, ' ');
                        if ($lastSpace === false) {
                            continue;
                        }
                        $formatted[] = trim(substr($tmpLine, 0, $lastSpace));
                        $tmpLine = substr($tmpLine, $lastSpace + 1);

                        $tmpLineLength = strlen($tmpLine);
                    }
                }
            }
            if (!empty($tmpLine)) {
                $formatted[] = $tmpLine;
            }
        }
        $formatted[] = '';

        return $formatted;
    }

    /**
     * Create unique boundary identifier
     *
     * @return void
     */
    protected function createBoundary(): void
    {
        if ($this->email->getAttachments() || $this->email->getEmailFormat() === Email::MESSAGE_BOTH) {
            $this->boundary = md5(Security::randomBytes(16));
        }
    }

    /**
     * Attach non-embedded files by adding file contents inside boundaries.
     *
     * @param string|null $boundary Boundary to use. If null, will default to $this->boundary
     * @return array An array of lines to add to the message
     */
    protected function attachFiles(?string $boundary = null): array
    {
        if ($boundary === null) {
            $boundary = $this->boundary;
        }

        $msg = [];
        foreach ($this->email->getAttachments() as $filename => $fileInfo) {
            if (!empty($fileInfo['contentId'])) {
                continue;
            }
            $data = $fileInfo['data'] ?? $this->readFile($fileInfo['file']);
            $hasDisposition = (
                !isset($fileInfo['contentDisposition']) ||
                $fileInfo['contentDisposition']
            );
            $part = new FormDataPart('', $data, '');

            if ($hasDisposition) {
                $part->disposition('attachment');
                $part->filename($filename);
            }
            $part->transferEncoding('base64');
            $part->type($fileInfo['mimetype']);

            $msg[] = '--' . $boundary;
            $msg[] = (string)$part;
            $msg[] = '';
        }

        return $msg;
    }

    /**
     * Read the file contents and return a base64 version of the file contents.
     *
     * @param string $path The absolute path to the file to read.
     * @return string File contents in base64 encoding
     */
    protected function readFile(string $path): string
    {
        return chunk_split(base64_encode((string)file_get_contents($path)));
    }

    /**
     * Attach inline/embedded files to the message.
     *
     * @param string|null $boundary Boundary to use. If null, will default to $this->boundary
     * @return array An array of lines to add to the message
     */
    protected function attachInlineFiles(?string $boundary = null): array
    {
        if ($boundary === null) {
            $boundary = $this->boundary;
        }

        $msg = [];
        foreach ($this->email->getAttachments() as $filename => $fileInfo) {
            if (empty($fileInfo['contentId'])) {
                continue;
            }
            $data = $fileInfo['data'] ?? $this->readFile($fileInfo['file']);

            $msg[] = '--' . $boundary;
            $part = new FormDataPart('', $data, 'inline');
            $part->type($fileInfo['mimetype']);
            $part->transferEncoding('base64');
            $part->contentId($fileInfo['contentId']);
            $part->filename($filename);
            $msg[] = (string)$part;
            $msg[] = '';
        }

        return $msg;
    }

    /**
     * Gets the text body types that are in this email message
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @return array Array of types. Valid types are Email::MESSAGE_TEXT and Email::MESSAGE_HTML
     */
    protected function getTypes(Email $email): array
    {
        $format = $email->getEmailFormat();

        $types = [$format];
        if ($format === Email::MESSAGE_BOTH) {
            $types = [Email::MESSAGE_HTML, Email::MESSAGE_TEXT];
        }

        return $types;
    }

    /**
     * Build and set all the view properties needed to render the templated emails.
     * If there is no template set, the $content will be returned in a hash
     * of the text content types for the email.
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @param string|null $content The content passed in from send() in most cases.
     * @return array The rendered content with html and text keys.
     */
    public function renderTemplates(Email $email, ?string $content = null): array
    {
        $types = $this->getTypes($email);
        $rendered = [];
        $template = $this->viewBuilder()->getTemplate();
        if (empty($template)) {
            foreach ($types as $type) {
                $content = str_replace(["\r\n", "\r"], "\n", $content);
                $rendered[$type] = $this->encodeString($content, $email->getCharset());
                $rendered[$type] = $this->wrap($rendered[$type]);
                $rendered[$type] = implode("\n", $rendered[$type]);
                $rendered[$type] = rtrim($rendered[$type], "\n");
            }

            return $rendered;
        }

        $view = $this->createView();

        [$templatePlugin] = pluginSplit($view->getTemplate());
        [$layoutPlugin] = pluginSplit((string)$view->getLayout());
        if ($templatePlugin) {
            $view->setPlugin($templatePlugin);
        } elseif ($layoutPlugin) {
            $view->setPlugin($layoutPlugin);
        }

        if ($view->get('content') === null) {
            $view->set('content', $content);
        }

        foreach ($types as $type) {
            $view->setTemplatePath(static::TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . $type);
            $view->setLayoutPath(static::TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . $type);

            $render = $view->render();
            $render = str_replace(["\r\n", "\r"], "\n", $render);
            $rendered[$type] = $this->encodeString($render, $email->getCharset());
        }

        foreach ($rendered as $type => $content) {
            $rendered[$type] = $this->wrap($content);
            $rendered[$type] = implode("\n", $rendered[$type]);
            $rendered[$type] = rtrim($rendered[$type], "\n");
        }

        return $rendered;
    }
}
