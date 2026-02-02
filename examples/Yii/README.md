# Yii 1.x Integration Guide

Complete guide for integrating PHP ArcGIS Server with Yii Framework 1.x.

## Installation
```bash
composer require iodani/php-arcgis-server
```

## Setup Steps

### 1. Configure URL Manager

Edit `protected/config/main.php`:
```php
'urlManager' => [
    'urlFormat' => 'path',
    'showScriptName' => false,
    'rules' => [
        // ArcGIS Feature Server endpoints
        'gis/FeatureServer' => 'gis/featureServer',
        'gis/FeatureServer/<layer:\d+>' => 'gis/featureServer',
        'gis/FeatureServer/<layer:\d+>/query' => 'gis/featureServer',
    ],
],
```

### 2. Create GIS Controller

Create `protected/controllers/GisController.php`:

See `controller-example.php` in this directory.

### 3. Create Custom Layers

Create `protected/components/arcgis_server/layers/YourLayer.php`:

See `layer-example.php` in this directory.

### 4. Test Endpoints
```bash
# Service definition
curl http://yourapp.com/gis/FeatureServer

# Layer definition
curl http://yourapp.com/gis/FeatureServer/0

# Query features
curl "http://yourapp.com/gis/FeatureServer/0/query?where=1=1&f=json"
```

## URL Structure
```
/gis/FeatureServer              → Service definition (all layers)
/gis/FeatureServer/0            → Layer 0 definition
/gis/FeatureServer/0/query      → Query layer 0
/gis/FeatureServer/1/query      → Query layer 1
```

## ArcGIS JS SDK Integration
```javascript
const featureLayer = new FeatureLayer({
  url: "http://yourapp.com/gis/FeatureServer/0"
});

map.add(featureLayer);
```

## Tips

- Use `Yii::app()->dbRead` for read-only operations
- Implement `TenantFilterInterface` for multi-tenancy
- Use `getFromClause()` for complex JOINs
- Use `getFieldMap()` for computed fields
- Cache layer definitions in production