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
 * @subpackage		cake.cake.console.libs
 * @since			CakePHP(tm) v 1.2.0.5012
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * @package		cake
 * @subpackage	cake.cake.console.libs
 */
class ConsoleShell extends Shell {
	var $associations = array('hasOne', 'hasMany', 'belongsTo', 'hasAndBelongsToMany');

	function initialize() {
		$this->models = @loadModels();
		foreach ($this->models as $model) {
			$class = Inflector::camelize(r('.php', '', $model));
			$this->models[$model] = $class;
			$this->{$class} =& new $class();
		}
		$this->out('Model classes:');
		$this->out('--------------');

		foreach ($this->models as $model) {
			$this->out(" - {$model}");
		}
	}

	function main() {

		while (true) {
			$command = trim($this->in(''));

			switch($command) {
				case 'help':
					$this->out('Console help:');
					$this->out('-------------');
					$this->out('The interactive console is a tool for testing models before you commit code');
					$this->out('');
					$this->out('To test for results, use the name of your model without a leading $');
					$this->out('e.g. Foo->findAll()');
				break;
				case 'quit':
				case 'exit':
					return true;
				break;
				case 'models':
					$this->out('Model classes:');
					$this->hr();
					foreach ($this->models as $model) {
						$this->out(" - {$model}");
					}
				break;
				default:
					// Look to see if we're dynamically binding something
					$dynamicAssociation = false;

					foreach ($this->associations as $association) {
						if (preg_match("/^(\w+) $association (\w+)/", $command, $this->models) == TRUE) {
							$modelA = $this->models[1];
							$modelB = $this->models[2];
							$dynamicAssociation = true;
							$this->{$modelA}->bindModel(
								array("$association" => array(
									"$modelB" => array(
										'className' => $modelB))), false);
							print "Added association $command\n";
							break;
						}
					}

					if ($dynamicAssociation == false) {
						// let's look for a find statment
						if (strpos($command, "->find") > 0) {
							$command = '$data = $this->' . $command . ";";
							eval($command);

							foreach ($data as $results) {
								foreach ($results as $modelName => $result) {
									$this->out("$modelName");
									foreach ($result as $field => $value) {
                                        if (is_array($value)) {
                                            foreach($value as $field2 => $value2) {
                                                $this->out("\t\t$field2: $value2");
                                            }
                                        } else {
                                            $this->out("\t$field: $value");
                                        }
									}
								}

								$this->hr();
							}
						}
					}
				break;
			}
		}
	}
}
	function fatal_error_handler($buffer) {
		if(ereg("(error</b>:)(.+)(<br)", $buffer, $regs) ) {
			$err = preg_replace("/<.*?>/", "", $regs[2]);
			error_log($err);
			return "ERROR CAUGHT check log file";
		}
		return $buffer;
	}

	function handle_error ($errno, $errstr, $errfile, $errline) {
		error_log("$errstr in $errfile on line $errline");
		if($errno == FATAL || $errno == ERROR){
			ob_end_flush();
			echo "ERROR CAUGHT check log file";
			exit(0);
		}
	}
?>
