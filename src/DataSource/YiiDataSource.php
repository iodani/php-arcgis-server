<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\DataSource;

use RuntimeException;

/**
 * YiiDataSource
 *
 * Yii Framework 1.x adapter for ArcGIS Feature Server.
 * Uses Yii's CDbConnection and CDbCommand for database queries.
 * Optimized for PostGIS with Yii.
 *
 * Features:
 * - Multi-tenancy support (TenantFilterInterface)
 * - Extent calculation (ExtentCalculatorInterface)
 * - objectIds filtering (for popups)
 * - outFields with * and comma-separated fields
 *
 * Usage:
 * ```php
 * $dataSource = new YiiDataSource(Yii::app()->dbRead, [
 *     'schema' => 'public',
 *     'geometry_column' => 'geom',
 *     'srid' => 4326,
 * ]);
 * ```
 */
class YiiDataSource implements DataSourceInterface, ExtentCalculatorInterface
{
    private CDbConnection $db;
    private string $schema;
    private string $geometryColumn;
    private int $srid;

    /**
     * Create Yii data source
     *
     * @param CDbConnection|null $db Yii database connection (defaults to Yii::app()->db)
     * @param array $config Configuration
     */
    public function __construct(?CDbConnection $db = null, array $config = [])
    {
        $this->db = $db ?? \Yii::app()->db;
        $this->schema = $config['schema'] ?? 'public';
        $this->geometryColumn = $config['geometry_column'] ?? 'geom';
        $this->srid = $config['srid'] ?? 4326;
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $table, array $params = []): array
    {
        $format = $params['f'] ?? 'json';
        $geometryCol = $params['geometry_column'] ?? $this->geometryColumn;

        // Build SQL based on format
        $sql = $this->buildQuery($table, $geometryCol, $params, $format);

        try {
            $command = $this->db->createCommand($sql);

            return $command->queryAll();
        } catch (\Exception $e) {
            throw new RuntimeException("Query failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $table, array $params = []): int
    {
        $where = $this->buildWhereClause($params);

        // Support custom FROM clause for JOINs
        $fromClause = $params['fromClause'] ?? "FROM {$this->schema}.{$table} t";

        $sql = "SELECT COUNT(*) {$fromClause} WHERE {$where}";

        try {
            $command = $this->db->createCommand($sql);

            return (int) $command->queryScalar();
        } catch (\Exception $e) {
            throw new RuntimeException("Count failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            $command = $this->db->createCommand("SELECT PostGIS_Version()");
            $version = $command->queryScalar();

            return !empty($version);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Build query based on format
     *
     * @param string $table Table name
     * @param string $geometryCol Geometry column name
     * @param array $params Query parameters
     * @param string $format Output format (json or geojson)
     * @return string SQL query
     */
    private function buildQuery(
        string $table,
        string $geometryCol,
        array  $params,
        string $format
    ): string {
        $outFields = $this->buildFieldList($params);
        $where = $this->buildWhereClause($params);
        $orderBy = $this->buildOrderBy($params);
        $limit = $this->buildLimit($params);

        $outSR = $params['outSR'] ?? $this->srid;

        // Support custom FROM clause for complex JOINs
        $fromClause = $params['fromClause'] ?? "FROM {$this->schema}.{$table} t";

        if ($format === 'geojson') {
            // Use ST_AsGeoJSON for GeoJSON format
            $sql = "
                SELECT
                    {$outFields},
                    ST_AsGeoJSON(ST_Transform({$geometryCol}, {$outSR}))::json AS geojson_geometry
                {$fromClause}
                WHERE {$where}
                {$orderBy}
                {$limit}
            ";
        } else {
            // Use ST_X/ST_Y for JSON format (points)
            // Ensure geometry column has table alias if not already present
            $geomExpr = str_contains($geometryCol, '.')
                ? $geometryCol
                : "t.{$geometryCol}";

            $sql = "
                SELECT
                    {$outFields},
                    ST_X(ST_Transform({$geomExpr}, {$outSR})) AS x,
                    ST_Y(ST_Transform({$geomExpr}, {$outSR})) AS y
                {$fromClause}
                WHERE {$where}
                {$orderBy}
                {$limit}
            ";
        }

        return $sql;
    }

    /**
     * Build field list
     *
     * @param array $params Query parameters
     * @return string Field list
     */
    private function buildFieldList(array $params): string
    {
        // Support custom field mapping
        if (isset($params['fieldMap']) && is_array($params['fieldMap'])) {
            $requestedFields = isset($params['outFields']) && $params['outFields'] !== '*'
                ? array_map('trim', explode(',', $params['outFields']))
                : array_keys($params['fieldMap']);

            $mappedFields = [];
            $hasObjectId = false;

            // Always include objectid first if it exists in fieldMap
            if (isset($params['fieldMap']['objectid']) && in_array('objectid', $requestedFields)) {
                $mappedFields[] = $params['fieldMap']['objectid'];
                $hasObjectId = true;
            }

            // Add other requested fields
            foreach ($requestedFields as $field) {
                // Skip objectid if already added
                if ($field === 'objectid' && $hasObjectId) {
                    continue;
                }

                if (isset($params['fieldMap'][$field])) {
                    $mappedFields[] = $params['fieldMap'][$field];
                }
            }

            // If objectid was requested but not in fieldMap, ensure we have the ID field
            if (!$hasObjectId && in_array('objectid', $requestedFields)) {
                // Try to find the ID field (usually 'id')
                if (isset($params['fieldMap']['id'])) {
                    array_unshift($mappedFields, $params['fieldMap']['id'] . ' AS objectid');
                }
            }

            return implode(', ', $mappedFields);
        }

        // Default behavior
        if (!isset($params['outFields']) || $params['outFields'] === '*') {
            return 't.*';
        }

        $fields = explode(',', $params['outFields']);
        $fields = array_map('trim', $fields);
        $fields = array_map(function ($field) {
            return 't.' . $field;
        }, $fields);

        return implode(', ', $fields);
    }

    /**
     * Build WHERE clause
     *
     * Supports objectIds, base conditions, tenant filtering, and geometry filter
     *
     * @param array $params Query parameters
     * @return string WHERE clause
     */
    private function buildWhereClause(array $params): string
    {
        $conditions = [];

        // 1. Base WHERE conditions (geometry, status, etc.)
        // These are the base filters from the layer (geom IS NOT NULL, status_code, etc.)
        if (isset($params['baseWhere']) && !empty($params['baseWhere'])) {
            $conditions[] = "({$params['baseWhere']})";
        }

        // 2. Tenant filtering (additional filter that complements baseWhere)
        // tenantWhere is ADDED to baseWhere, not a replacement
        if (isset($params['tenantWhere']) && !empty($params['tenantWhere'])) {
            $conditions[] = "({$params['tenantWhere']})";
        }

        // 3. Standard WHERE clause (user-provided)
        if (isset($params['where']) && !empty($params['where'])) {
            $conditions[] = "({$params['where']})";
        }

        // 4. objectIds filter (for popups when clicking on map)
        // ArcGIS sends objectIds as comma-separated string: "1,2,3,4,5"
        // IMPORTANT: objectIds refer to the actual database ID (t.id), not the alias 'objectid'
        if (isset($params['objectIds']) && !empty($params['objectIds'])) {
            // objectIds can be string "1,2,3" or array [1,2,3]
            $objectIds = is_array($params['objectIds'])
                ? $params['objectIds']
                : array_map('trim', explode(',', $params['objectIds']));

            // Sanitize and convert to integers
            $escapedIds = array_map('intval', array_filter($objectIds, 'is_numeric'));

            if (!empty($escapedIds)) {
                // Use t.id (the actual database column) not the alias 'objectid'
                $conditions[] = "t.id IN (" . implode(',', $escapedIds) . ")";
            }
        }

        // 5. Geometry filter (bbox)
        if (isset($params['geometry']) && isset($params['geometryType'])) {
            $geometryCol = $params['geometry_column'] ?? $this->geometryColumn;

            if ($params['geometryType'] === 'esriGeometryEnvelope') {
                // Bounding box query
                $bbox = json_decode($params['geometry']);
                $inSR = $params['inSR'] ?? $this->srid;

                $conditions[] = "ST_Intersects(
                    {$geometryCol},
                    ST_Transform(
                        ST_MakeEnvelope({$bbox->xmin}, {$bbox->ymin}, {$bbox->xmax},{$bbox->ymax}, {$inSR}),
                        ST_SRID({$geometryCol})
                    )
                )";
            }
        }

        return !empty($conditions) ? implode(' AND ', $conditions) : '1=1';
    }

    /**
     * Build ORDER BY clause
     *
     * @param array $params Query parameters
     * @return string ORDER BY clause
     */
    private function buildOrderBy(array $params): string
    {
        if (!isset($params['orderByFields']) || empty($params['orderByFields'])) {
            return '';
        }

        return 'ORDER BY ' . $params['orderByFields'];
    }

    /**
     * Build LIMIT/OFFSET clause
     *
     * @param array $params Query parameters
     * @return string LIMIT clause
     */
    private function buildLimit(array $params): string
    {
        $limit = $params['resultRecordCount'] ?? 2000;
        $offset = $params['resultOffset'] ?? 0;

        return "LIMIT {$limit} OFFSET {$offset}";
    }

    /**
     * Get the Yii database connection
     *
     * @return mixed CDbConnection instance
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Calculate extent (bounding box) for a layer
     *
     * {@inheritdoc}
     */
    public function calculateExtent(string $table, string $geometryColumn, array $params = []): ?array
    {
        $fromClause = $params['fromClause'] ?? "FROM {$this->schema}.{$table} t";
        $where = $this->buildWhereClause($params);

        // Use PostGIS ST_Extent to get bounding box
        // ST_Extent returns BOX format, we extract xmin, ymin, xmax, ymax
        $sql = "
            SELECT
                ST_XMin(extent) AS xmin,
                ST_YMin(extent) AS ymin,
                ST_XMax(extent) AS xmax,
                ST_YMax(extent) AS ymax
            FROM (
                SELECT ST_Extent(ST_Transform({$geometryColumn}, {$this->srid})) AS extent
                {$fromClause}
                WHERE {$where}
            ) AS extent_query
        ";

        try {
            $command = $this->db->createCommand($sql);
            $result = $command->queryRow();

            // If no data or null extent, return null
            if (!$result || $result['xmin'] === null) {
                return null;
            }

            return [
                'xmin' => (float) $result['xmin'],
                'ymin' => (float) $result['ymin'],
                'xmax' => (float) $result['xmax'],
                'ymax' => (float) $result['ymax'],
                'spatialReference' => [
                    'wkid' => $this->srid,
                    'latestWkid' => $this->srid,
                ],
            ];
        } catch (\Exception $e) {
            // Log error but don't fail - extent is optional
            Yii::log("Extent calculation failed: " . $e->getMessage(), CLogger::LEVEL_WARNING, 'arcgis_server');

            return null;
        }
    }
}
