CREATE TABLE artist (
  artistId serial not null,
  artistTypeId integer,
  name varchar(200) NOT NULL,
  slug varchar(80) NOT NULL,
  bio TEXT NULL,
  PRIMARY KEY (artistId),
  UNIQUE (slug)
);

CREATE TABLE artist_type (
  artistTypeId serial,
  type varchar(80) not null,
  slug varchar(80) not null,
  PRIMARY KEY (artistTypeId),
  UNIQUE (slug)
);

CREATE TABLE event (
  eventId serial,
  name varchar(100) NOT NULL,
  slug varchar(80) NOT NULL,
  sub_name varchar(200) NULL,
  dateStart timestamp NOT NULL,
  dateEnd timestamp NULL,
  venueId integer NULL,
  PRIMARY KEY (eventId),
  UNIQUE (slug)
);

CREATE TABLE planned_event (
  eventId serial,
  name varchar(100) NOT NULL,
  slug varchar(80) NOT NULL,
  sub_name varchar(200) NULL,
  dateStart DATE NULL,
  dateEnd DATE NULL,
  venueId integer NOT NULL,
  completeness smallint,
  PRIMARY KEY (eventId),
  UNIQUE (slug)
);

CREATE TABLE event_artist (
  eventId integer NOT NULL,
  artistId integer NOT NULL,
  eventArtistName varchar(200) DEFAULT NULL,
  priority integer NOT NULL,
  sequence integer NOT NULL,
  PRIMARY KEY (eventId,artistId)
);

CREATE TABLE ticket (
  ticketId serial,
  eventId integer NOT NULL,
  name varchar(200) not null,
  cost decimal(18,2) not null,
  numAvailable integer not null default '0',
  numSold integer not null default '0',
  PRIMARY KEY (ticketId)
);

CREATE TABLE venue (
  venueId serial,
  name varchar(50) NOT NULL,
  slug varchar(50) NOT NULL,
  address varchar(400) NOT NULL,
  shortAddress varchar(80) NOT NULL,
  latitude DECIMAL NULL,
  longitude DECIMAL NULL,
  PRIMARY KEY (venueId),
  UNIQUE (slug)
);

CREATE VIEW event_artist_full AS
  SELECT ea.eventId, ea.eventArtistName, ea.priority, ea.sequence, a.*
  FROM event_artist ea
  INNER JOIN artist a ON a.artistId = ea.artistId;

ALTER TABLE event
  ADD CONSTRAINT FK_event_venue FOREIGN KEY (venueId) REFERENCES venue (venueId);

ALTER TABLE artist
  ADD CONSTRAINT FK_artist_artisttype FOREIGN KEY (artistTypeId) REFERENCES artist_type (artistTypeId);

ALTER TABLE ticket
  ADD CONSTRAINT FK_ticket_event FOREIGN KEY (eventId) REFERENCES event (eventId);

ALTER TABLE event_artist
  ADD CONSTRAINT FK_eventartist_artist FOREIGN KEY (artistId) REFERENCES artist (artistId),
  ADD CONSTRAINT FK_eventartist_event FOREIGN KEY (eventId) REFERENCES event (eventId);

