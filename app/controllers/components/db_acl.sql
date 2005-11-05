CREATE TABLE `acos` (
  `id`		int(11) NOT NULL auto_increment,
  `object_id`	int(11) default NULL,
  `alias`	varchar(255) NOT NULL default '',
  `lft`		int(11) default NULL,
  `rght`	int(11) default NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `aros` (
  `id`		int(11) NOT NULL auto_increment,
  `user_id`	int(11) default NULL,
  `alias`	varchar(255) NOT NULL default '',
  `lft`		int(11) default NULL,
  `rght`	int(11) default NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `aros_acos` (
  `id`		int(11) NOT NULL auto_increment,
  `aro_id`	int(11) default NULL,
  `aco_id`	int(11) default NULL,
  `create`	tinyint(1) NOT NULL default '0',
  `read`	tinyint(1) NOT NULL default '0',
  `update`	tinyint(1) NOT NULL default '0',
  `delete`	tinyint(1) NOT NULL default '0',
  PRIMARY KEY (`id`)
);

CREATE TABLE `aco_actions` (
  `id`			int(11) NOT NULL auto_increment,
  `aros_acos_id`	int(11) default NULL,
  `action`		varchar(255) NOT NULL default '',
  `value`		tinyint(1) NOT NULL default '0',
  PRIMARY KEY (`id`)
);
