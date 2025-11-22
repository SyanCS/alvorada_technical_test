# Why PostgreSQL/PostGIS for Geospatial Data

## Context
The project manages property locations and notes. Although the initial requirement mentioned MySQL, PostgreSQL with the PostGIS extension was selected to provide more reliable and feature-rich geospatial support.

## Key reasons for choosing PostgreSQL/PostGIS
1. **Native geospatial type coverage**: PostGIS adds `GEOGRAPHY` and `GEOMETRY` types with SRID support (e.g., WGS84/4326) so coordinates are stored with clear spatial semantics instead of generic numeric columns.
2. **Rich spatial function library**: PostGIS ships hundreds of battle-tested spatial functions (`ST_Distance`, `ST_Contains`, `ST_Buffer`, `ST_Transform`, `ST_AsGeoJSON`, etc.), enabling distance queries, containment checks, projections, and GeoJSON output without custom math.
3. **Accurate spherical calculations**: `GEOGRAPHY` handles spheroidal distance/area calculations out of the box, avoiding the approximation errors of manual latitude/longitude math in SQL or PHP.
4. **Indexing and performance**: GIST/SP-GIST indexes on spatial columns accelerate proximity and containment queries (e.g., “find properties within X meters of a point”), which is critical as location data grows.
5. **Standards compliance and ecosystem**: PostGIS follows OGC standards and integrates with popular GIS tooling (QGIS, GDAL/OGR), making interoperability with mapping pipelines straightforward.
6. **Operational maturity**: PostgreSQL offers robust transactions, extensions, and query planning. PostGIS is widely deployed in production for geo-heavy workloads, reducing risk compared to MySQL’s more limited GIS feature set.

## Contrast with MySQL
- MySQL’s spatial support is primarily planar (`GEOMETRY`) and lacks native spheroidal calculations, requiring custom code for accurate earth-distance queries.
- Geo functions and index behavior vary across MySQL versions and storage engines, which can introduce portability issues.
- PostGIS provides richer GeoJSON and CRS handling, while MySQL often requires additional application-side processing for the same capabilities.

## Impact on the codebase
- The schema uses a `location` column of type `GEOGRAPHY(Point, 4326)` instead of separate `latitude`/`longitude` fields, simplifying spatial indexes and queries.
- Application code can rely on PostGIS functions for distance and formatting instead of bespoke calculations, keeping the PHP services focused on business logic.

## If MySQL is mandatory
If a MySQL backend is unavoidable, the project can be adapted by:
- Storing `latitude` and `longitude` as `DECIMAL` columns (or MySQL `POINT`) and replacing PostGIS calls with MySQL-compatible queries.
- Dropping PostGIS-specific migrations and updating repository queries to use MySQL’s available spatial operators.
- Adjusting Docker configuration to provision a MySQL service and revising connection settings in `AppConfig`.
