<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Response;

/**
 * ArcGISResponseBuilder
 *
 * Builds ArcGIS REST API compliant responses for both JSON and GeoJSON formats.
 * Based on real ArcGIS Server responses.
 */
class ArcGISResponseBuilder
{
    /**
     * Build query response for f=json format
     *
     * @param array $features Raw features from data source
     * @param array $layerDefinition Layer definition (fields, geometryType, etc.)
     * @param array $params Query parameters
     * @param int $maxRecordCount Maximum records per response
     * @return array ArcGIS JSON response
     */
    public function buildJsonResponse(
        array $features,
        array $layerDefinition,
        array $params,
        int   $maxRecordCount
    ): array {
        // Get output spatial reference from params (for popup queries with outSR)
        $outSR = isset($params['outSR'])
            ? (int) $params['outSR']
            : null;
        $spatialReference = $outSR
            ? ['wkid' => $outSR, 'latestWkid' => $outSR]
            : ($layerDefinition['spatialReference'] ?? ['wkid' => 4326, 'latestWkid' => 4326]);

        // Filter fields based on outFields parameter
        $requestedFields = $this->parseRequestedFields($params['outFields'] ?? '*');
        $responseFields = $this->filterFields($layerDefinition['fields'], $requestedFields);

        $response = [
            'objectIdFieldName' => $layerDefinition['objectIdField'] ?? 'objectid',
            'globalIdFieldName' => $layerDefinition['globalIdField'] ?? '',
            'geometryType' => $layerDefinition['geometryType'],
            'spatialReference' => $spatialReference,
            'fields' => $responseFields,
            'features' => [],
        ];

        // Process features
        foreach ($features as $feature) {
            $response['features'][] = $this->formatFeatureAsJson($feature, $requestedFields);
        }

        // Add exceededTransferLimit only if true
        if (count($features) >= $maxRecordCount) {
            $response['exceededTransferLimit'] = true;
        }

        return $response;
    }

    /**
     * Build query response for f=geojson format
     *
     * @param array $features Raw features from data source
     * @param int $maxRecordCount Maximum records per response
     * @return array GeoJSON response with ArcGIS extensions
     */
    public function buildGeoJsonResponse(
        array $features,
        int   $maxRecordCount
    ): array {
        $response = [
            'type' => 'FeatureCollection',
            'features' => [],
        ];

        // Process features
        foreach ($features as $feature) {
            $response['features'][] = $this->formatFeatureAsGeoJson($feature);
        }

        // Add ArcGIS extensions for pagination
        $exceeded = count($features) >= $maxRecordCount;
        $response['exceededTransferLimit'] = $exceeded;
        $response['properties'] = [
            'exceededTransferLimit' => $exceeded,
        ];

        return $response;
    }

    /**
     * Format single feature for JSON response
     *
     * @param array $feature Raw feature from database
     * @param array $requestedFields Requested field names (for filtering attributes)
     * @return array Formatted feature
     */
    private function formatFeatureAsJson(array $feature, array $requestedFields = ['*']): array
    {
        $formatted = [
            'attributes' => [],
            'geometry' => null,
        ];

        $returnAllFields = in_array('*', $requestedFields);

        // Separate geometry from attributes
        foreach ($feature as $key => $value) {
            if ($key === 'x' && isset($feature['y'])) {
                // Point geometry - extract x, y coordinates
                $formatted['geometry'] = [
                    'x' => (float) $feature['x'],
                    'y' => (float) $feature['y'],
                ];
            } elseif ($key === 'geometry') {
                // Geometry object (for polylines, polygons)
                $formatted['geometry'] = $value;
            } elseif ($key !== 'y' && $key !== 'geojson_geometry') {
                // All other fields are attributes (filter by requestedFields)
                if ($returnAllFields || in_array($key, $requestedFields)) {
                    $formatted['attributes'][$key] = $value;
                }
            }
        }

        return $formatted;
    }

    /**
     * Parse requested fields from outFields parameter
     *
     * @param string $outFields Comma-separated field names or '*'
     * @return array Array of field names
     */
    private function parseRequestedFields(string $outFields): array
    {
        if ($outFields === '*' || empty($outFields)) {
            return ['*'];
        }

        return array_map('trim', explode(',', $outFields));
    }

    /**
     * Filter field definitions based on requested fields
     *
     * @param array $allFields All field definitions from layer
     * @param array $requestedFields Requested field names
     * @return array Filtered field definitions
     */
    private function filterFields(array $allFields, array $requestedFields): array
    {
        if (in_array('*', $requestedFields)) {
            return $allFields;
        }

        $filtered = [];
        foreach ($allFields as $field) {
            $fieldName = $field['name'] ?? '';
            if (in_array($fieldName, $requestedFields)) {
                $filtered[] = $field;
            }
        }

        return $filtered;
    }

    /**
     * Format single feature for GeoJSON response
     *
     * @param array $feature Raw feature
     * @return array Formatted GeoJSON feature
     */
    private function formatFeatureAsGeoJson(array $feature): array
    {
        $formatted = [
            'type' => 'Feature',
            'geometry' => null,
            'properties' => [],
        ];

        // Extract ID if present
        if (isset($feature['OBJECTID'])) {
            $formatted['id'] = $feature['OBJECTID'];
        } elseif (isset($feature['objectid'])) {
            $formatted['id'] = $feature['objectid'];
        } elseif (isset($feature['id'])) {
            $formatted['id'] = $feature['id'];
        }

        // Process geometry and properties
        foreach ($feature as $key => $value) {
            if ($key === 'geojson_geometry' || $key === 'geometry') {
                // Geometry from PostGIS ST_AsGeoJSON
                $formatted['geometry'] = is_string($value) ? json_decode($value, true) : $value;
            } elseif ($key === 'x' && isset($feature['y'])) {
                // Point from x,y coordinates
                $formatted['geometry'] = [
                    'type' => 'Point',
                    'coordinates' => [(float) $feature['x'], (float) $feature['y']],
                ];
            } elseif ($key !== 'y' && $key !== 'geojson_geometry') {
                // Everything else goes to properties
                $formatted['properties'][$key] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Build count-only response
     *
     * Used when returnCountOnly parameter is true.
     * Returns only the count of matching features.
     *
     * @param int $count Feature count
     * @return array Count response
     *
     * @example
     * ```php
     * $response = $builder->buildCountResponse(142);
     * // Returns: ['count' => 142]
     * ```
     */
    public function buildCountResponse(int $count): array
    {
        return [
            'count' => $count,
        ];
    }

    /**
     * Build layer definition response
     *
     * @param array $definition Layer definition
     * @return array Complete layer definition
     */
    public function buildLayerDefinition(array $definition): array
    {
        return array_merge([
            'currentVersion' => 10.9,
            'id' => $definition['id'],
            'name' => $definition['name'],
            'type' => 'Feature Layer',
            'description' => $definition['description'] ?? '',
            'geometryType' => $definition['geometryType'],
            'copyrightText' => $definition['copyrightText'] ?? '',
            'parentLayer' => null,
            'subLayers' => [],
            'minScale' => 0,
            'maxScale' => 0,
            'drawingInfo' => $definition['drawingInfo'] ?? null,
            'defaultVisibility' => true,
            'extent' => $definition['extent'] ?? null,
            'hasAttachments' => false,
            'htmlPopupType' => 'esriServerHTMLPopupTypeAsHTMLText',
            'displayField' => $definition['displayField'] ?? '',
            'typeIdField' => null,
            'fields' => $definition['fields'],
            'relationships' => [],
            'canModifyLayer' => false,
            'canScaleSymbols' => false,
            'hasLabels' => false,
            'capabilities' => 'Query',
            'maxRecordCount' => $definition['maxRecordCount'] ?? 2000,
            'supportsStatistics' => true,
            'supportsAdvancedQueries' => true,
            'supportedQueryFormats' => 'JSON, geoJSON',
            'isDataVersioned' => false,
            'ownershipBasedAccessControlForFeatures' => ['allowOthersToQuery' => true],
            'useStandardizedQueries' => true,
            'advancedQueryCapabilities' => [
                'useStandardizedQueries' => true,
                'supportsStatistics' => true,
                'supportsHavingClause' => true,
                'supportsOrderBy' => true,
                'supportsDistinct' => true,
                'supportsCountDistinct' => true,
                'supportsPagination' => true,
                'supportsTrueCurve' => false,
                'supportsReturningQueryExtent' => true,
                'supportsQueryWithDistance' => true,
                'supportsSqlExpression' => true,
            ],
        ], $definition);
    }
}
