<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Iodani\ArcGIS\Server\Constants\FieldType;
use Iodani\ArcGIS\Server\Constants\GeometryType;
use Iodani\ArcGIS\Server\Contracts\TenantFilterInterface;
use Iodani\ArcGIS\Server\Core\FeatureLayer;
use Iodani\ArcGIS\Server\Core\FeatureServer;
use Iodani\ArcGIS\Server\DataSource\PostGISDataSource;

/**
 * Custom layer with multi-tenancy support
 *
 * Database table:
 * CREATE TABLE buildings (
 *     id SERIAL PRIMARY KEY,
 *     name VARCHAR(200),
 *     address VARCHAR(255),
 *     tenant_id INTEGER,
 *     status VARCHAR(50),
 *     geom GEOMETRY(POINT, 4326)
 * );
 */
class BuildingLayer extends FeatureLayer implements TenantFilterInterface
{
    private ?int $currentTenantId = null;

    /**
     * Set current tenant ID (from authentication/session)
     */
    public function setTenantId(int $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    protected function getTableName(): string
    {
        return 'buildings';
    }

    protected function getLayerConfig(): array
    {
        return [
            'id' => 0,
            'name' => 'Buildings',
            'description' => 'Building inventory with multi-tenancy',
            'geometryType' => GeometryType::POINT,
            'geometryColumn' => 'geom',
            'objectIdField' => 'id',
            'displayField' => 'name',
            'maxRecordCount' => 1000,

            'fields' => [
                ['name' => 'id', 'type' => FieldType::OID, 'alias' => 'ID'],
                ['name' => 'name', 'type' => FieldType::STRING, 'alias' => 'Building Name', 'length' => 200],
                ['name' => 'address', 'type' => FieldType::STRING, 'alias' => 'Address', 'length' => 255],
                ['name' => 'tenant_id', 'type' => FieldType::INTEGER, 'alias' => 'Tenant ID'],
                ['name' => 'status', 'type' => FieldType::STRING, 'alias' => 'Status', 'length' => 50],
            ],

            'spatialReference' => [
                'wkid' => 4326,
                'latestWkid' => 4326,
            ],
        ];
    }

    public function getTenantWhereClause(): string
    {
        return 't.tenant_id = ' . (int) $this->currentTenantId;
    }

    public function isTenantFilterEnabled(): bool
    {
        return $this->currentTenantId !== null && $this->currentTenantId > 0;
    }
}

// ============================================
// Usage Example
// ============================================

// Get tenant ID from session/auth (example)
session_start();
$tenantId = $_SESSION['tenant_id'] ?? 1; // Default to tenant 1 for demo

// Create database connection
$pdo = new PDO('pgsql:host=localhost;dbname=mydb', 'user', 'pass');
$dataSource = new PostGISDataSource($pdo, ['schema' => 'public']);

// Create layer and set tenant
$buildingLayer = new BuildingLayer($dataSource);
$buildingLayer->setTenantId($tenantId);

// Create server
$server = new FeatureServer($dataSource);
$server->registerLayer(BuildingLayer::class);

// Note: Because we set tenant on the instance, we need to replace the registered one
// Or better: register the instance directly (but current FeatureServer doesn't support this)
// For now, this shows how tenant filtering works

// Route requests
$pathInfo = $_SERVER['PATH_INFO'] ?? '/';
$parts = array_filter(explode('/', $pathInfo));
$parts = array_values($parts);

if (empty($parts)) {
    $response = $server->getServiceInfo();
} elseif (count($parts) === 1 && is_numeric($parts[0])) {
    $layerId = (int) $parts[0];
    $layer = $server->getLayer($layerId);

    // For this example, set tenant on the retrieved layer
    if ($layer instanceof BuildingLayer) {
        $layer->setTenantId($tenantId);
    }

    $response = $layer->getDefinition();
} elseif (count($parts) === 2 && $parts[1] === 'query') {
    $layerId = (int) $parts[0];
    $layer = $server->getLayer($layerId);

    // Set tenant before query
    if ($layer instanceof BuildingLayer) {
        $layer->setTenantId($tenantId);
    }

    $response = $layer->query($_GET);
} else {
    http_response_code(404);
    $response = ['error' => 'Not found'];
}

header('Content-Type: application/json');
echo json_encode($response);