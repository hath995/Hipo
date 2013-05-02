-- phpMyAdmin SQL Dump
-- version 3.3.10
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 22, 2012 at 08:10 PM
-- Server version: 5.1.56
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `hipo_dev`
--

-- --------------------------------------------------------

--
-- Table structure for table `pals_levels`
--

CREATE TABLE IF NOT EXISTS `pals_levels` (
  `level_num` tinyint(2) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`level_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pals_pages`
--

CREATE TABLE IF NOT EXISTS `pals_pages` (
  `IDX` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `level_num` tinyint(2) unsigned NOT NULL,
  `section_num` tinyint(2) unsigned NOT NULL,
  `page_num` int(7) unsigned DEFAULT NULL,
  `is_left_nav` tinyint(1) unsigned NOT NULL,
  `page_from` smallint(4) unsigned DEFAULT NULL,
  `page_next` smallint(4) unsigned DEFAULT NULL,
  `final_section_page` tinyint(1) DEFAULT NULL,
  `name` varchar(10) NOT NULL COMMENT 'this field is only here for reference as the NEW PALS code will not use this field. Pages are built/organized/ordered from level_num, section_num, and page_num',
  `type` varchar(25) NOT NULL COMMENT 'audio, video, query, recordvid, playvid, recordreflection',
  `title` varchar(255) DEFAULT NULL,
  `content_participant` text,
  `photo_file` varchar(16) DEFAULT NULL,
  `media_file` varchar(50) DEFAULT NULL,
  `review_video_file` varchar(50) DEFAULT NULL,
  `has_questions` tinyint(1) NOT NULL DEFAULT '0',
  `query_options` varchar(5000) DEFAULT NULL COMMENT 'this is only here for OLD PALS reference. The new PALS code does not pay attention to this field, you only need to place a "1" in "has_questions" then make an entry in the pals_questions table',
  `report_form_in` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'set to "1" to track in "Report Form: Participant Responses to Session x Questions"',
  `personalized_video` smallint(1) DEFAULT '0',
  `level_end` tinyint(1) DEFAULT NULL,
  `content_staff` varchar(5000) DEFAULT NULL,
  `notes` varchar(5000) DEFAULT NULL,
  `touchme` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`IDX`),
  KEY `level_num` (`level_num`),
  KEY `section_num` (`section_num`),
  KEY `page_num` (`page_num`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=484 ;

-- --------------------------------------------------------

--
-- Table structure for table `pals_questions`
--

CREATE TABLE IF NOT EXISTS `pals_questions` (
  `IDX` int(10) NOT NULL AUTO_INCREMENT,
  `level_num` tinyint(2) unsigned DEFAULT NULL,
  `section_num` tinyint(2) unsigned DEFAULT NULL,
  `page_num` int(7) unsigned DEFAULT NULL,
  `question_number` tinyint(2) unsigned DEFAULT NULL,
  `question_label` varchar(20) NOT NULL,
  `type` varchar(2) NOT NULL COMMENT 'tx = text box entry, ra = radio button group, ck = checkbox(s)',
  `question_text` text,
  `response_options` text,
  `response_correct` varchar(20) DEFAULT NULL COMMENT '"@@@" delimited "correct" choices',
  `incorrect_feedback` text,
  `scoreable` tinyint(1) NOT NULL DEFAULT '0',
  `data_label` varchar(8) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`IDX`),
  KEY `level_num` (`level_num`),
  KEY `section_num` (`section_num`),
  KEY `scoreable` (`scoreable`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=340 ;

-- --------------------------------------------------------

--
-- Table structure for table `pals_sections`
--

CREATE TABLE IF NOT EXISTS `pals_sections` (
  `IDX` smallint(3) unsigned NOT NULL AUTO_INCREMENT,
  `level_num` tinyint(2) unsigned NOT NULL,
  `section_num` tinyint(2) unsigned NOT NULL,
  `section_name` varchar(255) NOT NULL,
  PRIMARY KEY (`IDX`),
  KEY `level_num` (`level_num`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100 ;
