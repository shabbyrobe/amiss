Introduction
============

About the examples
------------------

Most of the examples contained herein will make use of the schema and model that accompanies the documentation in the ``doc/demo`` subdirectory of the source distribution. It contains a very simple set of related objects representing a set of events for a music festival.

- Artist: an artist playing at the festival

  - 1-n ArtistType: exactly what it says on the tin.

- Event: an event that is part of the festival

  - n-n EventArtist: The artists that are playing at the event
  - 1-n Venue: The venue the event is happening at

EventArtist contains two columns for ordering - priority and sequence - for controlling the way the event's "billing" is represented. Think of an event poster: some artists names are bigger than others (priority), then the artists of the same size are also ordered from first to last (sequence). So, for example, Neil Young and Fleetwood Mac could be headlining an event. They'll obviously both be the highest priority, but they would presumably still have a bit of a fight behind the scenes with everybody's manager about whose name appears first at that same size.

There is also an example directory under the ``example`` folder in the source distribution that will allow you to click through some examples that are built on this schema. Eventually, the docs will hopefully import the contents of those examples but I'm just learning ReST now and it's still kind of in the "too hard" basket.
