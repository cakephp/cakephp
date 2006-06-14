CREATE TABLE `i18n` (
  `id` int(11) NOT NULL auto_increment,
  `locale` varchar(8) NOT NULL default '',
  `i18n_content_id` int(11) NOT NULL default '0',
  `model` varchar(255) NOT NULL default '',
  `row_id` int(11) NOT NULL default '0',
  `field` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `row_id` (`row_id`),
  KEY `model` (`model`),
  KEY `field` (`field`)
);

CREATE TABLE `i18n_content` (
  `id` int(11) NOT NULL auto_increment,
  `content` text,
  PRIMARY KEY  (`id`)
);
