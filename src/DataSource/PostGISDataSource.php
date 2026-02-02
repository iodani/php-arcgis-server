<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\DataSource;

use Iodani\ArcGIS\Server\Contracts\DataSourceInterface;
use PDO;
use RuntimeException;

/**
 * PostGISDataSource
 *
 * Optimized PostGIS adapter for ArcGIS Feature Server.
 * Leverages PostGIS spatial functions for maximum performance.
 */
class PostGISDataSource implements DataSourceInterface
{
    private PDO $pdo;
    private string $schema;
    private string $geometryColumn;
    private int $srid;

    /**
     * Create PostGIS data source
     *
     * @param PDO $pdo PostgreSQL/PostGIS connection
     * @param array $config Configuration
     */
    public function __construct(PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->schema = $config['schema'] ?? 'public';
        $this->geometryColumn = $config['geometry_column'] ?? 'geom';
        $this->srid = $config['srid'] ?? 4326;

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Get database connection
     *
     * Returns the underlying PDO connection for direct access
     * (e.g., for quoting values, transactions, etc.)
     *
     * @return PDO Database connection
     */
    public function getDb(): PDO
    {
        return $this->pdo;
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
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new RuntimeException("Query failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $table, array $params = []): int
    {
        $where = $this->buildWhereClause($params);

        $sql = "SELECT COUNT(*) FROM {$this->schema}.{$table} WHERE {$where}";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new RuntimeException("Count failed: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            $stmt = $this->pdo->query("SELECT PostGIS_Version()");

            if ($stmt === false) {
                return false;
            }

            $version = $stmt->fetchColumn();
            return !empty($version);
        } catch (\PDOException $e) {
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
        array $params,
        string $format
    ): string {
        $outFields = $this->buildFieldList($params);
        $where = $this->buildWhereClause($params);
        $orderBy = $this->buildOrderBy($params);
        $limit = $this->buildLimit($params);

        $outSR = $params['outSR'] ?? $this->srid;

        if ($format === 'geojson') {
            // Use ST_AsGeoJSON for GeoJSON format
            $sql = "
                SELECT 
                    {$outFields},
                    ST_AsGeoJSON(ST_Transform({$geometryCol}, {$outSR}))::json AS geojson_geometry
                FROM {$this->schema}.{$table}
                WHERE {$where}
                {$orderBy}
                {$limit}
            ";
        } else {
            // Use ST_X/ST_Y for JSON format (points)
            // For other geometries, would need different handling
            $sql = "
                SELECT 
                    {$outFields},
                    ST_X(ST_Transform({$geometryCol}, {$outSR})) AS x,
                    ST_Y(ST_Transform({$geometryCol}, {$outSR})) AS y
                FROM {$this->schema}.{$table}
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
     * @param array $params Query parameters
     * @return string WHERE clause
     */
    private function buildWhereClause(array $params): string
    {
        $conditions = [];

        // Standard WHERE
        if (isset($params['where']) && !empty($params['where'])) {
            $conditions[] = "({$params['where']})";
        }

        // Geometry filter (bbox)
        if (isset($params['geometry']) && isset($params['geometryType'])) {
            $geometryCol = $params['geometry_column'] ?? $this->geometryColumn;

            if ($params['geometryType'] === 'esriGeometryEnvelope') {
                // Bounding box query
                $bbox = $params['geometry'];
                $inSR = $params['inSR'] ?? $this->srid;

                $conditions[] = "ST_Intersects(
                    {$geometryCol}, 
                    ST_Transform(
                        ST_MakeEnvelope({$bbox}, {$inSR}),
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
}
