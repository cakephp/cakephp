<?php // $Id$ 

class File
{
	var $path = null;
	
	function File ($path)
	{
		$this->path = $path;
	}
	
	function read ()
	{
		return file_get_contents($this->path);
	}
	
	function append ($data)
	{
		return $this->write($data, 'a');
	}
	
	function write ($data, $mode = 'w')
	{
		if (!($handle = fopen($this->path, $mode)))
		{
			print ("[File] Could not open {$this->path} with mode $mode!");
			return false;
		}
			
		if (!fwrite($handle, $data))
			return false;
			
		if (!fclose($handle))
			return false;
		
		return true;
	}
}

?>