<?php

/**
 * Interactive console for use with CakePHP
 *
 * @author Chris Hartjes
 * @email chartjes@littlehart.net
 */
class Console extends ConsoleScript {

	function main() {
		$models = @loadModels();
		foreach ($models as $model) {
			$class = Inflector::camelize(r('.php', '', $model));
			$models[$model] = $class;
			@${$class} =& new $class();
		}

		while (true) {
			$command = trim($this->in(''));
			switch($command) {
				case 'quit':
				case 'exit':
					return true;
				break;
				case 'models':
					$this->out('Model classes:');
					$this->out('--------------');
					foreach ($models as $model) {
						$this->out(" - {$model}");
					}
				break;
				default:
					print_r(eval('return ' . $command));
				break;
			}
		}
	}
}

function fatal_error_handler($buffer) {
  if (ereg("(error</b>:)(.+)(<br)", $buffer, $regs) ) {
   $err = preg_replace("/<.*?>/","",$regs[2]);
   error_log($err);
   return "ERROR CAUGHT check log file";
  }
  return $buffer;
}

function handle_error ($errno, $errstr, $errfile, $errline)
{
   error_log("$errstr in $errfile on line $errline");
   if($errno == FATAL || $errno == ERROR){
       ob_end_flush();
       echo "ERROR CAUGHT check log file";
       exit(0);
   }
}



?>