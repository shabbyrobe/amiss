INSERT INTO artist_type(artistTypeId, type, slug) VALUES(1, 'Band', 'band');
INSERT INTO artist_type(artistTypeId, type, slug) VALUES(2, 'Comedian', 'comedian');
INSERT INTO artist_type(artistTypeId, type, slug) VALUES(3, 'Busker', 'busker');



INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(1, 1, 'Limozeen', 'limozeen');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(2, 1, 'Taranchula', 'taranchula');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(3, 1, 'Bigg Nife', 'bigg-nife');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(4, 2, 'George Carlin', 'george-carlin');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(5, 2, 'David Cross', 'david-cross');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(6, 3, 'The Sonic Manipulator', 'the-sonic-manipulator');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(7, 1, 'Sloshy', 'sloshy');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(8, 1, 'Lords of the Underworld', 'lords-of-the-underworld');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(9, 1, 'Bad News', 'bad-news');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(10, 1, 'Stillwater', 'stillwater');
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(11, 1, 'Spinal Täp', 'spinal-tap');

/* this artist is meant to have the same name as another */ 
INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(12, 2, 'Bad News', 'bad-news-2');

INSERT INTO artist(artistId, artistTypeId, name, slug) VALUES(13, 1, 'Anvil', 'anvil');



INSERT INTO venue(venueId, name, slug, address, shortAddress, latitude, longitude) VALUES(1, 'Strong Badia', 'strong-badia', 'The field behind the dumpsters, Free-Country USA', 'Dumpster-field', '31.1234', '124.4444');



INSERT INTO event(eventId, name, sub_name, slug, dateStart, dateEnd, venueId) VALUES(1, 'AwexxomeFest', 'The Awexxomest Festival Ever', 'awexxome-fest', '1936-01-01', '1936-01-02', 1);
INSERT INTO event(eventId, name, sub_name, slug, dateStart, dateEnd, venueId) VALUES(2, 'AwexxomeFest 20X6', 'Curated by Awexxome McAwexxome', 'awexxome-fest-20x6', '2096-02-01', '2096-02-02', 1);

INSERT INTO planned_event(eventId, name, sub_name, slug, completeness, venueId) VALUES(1, 'AwexxomeFest 2025', 'Awexxome Future Times', 'awexxome-fest-2025', 20, 1);
INSERT INTO planned_event(eventId, name, sub_name, slug, completeness, venueId) VALUES(2, 'Something else', null, 'something-else', 0, 1);


/* don't order these by priority/sequence in this file */
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(1, 3, 2, 1);
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(1, 1, 1, 1);
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(1, 2, 1, 2);
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(2, 2, 1, 1);
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(2, 1, 1, 2);

/* don't change or remove the 2000 and 3000 values here: they are relied upon by some tests. */
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(1, 7, 2000, 3000);

INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(2, 6, 1, 1);

/* need a few artists of a different type against an event */
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(1, 4, 3, 1);
INSERT INTO event_artist(eventId, artistId, priority, sequence) VALUES(1, 5, 3, 2);

INSERT INTO ticket(eventId, name, cost) VALUES(1, "Fancy seats", 1000);
INSERT INTO ticket(eventId, name, cost) VALUES(1, "Nosebleeds", 100);
INSERT INTO ticket(eventId, name, cost) VALUES(1, "Car Park", 200);
INSERT INTO ticket(eventId, name, cost) VALUES(2, "The Toilet", 50);

