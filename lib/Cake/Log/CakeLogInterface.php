<?php

/**
 * CakeLogStreamInterface is the interface that should be implemented
 * by all classes that are going to be used as Log streams.
 *
 * @package cake.libs
 */
interface CakeLogInterface {
/**
 * Write method to handle writes being made to the Logger
 *
 * @param string $type 
 * @param string $message 
 * @return void
 */
	public function write($type, $message);
}