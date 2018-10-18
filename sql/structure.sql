SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `PClass` (
  `ID` varchar(15) NOT NULL COMMENT 'Название',
  `Public` varchar(1) NOT NULL DEFAULT 'Y',
  `Description` varchar(100) NOT NULL COMMENT 'Описание',
  `RemindTeachers` char(1) NOT NULL DEFAULT 'N' COMMENT 'Напоминать учителям о невнесённых задачах',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Классы';

CREATE TABLE IF NOT EXISTS `PList` (
  `ID` int(6) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный ID',
  `ListTypeID` int(6) NOT NULL DEFAULT '1' COMMENT 'Тип',
  `ClassID` varchar(15) DEFAULT NULL COMMENT 'Класс',
  `Number` varchar(15) NOT NULL COMMENT 'Номер',
  `Description` varchar(100) NOT NULL COMMENT 'Описание',
  `Date` varchar(50) NOT NULL COMMENT 'Дата листка',
  `MinFor5` decimal(5,2) NOT NULL DEFAULT '-1.00' COMMENT 'Минимальное кол-во баллов на 5-ку',
  `MinFor4` decimal(5,2) NOT NULL DEFAULT '-1.00' COMMENT 'Минимальное кол-во баллов на 4-ку',
  `MinFor3` decimal(5,2) NOT NULL DEFAULT '-1.00' COMMENT 'Минимальное кол-во баллов на 3-ку',
  PRIMARY KEY (`ID`),
  KEY `ListType` (`ListTypeID`),
  KEY `ClassID` (`ClassID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Листки' ;

CREATE TABLE IF NOT EXISTS `PListType` (
  `ID` int(6) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный ID',
  `Description` varchar(100) NOT NULL COMMENT 'Описание',
  `InStats` tinyint(1) NOT NULL COMMENT 'Учитывать в статистике',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Типы листков' ;

CREATE TABLE IF NOT EXISTS `PProblem` (
  `ID` int(6) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный ID',
  `ProblemTypeID` int(6) NOT NULL DEFAULT '0' COMMENT 'Тип задачи',
  `ListID` int(6) NOT NULL COMMENT 'Листок',
  `Number` int(5) NOT NULL COMMENT 'Порядковый номер в листке',
  `Group` int(5) NOT NULL COMMENT 'Номер группы',
  `Name` varchar(10) NOT NULL COMMENT 'Название',
  PRIMARY KEY (`ID`),
  KEY `ProblemType` (`ProblemTypeID`),
  KEY `List` (`ListID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Задачи' ;

CREATE TABLE IF NOT EXISTS `PProblemType` (
  `ID` int(6) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный ID',
  `Sign` char(3) NOT NULL COMMENT 'Обозначение',
  `Description` varchar(100) NOT NULL COMMENT 'Описание',
  `ProbValue` decimal(4,2) NOT NULL DEFAULT '1.00' COMMENT 'Стоимость задачи при вычислении оценки',
  `NotSolvedPen` decimal(4,2) NOT NULL DEFAULT '0.00' COMMENT 'Штраф за нерешённую задачу',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Типы задач' ;

CREATE TABLE IF NOT EXISTS `PPupil` (
  `ID` int(6) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный ID',
  `ClassID` varchar(15) NOT NULL COMMENT 'Класс',
  `Name1` varchar(20) NOT NULL COMMENT 'Фамилия',
  `Name2` varchar(20) NOT NULL COMMENT 'Имя',
  `Name3` varchar(20) NOT NULL COMMENT 'Отчество',
  `Nick` varchar(15) NOT NULL COMMENT 'Короткое имя',
  `Teacher` varchar(50) DEFAULT NULL COMMENT 'Учитель',
  PRIMARY KEY (`ID`),
  KEY `Class` (`ClassID`),
  KEY `Teacher` (`Teacher`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `PResult` (
  `PupilID` int(6) NOT NULL COMMENT 'Школьник',
  `ProblemID` int(6) NOT NULL COMMENT 'Задача',
  `Mark` varchar(50) NOT NULL COMMENT 'Отметка',
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Время внесения в базу',
  `User` varchar(50) DEFAULT NULL COMMENT 'Кто внёс?',
  UNIQUE KEY `Result` (`PupilID`,`ProblemID`),
  KEY `Pupil` (`PupilID`),
  KEY `Problem` (`ProblemID`),
  KEY `User` (`User`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Кондуит';

CREATE TABLE IF NOT EXISTS `PResultHistory` (
  `ID` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `PupilID` int(6) NOT NULL COMMENT 'Школьник',
  `ProblemID` int(6) NOT NULL COMMENT 'Задача',
  `Mark` varchar(50) NOT NULL COMMENT 'Отметка',
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Время внесения в базу',
  `User` varchar(50) NOT NULL COMMENT 'Кто внёс?',
  PRIMARY KEY (`ID`),
  KEY `PupilID` (`PupilID`,`ProblemID`,`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Кондуит (история)' ;

CREATE TABLE IF NOT EXISTS `PRole` (
  `Name` varchar(10) NOT NULL,
  `ManageMarks` tinyint(1) NOT NULL,
  `ManageLists` tinyint(1) NOT NULL,
  `ManageClasses` tinyint(1) NOT NULL,
  `ManageUsers` tinyint(1) NOT NULL,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Пользовательские роли';

CREATE TABLE IF NOT EXISTS `PUser` (
  `User` varchar(50) NOT NULL COMMENT 'Имя пользователя',
  `DisplayName` varchar(50) NOT NULL COMMENT 'Отображаемое имя',
  `Email` varchar(50) DEFAULT NULL COMMENT 'E-Mail',
  `Disabled` char(1) NOT NULL DEFAULT 'N' COMMENT 'Отключён',
  PRIMARY KEY (`User`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Пользовательские группы';

CREATE TABLE IF NOT EXISTS `PUserRole` (
  `ID` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `User` varchar(50) NOT NULL COMMENT 'Пользователь',
  `Class` varchar(15) NOT NULL COMMENT 'Класс',
  `Role` varchar(10) NOT NULL COMMENT 'Роль',
  PRIMARY KEY (`ID`),
  KEY `User` (`User`),
  KEY `ClassID` (`Class`),
  KEY `Role` (`Role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Настройки доступа пользователей к классам';

CREATE TABLE IF NOT EXISTS `PWorkDays` (
  `Day` int(1) NOT NULL COMMENT 'Номер дня недели'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Дни недели, в которые есть уроки';


ALTER TABLE `PList`
  ADD CONSTRAINT `PList_ibfk_1` FOREIGN KEY (`ListTypeID`) REFERENCES `PListType` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `PList_ibfk_2` FOREIGN KEY (`ClassID`) REFERENCES `PClass` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `PProblem`
  ADD CONSTRAINT `PProblem_ibfk_1` FOREIGN KEY (`ProblemTypeID`) REFERENCES `PProblemType` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `PProblem_ibfk_2` FOREIGN KEY (`ListID`) REFERENCES `PList` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `PPupil`
  ADD CONSTRAINT `PPupil_ibfk_2` FOREIGN KEY (`Teacher`) REFERENCES `PUser` (`User`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `PPupil_ibfk_3` FOREIGN KEY (`ClassID`) REFERENCES `PClass` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `PResult`
  ADD CONSTRAINT `PResult_ibfk_1` FOREIGN KEY (`PupilID`) REFERENCES `PPupil` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `PResult_ibfk_2` FOREIGN KEY (`ProblemID`) REFERENCES `PProblem` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `PResult_ibfk_3` FOREIGN KEY (`User`) REFERENCES `PUser` (`User`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `PUserRole`
  ADD CONSTRAINT `PUserRole_ibfk_1` FOREIGN KEY (`User`) REFERENCES `PUser` (`User`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `PUserRole_ibfk_3` FOREIGN KEY (`Role`) REFERENCES `PRole` (`Name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `PUserRole_ibfk_4` FOREIGN KEY (`Class`) REFERENCES `PClass` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
