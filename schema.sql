-- Adminer 5.4.1 MariaDB 10.11.14-MariaDB-0+deb12u2 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

SET NAMES utf8mb4;

DELIMITER ;;

DROP FUNCTION IF EXISTS `stripchord`;;
CREATE FUNCTION `stripchord` (`what` text CHARACTER SET 'utf8mb4') RETURNS text CHARACTER SET 'utf8mb4' LANGUAGE SQL
begin
  declare start,stop integer default 1;
  if what is null then
    return null;
  end if;
  l00p: loop
    set start=locate('[',what,start);
    if start=0 then
      leave l00p;
    end if;
    set stop=locate(']',what,start);
    if stop=0 then
      leave l00p;
    end if;
    set what=concat(left(what,start-1),mid(what,stop+1));
  end loop;
return what;
end;;

DELIMITER ;

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `Parameter` varchar(30) NOT NULL DEFAULT '',
  `Value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`Parameter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `event`;
CREATE TABLE `event` (
  `EventID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `Event` varchar(60) NOT NULL DEFAULT '',
  `Active` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1 if currently used, 0 if old archived',
  `Remarks` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`EventID`),
  KEY `Event` (`Event`,`Active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Events where songs are sung';


DROP TABLE IF EXISTS `history`;
CREATE TABLE `history` (
  `SongID` mediumint(8) NOT NULL DEFAULT 0,
  `EventID` mediumint(8) NOT NULL DEFAULT 0,
  `UseDate` date NOT NULL DEFAULT '0000-00-00',
  `UseOrder` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`EventID`,`UseDate`,`UseOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='To record when a song was used';


DROP TABLE IF EXISTS `loginlog`;
CREATE TABLE `loginlog` (
  `UserID` varchar(16) NOT NULL DEFAULT '',
  `LoginTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `IPAddress` char(15) NOT NULL DEFAULT '',
  `UserAgent` varchar(255) NOT NULL DEFAULT '',
  `Languages` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`UserID`,`LoginTime`),
  KEY `IPAddress` (`IPAddress`),
  KEY `LoginTime` (`LoginTime`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='Log of logins to SambiDB';


DROP TABLE IF EXISTS `pdfformat`;
CREATE TABLE `pdfformat` (
  `FormatName` varchar(100) NOT NULL DEFAULT '',
  `ListOrder` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'The sorting order in selection list',
  `LayoutParams` varchar(200) NOT NULL DEFAULT 'a4paper, margin=8mm' COMMENT 'Parameters to geometry package - paper size must be first',
  `Landscape` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 if landscape orientation',
  `NumColumns` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT 'Number of columns in layout',
  `Gutter` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Space between columns in mm',
  `TitleSizeSpace` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '14,16' COMMENT 'Fontsize,Lineheight in points',
  `TitleAlign` varchar(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'left' COMMENT '"left", "center", or "right"',
  `TitleStyle` varchar(100) NOT NULL DEFAULT '' COMMENT 'LaTeX commands',
  `TitleHanging` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '2em' COMMENT 'Amount of hanging indent - include unit',
  `LyricsSizeSpace` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '11,12' COMMENT 'Fontsize,Lineheight in points',
  `LyricsParskip` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '0.3em' COMMENT 'Extra space between song lines (paragraphs)',
  `LyricsHanging` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '2em' COMMENT 'Amount of hanging indent - include unit',
  `RomajiSizeSpace` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '10,11' COMMENT 'Fontsize,Lineheight in points',
  `RomajiStyle` varchar(100) NOT NULL DEFAULT '' COMMENT 'LaTeX commands',
  `RomajiHanging` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '2em' COMMENT 'Amount of hanging indent - include unit',
  `InstructionSizeSpace` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '10,11' COMMENT 'Fontsize,Lineheight in points',
  `InstructionStyle` varchar(100) NOT NULL DEFAULT '' COMMENT 'LaTeX commands',
  `InstructionHanging` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '2em' COMMENT 'Amount of hanging indent - include unit',
  `CreditSizeSpace` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '8,9' COMMENT 'Fontsize,Lineheight in points',
  `CreditAlign` varchar(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'left' COMMENT '"left", "center", or "right"',
  `CreditStyle` varchar(100) NOT NULL DEFAULT '' COMMENT 'LaTeX commands',
  `CreditHanging` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '2em' COMMENT 'Amount of hanging indent - include unit',
  `BetweenSongs` varchar(100) NOT NULL DEFAULT '' COMMENT 'LaTeX commands',
  `TitleNumbering` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'circle' COMMENT 'Whether to number the songs: "basic", "circle", or "none"',
  `TitleWithKey` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Whether to append song key to title',
  `Instruction` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'none' COMMENT 'Whether to include song instruction line:"long", "short", or "none"',
  `Credit` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'none' COMMENT 'Composer and copyright: "before", "before-twoline", "after", "after-twoline", or "none"',
  `Chords` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT 'Chords enabled by default?',
  `Romaji` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'chordless' COMMENT 'Default display of romaji: "chordless", "hide", "only", "showall"',
  `UseColor` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT 'Whether to use color in PDF by default',
  PRIMARY KEY (`FormatName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Layout settings for TeX-generated PDF';


DROP TABLE IF EXISTS `song`;
CREATE TABLE `song` (
  `SongID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(90) NOT NULL DEFAULT '',
  `OrigTitle` varchar(90) NOT NULL DEFAULT '',
  `Composer` varchar(240) NOT NULL DEFAULT '',
  `Copyright` varchar(240) NOT NULL DEFAULT '',
  `SongKey` varchar(20) NOT NULL DEFAULT '',
  `Tempo` varchar(8) NOT NULL DEFAULT '',
  `Source` text NOT NULL COMMENT 'What albums, videos, sheet music, etc. is referenced',
  `Lyrics` text NOT NULL,
  `Instruction` varchar(255) NOT NULL DEFAULT '',
  `Pattern` varchar(80) NOT NULL DEFAULT '',
  `Audio` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `AudioComment` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`SongID`),
  KEY `Title` (`Title`,`SongKey`,`Tempo`),
  KEY `OrigTitle` (`OrigTitle`),
  KEY `Lyrics` (`Lyrics`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `songtag`;
CREATE TABLE `songtag` (
  `SongID` mediumint(8) NOT NULL DEFAULT 0,
  `TagID` mediumint(8) NOT NULL DEFAULT 0,
  PRIMARY KEY (`SongID`,`TagID`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='Many-many link table';


DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `TagID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `Tag` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`TagID`),
  KEY `Keyword` (`Tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `UserID` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '' COMMENT 'For login form',
  `Password` char(41) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'hashed, of course',
  `UserName` varchar(100) NOT NULL DEFAULT '' COMMENT 'real name',
  `Email` varchar(200) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '' COMMENT 'for password reset',
  `Access` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=readonly, 1=normal, 2=admin',
  `Language` varchar(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'ja_JP' COMMENT 'en_US or ja_JP',
  `IncludeTags` varchar(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '' COMMENT 'for filter',
  `ExcludeTags` varchar(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '' COMMENT 'for filter',
  `Basket` varchar(2000) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '' COMMENT 'persistent save of session variable',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2026-05-12 06:03:38 UTC
