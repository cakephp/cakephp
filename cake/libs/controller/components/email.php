<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP v 1.2.0.3467
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
class EmailComponent extends Object {
	var $to = null;
	var $from = null;
	var $replyTo = null;
	var $return = null;
	var $cc = array();
	var $bcc = array();
	var $subject = null;
	var $template = 'default';
	var $sendAs = 'text';
	var $delivery = 'mail';
	var $charset = 'ISO-8859-15';
	var $attachments = array();
	var $xMailer = 'CakePHP EmailComponent';
	var $filePaths = array();

	var $_debug = false;
	var $_error = false;
	var $_newLine = "\r\n";
	var $_lineLength = 75;

	var $__header = null;
	var $__boundary = null;
	var $__message = null;


	function send($content){
		if($this->template === null) {
			if(is_array($content)){
				$message = null;
				foreach ($content as $key => $value){
					$message .= $value . $this->_newLine;
				}
			} else {
				$message = $content;
			}
		} else {
			$message = $this->__renderTemplate($content);
		}

		$this->__createBoundary();
		$this->__createHeader();
		$this->__formatMessage($message);

		if(!empty($this->attachments)) {
			$this->__attachFiles();
		}
		$__method = '__'.$this->delivery;
		$this->$__method();
	}

	function reset() {
		$this->to = null;
		$this->from = null;
		$this->replyTo = null;
		$this->return = null;
		$this->cc = array();
		$this->bcc = array();
		$this->subject = null;
		$this->__header = null;
		$this->__boundary = null;
		$this->__message = null;
	}

	function __createBoundary(){
		$this->__boundary = md5(uniqid(time()));
	}


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
			$this->to .= ', ' . $addresses;
		}
		if(!empty($this->bcc)) {
			foreach ($this->bcc as $bcc) {
				$addresses .= $bcc . ', ';
			}
			$this->__header .= 'Bcc: ' . $addresses . $this->_newLine;
			$this->to .= ', ' . $addresses;
		}

		$this->__header .= 'X-Mailer: ' . $this->xMailer . $this->_newLine;

		if(!empty($this->attachments) && $this->sendAs === 'text') {
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/mixed; boundary=' . $this->__boundary . $this->_newLine;
		} elseif(!empty($this->attachments) && $this->sendAs === 'html') {
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/related; boundary=' . $this->__boundary . $this->_newLine;
		} elseif($this->sendAs === 'html') {
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/alternative; boundary=' . $this->__boundary . $this->_newLine;
		}
	}

	function __formatMessage($message){
		$message = $this->__wrap($message);

		if($this->sendAs === 'html'){
			$this->__message = '--' . $this->__boundary . $this->_newLine;
			$this->__message .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
			$this->__message .=  'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$this->__message .= 'If you are seeing this is because you may need to change your'.$this->_newLine;
			$this->__message .= 'preferred message format from HTML to plain text.'.$this->_newLine.$this->_newLine;
			$this->__message .=  strip_tags($message) . $this->_newLine;

			$this->__message = '--' .  $this->__boundary . $this->_newLine;
			$this->__message .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$this->__message .= $message . $this->_newLine;
			$this->__message .=  $this->_newLine . $this->_newLine;
		} else {
			$this->__message .= $message . $this->_newLine;
		}
	}

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

	function __findFiles($attachment){
		foreach($this->filePaths as $path) {
			if (file_exists($path . DS . $attachment)) {
				$file = $path . DS . $attachment;
				return $file;
			}
		}
	}

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

	function __mail(){
		return @mail($this->to, $this->subject, $this->__message, $this->__header);
	}

	function __smtp(){

	}
}
?>