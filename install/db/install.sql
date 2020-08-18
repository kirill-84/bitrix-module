CREATE TABLE `REGIONS` (
	`ID` INT(11) PRIMARY KEY AUTO_INCREMENT,
	`NAME` VARCHAR(255) NOT NULL,
	`CODE` VARCHAR(255) NOT NULL
);

CREATE TABLE `CITY` (
	`ID` INT(11) PRIMARY KEY AUTO_INCREMENT,
	`NAME` VARCHAR(255) NOT NULL,
	`CODE` VARCHAR(255) NOT NULL,
	`REGION_ID` INT(11),
	FOREIGN KEY (`REGION_ID`) REFERENCES REGIONS (`ID`));

INSERT INTO `REGIONS` (`ID`, `NAME`, `CODE`) 
	VALUES (NULL, 'Ростовская область', 'rostovskaya-oblast'), (NULL, 'Московская область', 'moskovskaya-oblast'), (NULL, 'Краснодарский край', 'krasnodarskij-krai');

INSERT INTO `CITY` (`ID`, `NAME`, `CODE`, `REGION_ID`) 
	VALUES (NULL, 'Ростов-на-Дону', 'rostov-na-donu', 1), (NULL, 'Батайск', 'batajsk', 1), (NULL, 'Волгодонск', 'volgodonsk', 1), (NULL, 'Москва', 'moskva', 2), (NULL, 'Жуковский', 'zhukovskij', 2), (NULL, 'Звенигород', 'zvenigorod', 2), (NULL, 'Краснодар', 'krasnodar', 3), (NULL, 'Армавир', 'armavir', 3), (NULL, 'Геленджик', 'gelendzhik', 3);
