<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Constants;

/**
 * GeometryType
 *
 * ArcGIS geometry type constants.
 */
class GeometryType
{
    public const POINT = 'esriGeometryPoint';
    public const MULTIPOINT = 'esriGeometryMultipoint';
    public const POLYLINE = 'esriGeometryPolyline';
    public const POLYGON = 'esriGeometryPolygon';
    public const ENVELOPE = 'esriGeometryEnvelope';
}
