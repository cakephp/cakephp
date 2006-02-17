CREATE TABLE `acos` (
  `id` int(11) NOT NULL auto_increment,
  `model` varchar(255) NOT NULL default '',
  `object_id` int(11) default NULL,
  `alias` varchar(255) NOT NULL default '',
  `lft` int(11) default NULL,
  `rght` int(11) default NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE `aros` (
  `id` int(11) NOT NULL auto_increment,
  `model` varchar(255) NOT NULL default '',
  `user_id` int(11) default NULL,
  `alias` varchar(255) NOT NULL default '',
  `lft` int(11) default NULL,
  `rght` int(11) default NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE `aros_acos` (
  `id` int(11) NOT NULL auto_increment,
  `aro_id` int(11) default NULL,
  `aco_id` int(11) default NULL,
  `_create` int(1) NOT NULL default '0',
  `_read` int(1) NOT NULL default '0',
  `_update` int(1) NOT NULL default '0',
  `_delete` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);
