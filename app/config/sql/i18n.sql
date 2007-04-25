# $Id$
#
# Copyright 2005-2007,	Cake Software Foundation, Inc.
#								1785 E. Sahara Avenue, Suite 490-204
#								Las Vegas, Nevada 89104
#
# Licensed under The MIT License
# Redistributions of files must retain the above copyright notice.
# http://www.opensource.org/licenses/mit-license.php The MIT License

CREATE TABLE i18n (
	id int(10) NOT NULL auto_increment,
	locale varchar(6) NOT NULL,
	i18n_content_id int(10) NOT NULL,
	model varchar(255) NOT NULL,
	row_id int(10) NOT NULL,
	field varchar(255) NOT NULL,
	PRIMARY KEY	(id),
	KEY locale	(locale),
	KEY i18n_content_id (i18n_content_id),
	KEY row_id	(row_id),
	KEY model	(model),
	KEY field (field)
);

CREATE TABLE i18n_content (
	id int(10) NOT NULL auto_increment,
	content text,
	PRIMARY KEY  (id)
);