SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

DROP SCHEMA IF EXISTS `tinyauthdb` ;
CREATE SCHEMA IF NOT EXISTS `tinyauthdb` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
USE `tinyauthdb` ;

-- -----------------------------------------------------
-- Table `tinyauthdb`.`acls`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tinyauthdb`.`acls` ;

CREATE  TABLE IF NOT EXISTS `tinyauthdb`.`acls` (
  `id` INT(11) NOT NULL ,
  `controller` VARCHAR(65) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
COMMENT = 'This table only lists controller names';


-- -----------------------------------------------------
-- Table `tinyauthdb`.`acl_functions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tinyauthdb`.`acl_functions` ;

CREATE  TABLE IF NOT EXISTS `tinyauthdb`.`acl_functions` (
  `id` INT(11) NOT NULL ,
  `acl_id` INT(11) NULL ,
  `function` VARCHAR(65) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_acl_functions_acl_id` (`acl_id` ASC) ,
  CONSTRAINT `FK_acl_functions_acl_id`
    FOREIGN KEY (`acl_id` )
    REFERENCES `tinyauthdb`.`acls` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'This table lists the functions of controllers';


-- -----------------------------------------------------
-- Table `tinyauthdb`.`roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tinyauthdb`.`roles` ;

CREATE  TABLE IF NOT EXISTS `tinyauthdb`.`roles` (
  `id` INT(11) NOT NULL ,
  `name` VARCHAR(45) NULL ,
  `description` VARCHAR(254) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
COMMENT = 'This table lists roles that can be used for grouping user permissions';


-- -----------------------------------------------------
-- Table `tinyauthdb`.`acl_roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tinyauthdb`.`acl_roles` ;

CREATE  TABLE IF NOT EXISTS `tinyauthdb`.`acl_roles` (
  `id` INT(11) NOT NULL ,
  `acl_id` INT(11) NULL ,
  `acl_function_id` INT(11) NULL ,
  `role_id` INT(11) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_acl_roles_acl_id` (`acl_id` ASC) ,
  INDEX `FK_acl_roles_acl_function_id` (`acl_function_id` ASC) ,
  INDEX `FK_acl_roles_role_id` (`role_id` ASC) ,
  CONSTRAINT `FK_acl_roles_acl_id`
    FOREIGN KEY (`acl_id` )
    REFERENCES `tinyauthdb`.`acls` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_acl_roles_acl_function_id`
    FOREIGN KEY (`acl_function_id` )
    REFERENCES `tinyauthdb`.`acl_functions` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_acl_roles_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `tinyauthdb`.`roles` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'This table links roles with controllers and their functions';


-- -----------------------------------------------------
-- Table `tinyauthdb`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tinyauthdb`.`users` ;

CREATE  TABLE IF NOT EXISTS `tinyauthdb`.`users` (
  `id` INT NOT NULL ,
  `username` VARCHAR(45) NULL ,
  `password` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
COMMENT = 'This table lists the users';


-- -----------------------------------------------------
-- Table `tinyauthdb`.`roles_users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tinyauthdb`.`roles_users` ;

CREATE  TABLE IF NOT EXISTS `tinyauthdb`.`roles_users` (
  `id` INT(11) NOT NULL ,
  `role_id` INT(11) NULL ,
  `user_id` INT(11) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `FK_roles_users_role_id` (`role_id` ASC) ,
  INDEX `FK_roles_users_user_id` (`user_id` ASC) ,
  CONSTRAINT `FK_roles_users_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `tinyauthdb`.`roles` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_roles_users_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `tinyauthdb`.`users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'This table is only a joiner table for HABTM relationships';


CREATE USER `tinyauthdb`@`localhost` IDENTIFIED BY 'tinyauthdb';

grant ALL on TABLE `tinyauthdb`.`acl_functions` to tinyauthdb`@`localhost;
grant ALL on TABLE `tinyauthdb`.`acl_roles` to tinyauthdb`@`localhost;
grant ALL on TABLE `tinyauthdb`.`acls` to tinyauthdb`@`localhost;
grant ALL on TABLE `tinyauthdb`.`roles` to tinyauthdb`@`localhost;
grant ALL on TABLE `tinyauthdb`.`roles_users` to tinyauthdb`@`localhost;
grant ALL on TABLE `tinyauthdb`.`users` to tinyauthdb`@`localhost;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
