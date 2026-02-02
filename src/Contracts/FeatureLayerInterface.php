<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Contracts;

/**
 * FeatureLayerInterface
 *
 * Represents an ArcGIS Feature Layer with complete REST API compliance.
 */
interface FeatureLayerInterface
{
    /**
     * Get layer ID
     *
     * @return int Layer ID (0-based index)
     */
    public function getId(): int;

    /**
     * Get layer name
     *
     * @return string Layer name
     */
    public function getName(): string;

    /**
     * Get complete layer definition for ArcGIS JS SDK
     *
     * This is called when SDK requests: GET /FeatureServer/0
     *
     * @return array Layer definition with fields, renderer, extent, etc.
     */
    public function getDefinition(): array;

    /**
     * Execute query and return features with complete metadata
     *
     * This is called when SDK requests: GET /FeatureServer/0/query
     *
     * @param array $params Query parameters (where, outFields, returnGeometry, f, etc.)
     * @return array Query response with features and metadata
     */
    public function query(array $params = []): array;
}
