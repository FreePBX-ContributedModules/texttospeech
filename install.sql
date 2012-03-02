CREATE TABLE IF NOT EXISTS `texttospeech`
(
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` VARCHAR( 100 ) NOT NULL ,

	`engine` VARCHAR( 50 ) ,
	`arguments` VARCHAR( 255 ) ,
	
	`wait_before` TINYINT UNSIGNED DEFAULT '1' ,
	`wait_after` TINYINT UNSIGNED DEFAULT '0' ,

	`allow_skip` BOOL DEFAULT false ,
	`direct_dial` BOOL DEFAULT false ,
	`no_answer` BOOL DEFAULT false ,
	`return_ivr` BOOL DEFAULT false ,

	`destination` VARCHAR( 50 )
)
ENGINE = MYISAM ;
