<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Examples;

use Iodani\ArcGIS\Server\Constants\FieldType;
use Iodani\ArcGIS\Server\Constants\GeometryType;
use Iodani\ArcGIS\Server\Core\FeatureLayer;

/**
 * SimplePointLayer
 *
 * Basic example layer showing minimum required implementation.
 *
 * Database table structure:
 * CREATE TABLE points (
 *     id SERIAL PRIMARY KEY,
 *     name VARCHAR(100),
 *     description TEXT,
 *     geom GEOMETRY(POINT, 4326)
 * );
 */
class SimplePointLayer extends FeatureLayer
{
    protected function getTableName(): string
    {
        return 'points';
    }

    protected function getLayerConfig(): array
    {
        return [
            'id' => 0,
            'name' => 'Points',
            'description' => 'Simple Point Layer Example',
            'geometryType' => GeometryType::POINT,
            'geometryColumn' => 'geom',
            'objectIdField' => 'id',
            'displayField' => 'name',
            'maxRecordCount' => 1000,

            'fields' => [
                ['name' => 'id', 'type' => FieldType::OID, 'alias' => 'ID'],
                ['name' => 'name', 'type' => FieldType::STRING, 'alias' => 'Name', 'length' => 100],
                ['name' => 'description', 'type' => FieldType::STRING, 'alias' => 'Description', 'length' => 255],
            ],

            'spatialReference' => [
                'wkid' => 4326,
                'latestWkid' => 4326,
            ],
        ];
    }
}
