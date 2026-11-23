# Map tiles

The dashboard map is Leaflet, drawn by `UiMap` in `vue-ui`. Hanab supplies the office markers and the counts. The tile provider lives on `OPENSTREETMAP_TILES` in `vue-ui`.

## Provider

OpenStreetMap's public standard layer:

`https://tile.openstreetmap.org/{z}/{x}/{y}.png`

The map shows the required copyright line: © OpenStreetMap contributors, linking to https://www.openstreetmap.org/copyright. The data licence is ODbL. The tile images are served by the OpenStreetMap Foundation under their tile usage policy: https://operations.osmfoundation.org/policies/tiles/

That service is free for light use. It is not an unlimited production host, and access can be withdrawn. A website must:

- use the HTTPS URL above
- keep the attribution visible
- send a valid HTTP Referer
- let the browser cache tiles (the server sends a cache lifetime of about one day)
- avoid prefetching, bulk download, and `Cache-Control: no-cache`

A missing Referer does not return an HTTP error. The server responds `200` with a small PNG and the header `x-blocked: Access denied`. The map then looks broken even though the markers are still there. `UiMap` sets each tile image to `strict-origin-when-cross-origin`, so the page origin is sent even when the rest of the page uses a stricter policy.

The public server's highest zoom is 19. Retina mode is off, because it would request four tiles in place of one. Extra tiles around the viewport are limited to one ring, and tiles are requested after the map has finished moving to the offices.

## Changing the provider

Pass a `tiles` object to `UiMap` with `url`, `attribution`, `maxZoom`, and Leaflet `options`. Leave that object out and the OpenStreetMap layer is used. Do not point the map at another public OpenStreetMap tile server unless that server's own terms allow Hanab's traffic.
