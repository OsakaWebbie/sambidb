-- MySQL dump 10.11
--
-- Host: localhost    Database: sambi_abide
-- ------------------------------------------------------
-- Server version	5.0.95-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `pw_config`
--

DROP TABLE IF EXISTS `pw_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_config` (
  `Parameter` varchar(30) NOT NULL default '',
  `Value` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`Parameter`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_event`
--

DROP TABLE IF EXISTS `pw_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_event` (
  `EventID` int(11) unsigned NOT NULL auto_increment,
  `Event` varchar(60) default NULL,
  `Active` tinyint(4) default NULL,
  `Remarks` text,
  PRIMARY KEY  (`EventID`),
  KEY `Event` (`Event`,`Active`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Events where songs are sung';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_keyword`
--

DROP TABLE IF EXISTS `pw_keyword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_keyword` (
  `KeywordID` int(11) unsigned NOT NULL auto_increment,
  `Keyword` varchar(60) default NULL,
  PRIMARY KEY  (`KeywordID`),
  KEY `Keyword` (`Keyword`)
) ENGINE=MyISAM AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_log`
--

DROP TABLE IF EXISTS `pw_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_log` (
  `UserID` varchar(16) NOT NULL default '',
  `LoginTime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `IPAddress` varchar(16) default NULL,
  `UserAgent` varchar(100) default NULL,
  `Languages` varchar(100) default NULL,
  PRIMARY KEY  (`UserID`,`LoginTime`),
  KEY `IPAddress` (`IPAddress`),
  KEY `UserAgent` (`UserAgent`),
  KEY `LoginTime` (`LoginTime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Log of logins to PWDB';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_login`
--

DROP TABLE IF EXISTS `pw_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_login` (
  `UserID` varchar(16) NOT NULL default '',
  `Password` varchar(16) NOT NULL default '',
  `UserName` varchar(30) default '',
  `IncludeKeywords` varchar(100) default NULL,
  `ExcludeKeywords` varchar(100) default NULL,
  `CellEventID` int(11) unsigned NOT NULL default '0',
  `Admin` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`UserID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_output`
--

DROP TABLE IF EXISTS `pw_output`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_output` (
  `Class` varchar(20) NOT NULL default '',
  `OutputSQL` text NOT NULL,
  PRIMARY KEY  (`Class`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_outputset`
--

DROP TABLE IF EXISTS `pw_outputset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_outputset` (
  `SetName` varchar(40) NOT NULL default '',
  `OrderNum` tinyint(4) unsigned NOT NULL default '0',
  `Class` varchar(20) NOT NULL default '',
  `CSS` text,
  PRIMARY KEY  (`SetName`,`OrderNum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_pdfformat`
--

DROP TABLE IF EXISTS `pw_pdfformat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_pdfformat` (
  `FormatName` varchar(100) NOT NULL default '',
  `ListOrder` tinyint(4) NOT NULL default '0' COMMENT 'The sorting order in selection list',
  `LayoutParams` varchar(200) NOT NULL default 'a4paper, margin=8mm' COMMENT 'Parameters to geometry package - paper size must be first',
  `Landscape` tinyint(4) NOT NULL default '0' COMMENT '1 if landscape orientation',
  `NumColumns` tinyint(3) unsigned NOT NULL default '1' COMMENT 'Number of columns in layout',
  `Gutter` smallint(6) NOT NULL default '0' COMMENT 'Space between columns in mm',
  `TitleSizeSpace` varchar(10) NOT NULL default '14,16' COMMENT 'Fontsize,Lineheight in points',
  `TitleAlign` varchar(6) NOT NULL default 'left' COMMENT '"left", "center", or "right"',
  `TitleStyle` varchar(100) NOT NULL default '' COMMENT 'LaTeX commands',
  `TitleHanging` varchar(10) NOT NULL default '2em' COMMENT 'Amount of hanging indent - include unit',
  `LyricsSizeSpace` varchar(10) NOT NULL default '11,12' COMMENT 'Fontsize,Lineheight in points',
  `LyricsParskip` varchar(10) NOT NULL default '0.3em' COMMENT 'Extra space between song lines (paragraphs)',
  `LyricsHanging` varchar(10) NOT NULL default '2em' COMMENT 'Amount of hanging indent - include unit',
  `RomajiSizeSpace` varchar(10) NOT NULL default '10,11' COMMENT 'Fontsize,Lineheight in points',
  `RomajiStyle` varchar(100) NOT NULL default '' COMMENT 'LaTeX commands',
  `RomajiHanging` varchar(10) NOT NULL default '2em' COMMENT 'Amount of hanging indent - include unit',
  `InstructionSizeSpace` varchar(10) NOT NULL default '10,11' COMMENT 'Fontsize,Lineheight in points',
  `InstructionStyle` varchar(100) NOT NULL default '' COMMENT 'LaTeX commands',
  `InstructionHanging` varchar(10) NOT NULL default '2em' COMMENT 'Amount of hanging indent - include unit',
  `CreditSizeSpace` varchar(10) NOT NULL default '8,9' COMMENT 'Fontsize,Lineheight in points',
  `CreditAlign` varchar(6) NOT NULL default 'left' COMMENT '"left", "center", or "right"',
  `CreditStyle` varchar(100) NOT NULL default '' COMMENT 'LaTeX commands',
  `CreditHanging` varchar(10) NOT NULL default '2em' COMMENT 'Amount of hanging indent - include unit',
  `BetweenSongs` varchar(100) NOT NULL default '' COMMENT 'LaTeX commands',
  `TitleNumbering` varchar(10) NOT NULL default 'circle' COMMENT 'Whether to number the songs: "basic", "circle", or "none"',
  `TitleWithKey` tinyint(1) NOT NULL default '0' COMMENT 'Whether to append song key to title',
  `Instruction` varchar(10) NOT NULL default 'none' COMMENT 'Whether to include song instruction line:"long", "short", or "none"',
  `Credit` varchar(10) NOT NULL default 'none' COMMENT 'Composer and copyright: "before", "before-twoline", "after", "after-twoline", or "none"',
  `Chords` tinyint(1) NOT NULL default '1' COMMENT 'Chords enabled by default?',
  `Romaji` varchar(10) NOT NULL default 'chordless' COMMENT 'Default display of romaji: "chordless", "hide", "only", "showall"',
  `UseColor` tinyint(1) NOT NULL default '1' COMMENT 'Whether to use color in PDF by default',
  PRIMARY KEY  (`FormatName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Layout settings for TeX-generated PDF';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_song`
--

DROP TABLE IF EXISTS `pw_song`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_song` (
  `SongID` int(11) unsigned NOT NULL auto_increment,
  `Title` varchar(90) NOT NULL default '',
  `OrigTitle` varchar(90) default NULL,
  `Composer` varchar(240) default NULL,
  `Copyright` varchar(240) default NULL,
  `SongKey` varchar(20) default NULL,
  `Tempo` varchar(8) default NULL,
  `Source` text,
  `Lyrics` text,
  `Instruction` varchar(255) default NULL,
  `Pattern` varchar(80) NOT NULL,
  `Audio` tinyint(4) NOT NULL default '0',
  `AudioComment` varchar(255) default NULL,
  `Tagged` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`SongID`),
  KEY `Title` (`Title`,`SongKey`,`Tempo`),
  KEY `Tagged` (`Tagged`),
  KEY `OrigTitle` (`OrigTitle`),
  KEY `Lyrics` (`Lyrics`(50))
) ENGINE=MyISAM AUTO_INCREMENT=712 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_songkey`
--

DROP TABLE IF EXISTS `pw_songkey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_songkey` (
  `SongID` int(11) NOT NULL default '0',
  `KeywordID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`SongID`,`KeywordID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Many-many link table';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pw_usage`
--

DROP TABLE IF EXISTS `pw_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pw_usage` (
  `SongID` int(11) NOT NULL default '0',
  `EventID` int(11) NOT NULL default '0',
  `UseDate` date NOT NULL default '0000-00-00',
  `UseOrder` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`EventID`,`UseDate`,`UseOrder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='To record when a song was used';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-11-16 17:57:50
