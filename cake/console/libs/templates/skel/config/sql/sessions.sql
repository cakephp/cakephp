-- @copyright	Copyright 2005-2007, Cake Software Foundation, Inc.
-- @link		 http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
-- @since		CakePHP v 0.10.8.1997
-- @version	  $Revision$

CREATE TABLE cake_sessions (
  id varchar(255) NOT NULL default '',
  data text,
  expires int(11) default NULL,
  PRIMARY KEY  (id)
);