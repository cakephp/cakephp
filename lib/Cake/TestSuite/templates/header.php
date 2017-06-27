<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.templates
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>CakePHP Test Suite <?php echo Configure::version(); ?></title>
		<style type="text/css">
			body h2 {color: #777;}
			h3 {font-size: 170%; padding-top: 1em}
			a {font-size: 120%}
			li {line-height: 140%}
			.test-menu {float:left; margin-right: 24px;}
			.test-results {float:left; width: 67%;}
			ul.tests {margin: 0; font-size:12px;}
			ul.tests li {
				list-style: none;
				margin: 14px 0;
				padding-left: 20px;
			}
			ul.tests li span {
				font-size:14px;
				text-transform: uppercase;
				font-weight: bold;
			}
			ul.tests li.pass span, ul.tests li.skipped span { display:inline;}
			ul.tests li.fail span { color: red; }
			ul.tests li.pass span { color: green; }
			ul.tests li.skipped span { color: navy; }
			ul.tests li.error span { color : #d15d00; }

			ul.tests li.pass,
			ul.tests li.error,
			ul.tests li.skipped,
			ul.tests li.fail {
				background: #fff2f2 url(<?php echo $baseDir; ?>img/test-fail-icon.png) 5px 5px no-repeat;
				border-top: 1px dotted red;
				border-bottom: 1px dotted red;
				padding:5px 10px 2px 25px;
			}
			ul.tests li.pass {
				background-color: #f2fff2;
				background-image: url(<?php echo $baseDir; ?>img/test-pass-icon.png);
				border-color:green;
			}
			ul.tests li.skipped {
				background-color: #edf1ff;
				background-image: url(<?php echo $baseDir; ?>img/test-skip-icon.png);
				border-color:navy;
			}
			ul.tests li.error {
				background-color: #ffffe5;
				background-image: url(<?php echo $baseDir; ?>img/test-error-icon.png);
				border-color: #DF6300;
			}
			ul.tests li div { margin: 5px 0 8px 0; }
			ul.tests li div.msg { font-weight: bold; }
			table caption { color:#fff; }

			div.code-coverage-results div.code-line {
				padding-left:5px;
				display:block;
				margin-left:10px;
			}
			.coverage-toggle {
				float:right;
				margin-top:10px;
				font-size:12px;
			}
			.coverage-container {
				margin-top:1em;
			}
			div.code-coverage-results div.uncovered span.content { background:#ecc; }
			div.code-coverage-results div.covered span.content { background:#cec; }
			div.code-coverage-results div.ignored span.content { color:#aaa; }
			div.code-coverage-results div:hover {
				background:#e8e8e8;
				cursor: pointer;
			}
			div.code-coverage-results div.covered:hover span.content { background:#b4edb4;}
			div.code-coverage-results div.uncovered:hover span.content { background:#edb4b4;}
			div.code-coverage-results span.line-num {
				color:#666;
				display:block;
				float:left;
				width:20px;
				text-align:right;
				margin-right:5px;
			}
			div.code-coverage-results span.line-num strong { color:#666; }
			div.code-coverage-results div.start {
				border:1px solid #aaa;
				border-width:1px 1px 0 1px;
				margin-top:30px;
				padding-top:5px;
			}
			div.code-coverage-results div.end {
				border:1px solid #aaa;
				border-width:0px 1px 1px 1px;
				margin-bottom:30px;
				padding-bottom:5px;
			}
			div.code-coverage-results div.realstart { margin-top:0px; }
			div.code-coverage-results p.note {
				color:#bbb;
				padding:5px;
				margin:5px 0 10px;
				font-size:10px;
			}
			div.code-coverage-results span.result-bad { color: #a00; }
			div.code-coverage-results span.result-ok { color: #fa0; }
			div.code-coverage-results span.result-good { color: #0a0; }

			div#version {
				padding-top: 2px;
				float: right;
				padding-left: 20px;
			}
		</style>
		<link rel="stylesheet" type="text/css" href="<?php echo $baseDir; ?>css/cake.generic.css" />
	</head>
	<body>
		<div id="container">
			<div id="header">
			<div id="version">PHPUnit: <?php echo class_exists('PHPUnit_Runner_Version') ? PHPUnit_Runner_Version::id() : 'n/a'; ?></div>
				<h1>CakePHP: the rapid development php framework</h1>
			</div>
			<div id="content">
			<h2>CakePHP Test Suite <?php echo Configure::version(); ?></h2>
