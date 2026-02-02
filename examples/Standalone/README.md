# Standalone Examples

These examples show how to use PHP ArcGIS Server without any framework.

## Requirements

- PHP 8.0+
- PostGIS/PostgreSQL database
- Composer

## Setup

1. Install dependencies:
```bash
composer require iodani/php-arcgis-server
```

2. Create database and tables (see SQL below)

3. Update connection details in examples

4. Run with PHP built-in server:
```bash
php -S localhost:8000 basic-usage.php
```

5. Access in browser or ArcGIS JS SDK:
```
http://localhost:8000/FeatureServer
http://localhost:8000/FeatureServer/0
http://localhost:8000/FeatureServer/0/query?where=1=1&f=json
```

## Database Setup
```sql
-- Simple points table
CREATE TABLE points (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    geom GEOMETRY(POINT, 4326)
);

-- Insert sample data
INSERT INTO points (name, description, geom) VALUES
('Point 1', 'First point', ST_SetSRID(ST_MakePoint(-122.4194, 37.7749), 4326)),
('Point 2', 'Second point', ST_SetSRID(ST_MakePoint(-118.2437, 34.0522), 4326));

-- Buildings table with multi-tenancy
CREATE TABLE buildings (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200),
    address VARCHAR(255),
    tenant_id INTEGER,
    status VARCHAR(50),
    geom GEOMETRY(POINT, 4326)
);
```

## Examples

- `basic-usage.php` - Minimal setup with one layer
- `custom-layer.php` - Custom layer with multi-tenancy