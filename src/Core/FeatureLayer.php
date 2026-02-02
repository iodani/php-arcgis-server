<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Core;

use Iodani\ArcGIS\Server\Contracts\DataSourceInterface;
use Iodani\ArcGIS\Server\Contracts\ExtentCalculatorInterface;
use Iodani\ArcGIS\Server\Contracts\FeatureLayerInterface;
use Iodani\ArcGIS\Server\Contracts\TenantFilterInterface;
use Iodani\ArcGIS\Server\Response\ArcGISResponseBuilder;

/**
 * FeatureLayer
 *
 * Base class for ArcGIS Feature Layers.
 * Child classes define layer configuration and data source.
 */
abstract class FeatureLayer implements FeatureLayerInterface
{
    protected DataSourceInterface $dataSource;
    protected ArcGISResponseBuilder $responseBuilder;
    private ?array $layerConfig = null;

    /**
     * Create feature layer
     *
     * @param DataSourceInterface $dataSource Data source
     */
    public function __construct(DataSourceInterface $dataSource)
    {
        $this->dataSource = $dataSource;
        $this->responseBuilder = new ArcGISResponseBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return $this->getConfig()['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->getConfig()['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): array
    {
        $config = $this->getConfig();

        // Auto-calculate extent if not provided and data source supports it
        if (!isset($config['extent']) && $this->dataSource instanceof ExtentCalculatorInterface) {
            $params = $this->buildDataSourceParams([]);
            $extent = $this->dataSource->calculateExtent(
                $this->getTableName(),
                $config['geometryColumn'] ?? 'geom',
                $params
            );

            if ($extent) {
                $config['extent'] = $extent;
            }
        }

        return $this->responseBuilder->buildLayerDefinition($config);
    }

    /**
     * Get layer extent (bounding box)
     *
     * Calculate extent from data if not defined in config.
     * Useful for dynamic extent calculation.
     *
     * @return array|null Extent or null if unavailable
     */
    public function getExtent(): ?array
    {
        $config = $this->getConfig();

        // Return configured extent if available
        if (isset($config['extent'])) {
            return $config['extent'];
        }

        // Calculate extent if data source supports it
        if ($this->dataSource instanceof ExtentCalculatorInterface) {
            $params = $this->buildDataSourceParams([]);

            return $this->dataSource->calculateExtent(
                $this->getTableName(),
                $config['geometryColumn'] ?? 'geom',
                $params
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $params = []): array
    {
        $config = $this->getConfig();
        $maxRecordCount = $config['maxRecordCount'] ?? 2000;

        // Handle returnCountOnly parameter (returns only count, no features)
        if (!empty($params['returnCountOnly'])) {
            $params = $this->buildDataSourceParams($params);
            $count = $this->dataSource->count($this->getTableName(), $params);

            return $this->responseBuilder->buildCountResponse($count);
        }

        // Ensure we don't exceed max
        if (!isset($params['resultRecordCount'])) {
            $params['resultRecordCount'] = $maxRecordCount;
        } else {
            $params['resultRecordCount'] = min((int)$params['resultRecordCount'], $maxRecordCount);
        }

        // Add common params needed by data source
        $params = $this->buildDataSourceParams($params);

        // Execute query
        $features = $this->dataSource->query($this->getTableName(), $params);

        // Build response based on format
        $format = $params['f'] ?? 'json';

        if ($format === 'geojson') {
            return $this->responseBuilder->buildGeoJsonResponse(
                $features,
                $maxRecordCount
            );
        }

        return $this->responseBuilder->buildJsonResponse(
            $features,
            $config,
            $params,
            $maxRecordCount
        );
    }

    /**
     * Count features matching query
     *
     * @param array $params Query parameters
     * @return int Feature count
     */
    public function count(array $params = []): int
    {
        // Add common params needed by data source
        $params = $this->buildDataSourceParams($params);

        return $this->dataSource->count($this->getTableName(), $params);
    }

    /**
     * Build common parameters for data source queries
     *
     * This ensures fromClause, fieldMap, tenant filter, etc. are always passed.
     *
     * @param array $params Original params
     * @return array Enhanced params
     */
    private function buildDataSourceParams(array $params): array
    {
        $config = $this->getConfig();

        // Add geometry column to params
        $params['geometry_column'] = $config['geometryColumn'] ?? 'geom';

        // Add objectId field name
        $params['objectIdField'] = $config['objectIdField'] ?? 'id';

        // Add custom FROM clause if provided by layer
        if (method_exists($this, 'getFromClause')) {
            $params['fromClause'] = $this->getFromClause();
        }

        // Add field mapping if provided by layer
        if (method_exists($this, 'getFieldMap')) {
            $params['fieldMap'] = $this->getFieldMap();
        }

        // Add base WHERE conditions if layer provides them
        if (method_exists($this, 'getBaseWhereClause')) {
            $params['baseWhere'] = $this->getBaseWhereClause();
        }

        // Add tenant filtering if layer implements TenantFilterInterface
        // This is ADDED to baseWhere, not a replacement
        if ($this instanceof TenantFilterInterface && $this->isTenantFilterEnabled()) {
            $params['tenantWhere'] = $this->getTenantWhereClause();
        }

        return $params;
    }

    /**
     * Get layer configuration
     *
     * @return array Layer config
     */
    protected function getConfig(): array
    {
        if ($this->layerConfig === null) {
            $this->layerConfig = $this->getLayerConfig();
        }

        return $this->layerConfig;
    }

    /**
     * Get database connection from data source
     *
     * Helper method to access the underlying database connection.
     * Use this instead of Yii::app()->dbRead for better testability.
     *
     * @return CDbConnection Database connection
     */
    protected function getDb(): CDbConnection
    {
        return $this->dataSource->getDb();
    }

    /**
     * Quote a value for safe SQL usage
     *
     * Helper method for quoting values in SQL queries.
     * Use this instead of Yii::app()->dbRead->quoteValue() for better testability.
     *
     * @param mixed $value Value to quote
     * @return string Quoted value
     */
    protected function quoteValue($value): string
    {
        return $this->getDb()->quoteValue($value);
    }

    /**
     * Get table name for queries
     *
     * Child classes must implement this.
     *
     * @return string Table name
     */
    abstract protected function getTableName(): string;

    /**
     * Get layer configuration
     *
     * Child classes must implement this to define:
     * - id: Layer ID (required)
     * - name: Layer name (required)
     * - geometryType: Geometry type (required)
     * - fields: Array of field definitions (required)
     * - geometryColumn: Geometry column name (optional, default: 'geom')
     * - objectIdField: Object ID field name (optional, default: 'OBJECTID')
     * - displayField: Display field name (optional)
     * - description: Layer description (optional)
     * - drawingInfo: Renderer configuration (optional)
     * - extent: Layer extent (optional)
     * - maxRecordCount: Max records per query (optional, default: 2000)
     *
     * @return array Layer configuration
     */
    abstract protected function getLayerConfig(): array;
}
