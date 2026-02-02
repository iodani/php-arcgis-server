<?php

/**
 * GisController - Example Yii 1.x controller for ArcGIS Feature Server
 *
 * Place this file in: protected/controllers/GisController.php
 *
 * URL Configuration in config/main.php:
 * 'urlManager' => [
 *     'urlFormat' => 'path',
 *     'rules' => [
 *         'gis/FeatureServer' => 'gis/featureServer',
 *         'gis/FeatureServer/<layer:\d+>' => 'gis/featureServer',
 *         'gis/FeatureServer/<layer:\d+>/query' => 'gis/featureServer',
 *     ],
 * ],
 */

use Iodani\ArcGIS\Server\Core\FeatureServer;
use Iodani\ArcGIS\Server\DataSource\YiiDataSource;

class GisController extends CController
{
    private FeatureServer $server;

    /**
     * Initialize Feature Server with layers
     */
    public function init()
    {
        parent::init();

        // Create data source using Yii's database connection
        $dataSource = new YiiDataSource(
            Yii::app()->dbRead,
            [
                'schema' => 'public',
                'geometry_column' => 'geom',
                'srid' => 4326,
            ]
        );

        // Create Feature Server
        $this->server = new FeatureServer($dataSource, [
            'description' => 'GIS Feature Server',
            'maxRecordCount' => 2000,
        ]);

        // Register your custom layers
        // These classes should be in protected/components/arcgis_server/layers/
        $this->server->registerLayer('PlaceLayer');
        $this->server->registerLayer('HydrantLayer');
        // Add more layers as needed
    }

    /**
     * Handle ArcGIS Feature Server requests
     *
     * Routes:
     * - /gis/FeatureServer             → Service info
     * - /gis/FeatureServer/0            → Layer 0 definition
     * - /gis/FeatureServer/0/query      → Query layer 0
     */
    public function actionFeatureServer($layer = null)
    {
        try {
            // Determine action based on URL
            $action = Yii::app()->request->getParam('action');

            if ($layer === null) {
                // GET /gis/FeatureServer → Service info
                $response = $this->server->getServiceInfo();
            } elseif ($action === 'query' || isset($_GET['where'])) {
                // GET /gis/FeatureServer/0/query → Query features
                $layerObj = $this->server->getLayer((int) $layer);
                $response = $layerObj->query($_GET);
            } else {
                // GET /gis/FeatureServer/0 → Layer definition
                $layerObj = $this->server->getLayer((int) $layer);
                $response = $layerObj->getDefinition();
            }

            // Send JSON response
            header('Content-Type: application/json');
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (RuntimeException $e) {
            // Layer not found or other error
            Yii::log('ArcGIS Server Error: ' . $e->getMessage(), CLogger::LEVEL_ERROR);

            header('HTTP/1.1 404 Not Found');
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'code' => 404,
                    'message' => $e->getMessage(),
                ],
            ]);

        } catch (Exception $e) {
            // Other errors
            Yii::log('ArcGIS Server Error: ' . $e->getMessage(), CLogger::LEVEL_ERROR);

            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'code' => 500,
                    'message' => 'Internal Server Error',
                    'details' => YII_DEBUG ? $e->getMessage() : null,
                ],
            ]);
        }

        Yii::app()->end();
    }

    /**
     * Optional: Enable CORS if needed
     */
    public function beforeAction($action)
    {
        // Enable CORS for cross-origin requests
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Handle OPTIONS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            Yii::app()->end();
        }

        return parent::beforeAction($action);
    }

    /**
     * Disable CSRF validation for API endpoints
     */
    public function filters()
    {
        return [];
    }
}