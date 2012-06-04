CREATE TABLE `artist` (
  `artistId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `artistTypeId` int(10) unsigned NOT NULL,
  `name` varchar(200) DEFAULT NOT NULL,
  `slug` varchar(80) DEFAULT NOT NULL,
  `bio` TEXT NULL,
  PRIMARY KEY (`artistId`),
  KEY `FK_artist_type` (`artistTypeId`),
  UNIQUE KEY `FK_artist_slug` (`slug`)
) ENGINE=InnoDB;

CREATE TABLE `artist_type` (
  `artistTypeId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(80) not null,
  `slug` varchar(80) not null,
  PRIMARY KEY (`artistId`),
  UNIQUE KEY `FK_artist_type_slug` (`slug`)
) ENGINE=InnoDB;

CREATE TABLE `event` (
  `eventId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `sub_name` varchar(200) NULL,
  `dateStart` DATETIME NOT NULL,
  `dateEnd` DATETIME NULL,
  `venueId` int(10) unsigned NULL,
  PRIMARY KEY (`eventId`),
  UNIQUE KEY `FK_event_slug` (`slug`),
  KEY `FK_event_venue` (`venueId`)
) ENGINE=InnoDB;

CREATE TABLE `planned_event` (
  `eventId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `sub_name` varchar(200) NULL,
  `dateStart` DATE NULL,
  `dateEnd` DATE NULL,
  `venueId` int(10) unsigned NOT NULL,
  `completeness` tinyint unsigned NOT NULL,
  PRIMARY KEY (`eventId`),
  UNIQUE KEY `FK_event_slug` (`slug`),
  KEY `FK_event_venue` (`venueId`)
) ENGINE=InnoDB;

CREATE TABLE `event_artist` (
  `eventId` int(10) unsigned NOT NULL,
  `artistId` int(10) unsigned NOT NULL,
  `eventArtistName` varchar(200) DEFAULT NULL,
  `priority` mediumint(8) unsigned NOT NULL,
  `sequence` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`eventId`,`artistId`)
) ENGINE=InnoDB;

CREATE TABLE `venue` (
  `venueId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `address` varchar(400) NOT NULL,
  `shortAddress` varchar(80) NOT NULL,
  `latitude` DECIMAL NULL,
  `longitude` DECIMAL NULL,
  PRIMARY KEY (`venueId`),
  UNIQUE KEY `FK_venue_slug` (`slug`)
) ENGINE=InnoDB;

CREATE VIEW event_artist_full AS
  SELECT ea.*, a.* FROM event_artist ea
  INNER JOIN artist a ON a.artistId = ea.artistId;

ALTER TABLE `event`
  ADD CONSTRAINT `FK_event_venue` FOREIGN KEY (`venueId`) REFERENCES `venue` (`venueId`);

ALTER TABLE `artist`
  ADD CONSTRAINT `FK_artist_artisttype` FOREIGN KEY (`artistTypeId`) REFERENCES `artist_type` (`artistTypeId`);

ALTER TABLE `event_artist`
  ADD CONSTRAINT `FK_eventartist_artist` FOREIGN KEY (`artistId`) REFERENCES `artist` (`artistId`),
  ADD CONSTRAINT `FK_eventartist_event` FOREIGN KEY (`eventId`) REFERENCES `event` (`eventId`);

