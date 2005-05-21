<?PHP

class Log {

	function write($type, $msg) {
		$out = date('y-m-d H:i:s').' '.ucfirst($type).': '.$msg."\r\n";
		$fn = LOGS.$type.'.log';

		$log = fopen($fn, 'a+');
		fwrite($log, $out);
		fclose($log);
	}

}

?>