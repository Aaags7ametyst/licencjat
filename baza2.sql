SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`lic_users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lic_users` (
  `iduser` INT NOT NULL AUTO_INCREMENT,
  `login` CHAR(45) NOT NULL,
  `password` CHAR(255) NOT NULL,
  `email` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`iduser`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lic_categories`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lic_categories` (
  `idcategory` INT NOT NULL AUTO_INCREMENT,
  `name` CHAR(45) NOT NULL,
  PRIMARY KEY (`idcategory`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lic_comments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `lic_comments` (
  `idcomment` INT NOT NULL AUTO_INCREMENT,
  `content` TEXT NOT NULL,
  `published` DATE NOT NULL,
  `idpost` INT NOT NULL,
  `author` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`idcomment`))
ENGINE = InnoDB DEFAULT CHARSET=utf8;



-- -----------------------------------------------------
-- Table `mydb`.`lic_roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lic_roles` (
  `idrole` INT NOT NULL,
  `role` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`idrole`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lic_users_roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lic_users_roles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `iduser` INT NOT NULL,
  `idrole` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lic_posts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lic_posts` (
  `idpost` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) NOT NULL,
  `content` TEXT NOT NULL,
  `published` DATE NOT NULL,
  `idcategory` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`idpost`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
