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
LIB=$(cd -P -- "$(dirname -- "$0")" && pwd -P) && LIB=$LIB/$(basename -- "$0")

while [ -h "$LIB" ]; do
	DIR=$(dirname -- "$LIB")
	SYM=$(readlink "$LIB")
	LIB=$(cd "$DIR" && cd $(dirname -- "$SYM") && pwd)/$(basename -- "$SYM")
done

LIB=$(dirname -- "$LIB")/
APP=`pwd`

exec php -q "$LIB"cake.php -working "$APP" "$@"

exit;
