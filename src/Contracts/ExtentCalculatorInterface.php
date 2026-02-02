<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Contracts;

/**
 * ExtentCalculatorInterface
 *
 * Interface for calculating layer extent (bounding box) from data.
 *
 * Data sources that support PostGIS should implement this interface
 * to provide automatic extent calculation for layers.
 */
interface ExtentCalculatorInterface
{
    /**
     * Calculate extent (bounding box) for a layer
     *
     * Uses PostGIS ST_Extent to calculate the bounding box of all features
     * matching the given parameters (tenant filter, where clause, etc.).
     *
     * @param string $table Table name
     * @param string $geometryColumn Geometry column name
     * @param array $params Query parameters (for tenant filtering, where clause, etc.)
     * @return array|null Extent array or null if no data
     *
     * Return format:
     * ```php
     * [
     *     'xmin' => -118.5,
     *     'ymin' => 34.0,
     *     'xmax' => -118.2,
     *     'ymax' => 34.3,
     *     'spatialReference' => [
     *         'wkid' => 4326,
     *         'latestWkid' => 4326
     *     ]
     * ]
     * ```
     */
    public function calculateExtent(string $table, string $geometryColumn, array $params = []): ?array;
}
