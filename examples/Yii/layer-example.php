<?php

/**
 * Custom Layer Example for Yii 1.x
 *
 * Place this file in: protected/components/arcgis_server/layers/PlaceLayer.php
 */

use Iodani\ArcGIS\Server\Constants\FieldType;
use Iodani\ArcGIS\Server\Constants\GeometryType;
use Iodani\ArcGIS\Server\Contracts\TenantFilterInterface;
use Iodani\ArcGIS\Server\Core\FeatureLayer;

class PlaceLayer extends FeatureLayer implements TenantFilterInterface
{
    protected function getTableName(): string
    {
        return 'place';
    }

    protected function getLayerConfig(): array
    {
        return [
            'id' => 1,
            'name' => 'Places',
            'description' => 'Place and Address System',
            'geometryType' => GeometryType::POINT,
            'geometryColumn' => 't.geom',
            'objectIdField' => 'objectid',
            'displayField' => 'address1',
            'maxRecordCount' => 2000,

            'spatialReference' => [
                'wkid' => 4326,
                'latestWkid' => 4326,
            ],

            'fields' => [
                ['name' => 'objectid', 'type' => FieldType::OID, 'alias' => 'OBJECTID'],
                ['name' => 'id', 'type' => FieldType::INTEGER, 'alias' => 'ID'],
                ['name' => 'address1', 'type' => FieldType::STRING, 'alias' => 'Address 1', 'length' => 100],
                ['name' => 'address2', 'type' => FieldType::STRING, 'alias' => 'Address 2', 'length' => 100],
                ['name' => 'latitude', 'type' => FieldType::DOUBLE, 'alias' => 'Latitude'],
                ['name' => 'longitude', 'type' => FieldType::DOUBLE, 'alias' => 'Longitude'],
                ['name' => 'client_code', 'type' => FieldType::STRING, 'alias' => 'Client Code', 'length' => 28],
            ],
        ];
    }

    /**
     * Optional: Define custom FROM clause with JOINs
     */
    protected function getFromClause(): string
    {
        return "
            FROM
                place t
                LEFT JOIN occupancy o ON (t.id = o.address_id)
        ";
    }

    /**
     * Optional: Define field mapping for complex queries
     */
    protected function getFieldMap(): array
    {
        return [
            'objectid' => 't.id AS objectid',
            'id' => 't.id',
            'address1' => 't.address1',
            'address2' => 't.address2',
            'latitude' => 't.latitude',
            'longitude' => 't.longitude',
            'client_code' => 't.client_code',
            'has_occupancy' => 'CASE WHEN o.id IS NOT NULL THEN 1 ELSE 0 END AS has_occupancy',
        ];
    }

    /**
     * Multi-tenancy: Filter by client code
     */
    public function getTenantWhereClause(): string
    {
        $userId = Yii::app()->user->id;
        $userClient = (new UserParam)->getUserClient($userId);
        $clientCode = $userClient->client_code;

        return 't.client_code = ' . $this->quoteValue($clientCode);
    }

    /**
     * Multi-tenancy: Disable for admin users
     */
    public function isTenantFilterEnabled(): bool
    {
        return !Yii::app()->user->checkAccess(UserRole::ADMIN);
    }
}