<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.3467
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 *
 */
class EmailComponent extends Object{
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $to = null;
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $from = null;
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $replyTo = null;
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $return = null;
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $cc = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $bcc = array();
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $subject = null;
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $additionalParams = null;
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $template = 'default';
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $sendAs = 'text';  //html, text, both
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $delivery = 'mail'; //mail, smtp, debug
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $charset = 'ISO-8859-15';
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $attachments = array();
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $xMailer = 'CakePHP Email Component';
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $filePaths = array();
/**
 * Enter description here...
 *
 * @var string
 * @access protected
 */
	var $_debug = false;
/**
 * Enter description here...
 *
 * @var string
 * @access protected
 */
	var $_error = false;
/**
 * Enter description here...
 *
 * @var string
 * @access protected
 */
	var $_newLine = "\n";
/**
 * Enter description here...
 *
 * @var integer
 * @access protected
 */
	var $_lineLength = 75;
/**
 * Enter description here...
 *
 * @var string
 * @access private
 */
	var $__header = null;
/**
 * Enter description here...
 *
 * @var string
 * @access private
 */
	var $__boundary = null;
/**
 * Enter description here...
 *
 * @var string
 * @access private
 */
	var $__message = null;
/**
 * Enter description here...
 *
 * @param unknown_type $controller
 * @access public
 */
	function startup(&$controller){
		$this->Controller = $controller;
	}
/**
 * Enter description here...
 *
 * @param mixed $content
 * @return unknown
 * @access public
 */
	function send($content = null){
		$this->__createHeader();

		if($this->template === null) {
			if(is_array($content)){
				$message = null;
				foreach ($content as $key => $value){
					$message .= $value . $this->_newLine;
				}
			} else {
				$message = $content;
			}
			$this->__formatMessage($message);
		} else {
			$this->__message = $this->__renderTemplate($content);
		}

		if(!empty($this->attachments)) {
			$this->__attachFiles();
		}

		if (!is_null($this->__boundary)) {
			$this->__message .= $this->_newLine .'--' . $this->__boundary . '--' . $this->_newLine . $this->_newLine;
		}

		if ($this->_debug){
			$this->delivery = 'debug';
		}
		$__method = '__'.$this->delivery;

		if(low($this->charset) === 'utf-8') {
			$this->subject = utf8_decode($this->subject);
		}
		return $this->$__method();
	}
/**
 * Enter description here...
 *
 * @access public
 */
	function reset() {
		$this->to = null;
		$this->from = null;
		$this->replyTo = null;
		$this->return = null;
		$this->cc = array();
		$this->bcc = array();
		$this->subject = null;
		$this->additionalParams = null;
		$this->__header = null;
		$this->__boundary = null;
		$this->__message = null;
	}
/**
 * Enter description here...
 *
 * @param string $content
 * @return unknown
 * @access private
 */
	function __renderTemplate($content) {
		$View = new View($this->Controller);
		if($this->sendAs === 'both'){
			$htmlContent = $content;
			$msg = '--' . $this->__boundary . $this->_newLine;
			$msg .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
			$msg .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$content = $View->renderElement('email' . DS . 'text' . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . 'text';
			$msg .= $View->renderLayout($content) . $this->_newLine;

			$msg .= $this->_newLine. '--' . $this->__boundary . $this->_newLine;
			$msg .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$msg .=  'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$content = $View->renderElement('email' . DS . 'html' . DS . $this->template, array('content' => $htmlContent), true);
			$View->layoutPath = 'email' . DS . 'html';
			$msg .= $View->renderLayout($content);

			return $msg;
		} else {
			$content = $View->renderElement('email' . DS . $this->sendAs . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . $this->sendAs;
			return $View->renderLayout($content);
		}
	}
/**
 * Enter description here...
 *
 * @access private
 */
	function __createBoundary(){
		$this->__boundary = md5(uniqid(time()));
	}
/**
 * Enter description here...
 *
 * @access private
 */
	function __createHeader(){
		$this->__header .= 'From: ' . $this->from . $this->_newLine;
		$this->__header .= 'Reply-To: ' . $this->replyTo . $this->_newLine;
		$this->__header .= 'Return-Path: ' . $this->return . $this->_newLine;

		$addresses = null;
		if(!empty($this->cc)) {
			foreach ($this->cc as $cc) {
				$addresses .= $cc . ', ';
			}
			$this->__header .= 'cc: ' . $addresses . $this->_newLine;
			//$this->to .= ', ' . $addresses;
		}
		if(!empty($this->bcc)) {
			foreach ($this->bcc as $bcc) {
				$addresses .= $bcc . ', ';
			}
			$this->__header .= 'Bcc: ' . $addresses . $this->_newLine;
			//$this->to .= ', ' . $addresses;
		}

		$this->__header .= 'X-Mailer: ' . $this->xMailer . $this->_newLine;

		if(!empty($this->attachments) && $this->sendAs === 'text') {
			$this->__createBoundary();
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/mixed; boundary="' . $this->__boundary . '"' . $this->_newLine;
		} elseif(!empty($this->attachments) && $this->sendAs === 'html') {
			$this->__createBoundary();
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/related; boundary="' . $this->__boundary . '"' . $this->_newLine;
		} elseif($this->sendAs === 'html') {
			$this->__header .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$this->__header .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
		} elseif($this->sendAs === 'both') {
			$this->__createBoundary();
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/alternative; boundary="' . $this->__boundary . '"' . $this->_newLine;
		}
	}
/**
 * Enter description here...
 *
 * @param string $message
 * @access private
 */
	function __formatMessage($message){
		$message = $this->__wrap($message);

		if($this->sendAs === 'both'){
			$this->__message = '--' . $this->__boundary . $this->_newLine;
			$this->__message .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
			$this->__message .=  'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$this->__message .= 'If you are seeing this is because you may need to change your'.$this->_newLine;
			$this->__message .= 'preferred message format from HTML to plain text.'.$this->_newLine.$this->_newLine;
			$this->__message .=  strip_tags($message) . $this->_newLine;

			$this->__message .= '--' .  $this->__boundary . $this->_newLine;
			$this->__message .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$this->__message .= $message . $this->_newLine;
			$this->__message .=  $this->_newLine . $this->_newLine;
		} else {
			$this->__message .= $message . $this->_newLine;
		}
	}
/**
 * Enter description here...
 *
 * @access private
 */
	function __attachFiles(){
		foreach($this->attachments as $attachment) {
			$files[] = $this->__findFiles($attachment);
		}

		foreach($files as $file) {
			$handle = fopen($file, 'rb');
			$data = fread($handle, filesize($file));
			$data = chunk_split(base64_encode($data)) ;
			$filetype = mime_content_type($file);

			$this->__message .= '--' . $this->__boundary . $this->_newLine;
			$this->__message .= 'Content-Type: ' . $filetype . '; name="' . $file . '"' . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: base64' . $this->_newLine;
			$this->__message .= 'Content-Disposition: attachment; filename="' .$file. '"' . $this->_newLine . $this->_newLine;
			$this->__message .= $data . $this->_newLine . $this->_newLine;
		}
	}
/**
 * Enter description here...
 *
 * @param string $attachment
 * @return unknown
 * @access private
 */
	function __findFiles($attachment){
		foreach($this->filePaths as $path) {
			if (file_exists($path . DS . $attachment)) {
				$file = $path . DS . $attachment;
				return $file;
			}
		}
	}
/**
 * Enter description here...
 *
 * @param string $message
 * @return unknown
 * @access private
 */
	function __wrap($message) {
		$message = str_replace(array('\r','\n'), '\n', $message);
		$words = explode('\n', $message);
		$formated = null;
		foreach ($words as $word) {
			$formated .= wordwrap($word, $this->_lineLength, ' ', 1);
			$formated .= "\n";
		}
		return $formated;
	}
/**
 * Enter description here...
 *
 * @return unknown
 * @access private
 */
	function __mail(){
		return @mail($this->to, $this->subject, $this->__message, $this->__header, $this->additionalParams);
	}
/**
 * Enter description here...
 *
 * @access private
 */
	function __smtp(){

	}
/**
 * Enter description here...
 *
 * @return unknown
 * @access private
 */
	function __debug() {
		$fm = '<pre>';
		$fm .= sprintf('%s %s', 'To:', $this->to);
		$fm .= sprintf('%s %s', 'Subject:', $this->subject);
		$fm .= sprintf('%s\n\n%s', 'Header:', $this->__header);
		$fm .= sprintf('%s\n\n%s', 'Parameters:', $this->additionalParams);
		$fm .= sprintf('%s\n\n%s', 'Message:', $this->__message);
		$fm .= '</pre>';

		$this->Controller->Session->setFlash($fm, 'default', null, 'email');
		return true;
	}
}
?>
