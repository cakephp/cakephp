#!/usr/bin/env bash
################################################################################
#
# Bake is a shell script for running CakePHP bake script
# PHP 5
#
# CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
# Copyright 2005-2012, Cake Software Foundation, Inc.
#
# Licensed under The MIT License
# Redistributions of files must retain the above copyright notice.
#
# @copyright    Copyright 2005-2012, Cake Software Foundation, Inc.
# @link         http://cakephp.org CakePHP(tm) Project
# @package      cake.Console
# @since        CakePHP(tm) v 1.2.0.5012
# @license      MIT License (http://www.opensource.org/licenses/mit-license.php)
#
################################################################################

# Canonicalize by following every symlink of the given name recursively
canonicalize() {
	NAME=$1
	while [ -h "$NAME" ]; do
		DIR=$(dirname -- "$NAME")
		SYM=$(readlink "$NAME")
		NAME=$(cd "$DIR" && cd $(dirname -- "$SYM") && pwd)/$(basename -- "$SYM")
	done
	echo "$NAME"
}

CONSOLE=$(dirname $(canonicalize "$0"))
APP=`pwd`

exec php -q $CONSOLE/cake.php -working "$APP" "$@"

exit;
