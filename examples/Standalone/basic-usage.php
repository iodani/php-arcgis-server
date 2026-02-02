<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Iodani\ArcGIS\Server\Core\FeatureServer;
use Iodani\ArcGIS\Server\DataSource\PostGISDataSource;
use Iodani\ArcGIS\Server\Examples\SimplePointLayer;

// Create PostGIS connection
$pdo = new PDO(
    'pgsql:host=localhost;dbname=gis_database',
    'username',
    'password'
);

// Create data source
$dataSource = new PostGISDataSource($pdo, [
    'schema' => 'public',
    'srid' => 4326,
]);

// Create Feature Server
$server = new FeatureServer($dataSource);

// Register layers
$server->registerLayer(SimplePointLayer::class);

// Route requests
$pathInfo = $_SERVER['PATH_INFO'] ?? '/';
$parts = array_filter(explode('/', $pathInfo));
$parts = array_values($parts);

if (empty($parts)) {
    // GET /FeatureServer → Service info
    $response = $server->getServiceInfo();
} elseif (count($parts) === 1 && is_numeric($parts[0])) {
    // GET /FeatureServer/0 → Layer definition
    $layerId = (int) $parts[0];
    $layer = $server->getLayer($layerId);
    $response = $layer->getDefinition();
} elseif (count($parts) === 2 && $parts[1] === 'query') {
    // GET /FeatureServer/0/query → Query features
    $layerId = (int) $parts[0];
    $layer = $server->getLayer($layerId);
    $response = $layer->query($_GET);
} else {
    http_response_code(404);
    $response = ['error' => 'Not found'];
}

// Send response
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);