CREATE TABLE `artist` (
  `artistId` INTEGER PRIMARY KEY AUTOINCREMENT,
  `artistTypeId` INTEGER NOT NULL,
  `name` STRING NOT NULL,
  `slug` STRING NOT NULL,
  `bio` STRING NULL,
  UNIQUE (`slug`)
);

CREATE TABLE `artist_type` (
  `artistTypeId` INTEGER PRIMARY KEY AUTOINCREMENT,
  `type` STRING not null,
  `slug` STRING not null,
  UNIQUE (`slug`)
);

CREATE TABLE `event` (
  `eventId` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` STRING NOT NULL,
  `slug` STRING NOT NULL,
  `sub_name` STRING NULL,
  `dateStart` int(12) NULL,
  `dateEnd` int(12) NULL,
  `venueId` INTEGER NOT NULL,
  UNIQUE  (`slug`)
);

CREATE TABLE `planned_event` (
  `eventId` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` STRING NOT NULL,
  `slug` STRING NOT NULL,
  `sub_name` STRING NULL,
  `dateStart` int(12) NULL,
  `dateEnd` int(12) NULL,
  `venueId` INTEGER NOT NULL,
  `completeness` INTEGER,
  UNIQUE  (`slug`)
);

CREATE TABLE `event_artist` (
  `eventId` INTEGER NOT NULL,
  `artistId` INTEGER NOT NULL,
  `eventArtistName` STRING NULL,
  `priority` INTEGER NOT NULL,
  `sequence` INTEGER NOT NULL,
  PRIMARY KEY (`eventId`, `artistId`)
);

CREATE TABLE `venue` (
  `venueId` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` STRING NOT NULL,
  `slug` STRING NOT NULL,
  `address` STRING NOT NULL,
  `shortAddress` STRING NOT NULL,
  `latitude` DECIMAL NULL,
  `longitude` DECIMAL NULL,
  UNIQUE  (`slug`)
);

CREATE VIEW event_artist_full AS
  SELECT ea.*, a.* FROM event_artist ea
  INNER JOIN artist a ON a.artistId = ea.artistId;
