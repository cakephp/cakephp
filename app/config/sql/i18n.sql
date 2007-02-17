CREATE TABLE i18n (
  id int(10) NOT NULL auto_increment,
  locale varchar(6) NOT NULL,
  i18n_content_id int(10) NOT NULL,
  model varchar(255) NOT NULL,
  row_id int(10) NOT NULL,
  field varchar(255) NOT NULL,
  PRIMARY KEY  (id),
  KEY row_id (row_id),
  KEY model (model),
  KEY field (field)
);

CREATE TABLE i18n_content (
  id int(10) NOT NULL auto_increment,
  content text,
  PRIMARY KEY  (id)
);
