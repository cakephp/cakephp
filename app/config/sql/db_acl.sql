# $Id$
#
# Copyright 2005-2007,	Cake Software Foundation, Inc.
#								1785 E. Sahara Avenue, Suite 490-204
#								Las Vegas, Nevada 89104
#
# Licensed under The MIT License
# Redistributions of files must retain the above copyright notice.
# http://www.opensource.org/licenses/mit-license.php The MIT License

CREATE TABLE acos (
  id INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  model VARCHAR(255) NOT NULL DEFAULT '',
  object_id INTEGER(10) NULL DEFAULT NULL,
  alias VARCHAR(255) NOT NULL DEFAULT '',
  lft INTEGER(10) NULL DEFAULT NULL,
  rght INTEGER(10) NULL DEFAULT NULL,
  PRIMARY KEY(id)
);

CREATE TABLE aros_acos (
  id INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  aro_id INTEGER(10) UNSIGNED NOT NULL,
  aco_id INTEGER(10) UNSIGNED NOT NULL,
  _create CHAR(2) NOT NULL DEFAULT 0,
  _read CHAR(2) NOT NULL DEFAULT 0,
  _update CHAR(2) NOT NULL DEFAULT 0,
  _delete CHAR(2) NOT NULL DEFAULT 0,
  PRIMARY KEY(id)
);

CREATE TABLE aros (
  id INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  model VARCHAR(255) NOT NULL DEFAULT '',
  foreign_key INTEGER(10) UNSIGNED NULL DEFAULT NULL,
  alias VARCHAR(255) NOT NULL DEFAULT '',
  lft INTEGER(10) NULL DEFAULT NULL,
  rght INTEGER(10) NULL DEFAULT NULL,
  PRIMARY KEY(id)
);