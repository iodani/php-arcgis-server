# PHP ArcGIS Server

Self-hosted ArcGIS Feature Server implementation in PHP for serving PostGIS/PostgreSQL data to ArcGIS JS SDK **without requiring an ESRI subscription**.

## ✨ Features

- ✅ **No ESRI Subscription Required** - Serve your own data from PostGIS/PostgreSQL
- ✅ **ArcGIS JS SDK Compatible** - Drop-in replacement for ESRI Feature Services
- ✅ **Multi-Tenancy Support** - Built-in tenant filtering for SaaS applications
- ✅ **Framework Agnostic** - Works with Yii, Laravel, or standalone
- ✅ **Automatic Extent Calculation** - PostGIS-powered bounding box calculation
- ✅ **Field Mapping & JOINs** - Complex SQL queries with field mapping support
- ✅ **Multiple Data Sources** - PostGIS direct or through Yii Framework
- ✅ **Production Ready** - Used in production serving enterprise GIS data

## 📦 Installation

```bash
composer require iodani/php-arcgis-server
```

## 🚀 Quick Start

### Standalone Usage

```php
use Iodani\ArcGIS\Server\Core\FeatureServer;
use Iodani\ArcGIS\Server\DataSource\PostGISDataSource;
use Iodani\ArcGIS\Server\Examples\SimplePointLayer;

// Create PDO connection
$pdo = new PDO('pgsql:host=localhost;dbname=mydb', 'user', 'pass');

// Create data source
$dataSource = new PostGISDataSource($pdo, [
    'schema' => 'public',
    'srid' => 4326
]);

// Create server
$server = new FeatureServer($dataSource);

// Register layers
$server->registerLayer(SimplePointLayer::class);

// Handle request
$response = $server->handleRequest($_GET);

header('Content-Type: application/json');
echo json_encode($response);
```

### Yii 1.x Usage

See [docs/framework-guides/yii-1.x.md](docs/framework-guides/yii-1.x.md) for detailed guide.

```php
// In your controller
use Iodani\ArcGIS\Server\Core\FeatureServer;
use Iodani\ArcGIS\Server\DataSource\YiiDataSource;

class GisController extends Controller
{
    private FeatureServer $server;

    public function init()
    {
        parent::init();
        
        $dataSource = new YiiDataSource(Yii::app()->dbRead, [
            'schema' => 'public',
            'srid' => 4326
        ]);

        $this->server = new FeatureServer($dataSource);
        $this->server->registerLayer(YourCustomLayer::class);
    }

    public function actionFeatureServer($layer = null)
    {
        $response = $this->server->handleRequest($_GET, $layer);
        
        header('Content-Type: application/json');
        echo json_encode($response);
        Yii::app()->end();
    }
}
```

## 📖 Documentation

- [Getting Started](docs/getting-started.md)
- [Creating Custom Layers](docs/custom-layers.md)
- [Framework Guides](docs/framework-guides/)
    - [Yii 1.x Integration](docs/framework-guides/yii-1.x.md)
    - [Laravel Integration](docs/framework-guides/laravel.md)
    - [Standalone Usage](docs/framework-guides/standalone.md)
- [API Reference](docs/api/reference.md)
- [Advanced Usage](docs/advanced.md)

## 🧪 Testing

```bash
# Run tests
composer test

# Run with coverage
composer test:coverage

# Check code style
composer cs

# Fix code style
composer cs:fix

# Run static analysis
composer stan

# Run all quality checks
composer quality
```

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📝 License

MIT License - see [LICENSE](LICENSE) for details.

## 🙏 Credits

Created by [Iodani Batista Pérez](https://github.com/iodani)