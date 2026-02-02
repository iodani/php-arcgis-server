<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Core;

use Iodani\ArcGIS\Server\Contracts\DataSourceInterface;
use RuntimeException;

/**
 * FeatureServer
 *
 * Main ArcGIS Feature Server implementation.
 * Manages layers and routes requests.
 */
class FeatureServer
{
    private DataSourceInterface $dataSource;
    private array $layers = [];
    private array $config;

    /**
     * Create feature server
     *
     * @param DataSourceInterface $dataSource Data source
     * @param array $config Server configuration
     */
    public function __construct(DataSourceInterface $dataSource, array $config = [])
    {
        $this->dataSource = $dataSource;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Register a layer
     *
     * @param string $className Layer class name
     * @return self For method chaining
     */
    public function registerLayer(string $className): self
    {
        if (!class_exists($className)) {
            throw new RuntimeException("Layer class not found: {$className}");
        }

        $layer = new $className($this->dataSource);

        if (!($layer instanceof FeatureLayer)) {
            throw new RuntimeException("Layer must extend FeatureLayer: {$className}");
        }

        $this->layers[$layer->getId()] = $layer;

        return $this;
    }

    /**
     * Get layer by ID
     *
     * @param int $layerId Layer ID
     * @return FeatureLayer Layer instance
     * @throws RuntimeException If layer not found
     */
    public function getLayer(int $layerId): FeatureLayer
    {
        if (!isset($this->layers[$layerId])) {
            throw new RuntimeException("Layer not found: {$layerId}");
        }

        return $this->layers[$layerId];
    }

    /**
     * Get service info
     *
     * Returns service-level metadata and list of layers.
     * This is the response for: GET /FeatureServer
     *
     * @return array Service info
     */
    public function getServiceInfo(): array
    {
        $layers = [];

        foreach ($this->layers as $layer) {
            $layers[] = [
                'id' => $layer->getId(),
                'name' => $layer->getName(),
                'type' => 'Feature Layer',
            ];
        }

        return [
            'currentVersion' => 10.9,
            'serviceDescription' => $this->config['description'],
            'hasVersionedData' => false,
            'supportsDisconnectedEditing' => false,
            'hasStaticData' => false,
            'maxRecordCount' => $this->config['maxRecordCount'],
            'supportedQueryFormats' => 'JSON, geoJSON',
            'capabilities' => 'Query',
            'description' => $this->config['description'],
            'copyrightText' => $this->config['copyrightText'] ?? '',
            'spatialReference' => [
                'wkid' => 4326,
                'latestWkid' => 4326,
            ],
            'initialExtent' => $this->config['initialExtent'] ?? null,
            'fullExtent' => $this->config['fullExtent'] ?? null,
            'allowGeometryUpdates' => false,
            'units' => 'esriDecimalDegrees',
            'layers' => $layers,
            'tables' => [],
        ];
    }

    /**
     * Get all registered layers
     *
     * @return array Array of layers
     */
    public function getLayers(): array
    {
        return array_values($this->layers);
    }

    /**
     * Get default configuration
     *
     * @return array Default config
     */
    private function getDefaultConfig(): array
    {
        return [
            'description' => 'ArcGIS Feature Server',
            'maxRecordCount' => 2000,
            'copyrightText' => '',
        ];
    }
}
