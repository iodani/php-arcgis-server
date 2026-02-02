<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Contracts;

/**
 * DataSourceInterface
 *
 * Contract for data source adapters (PostGIS, MySQL, REST API, etc.)
 */
interface DataSourceInterface
{
    /**
     * Execute query and return raw features
     *
     * @param string $table Table/collection name
     * @param array $params Query parameters
     * @return array Array of features with geometry
     */
    public function query(string $table, array $params = []): array;

    /**
     * Get total count of features matching query
     *
     * @param string $table Table/collection name
     * @param array $params Query parameters
     * @return int Total count
     */
    public function count(string $table, array $params = []): int;

    /**
     * Check if data source is available
     *
     * @return bool True if available
     */
    public function isAvailable(): bool;
}
