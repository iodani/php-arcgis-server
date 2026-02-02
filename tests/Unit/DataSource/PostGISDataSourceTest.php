<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Tests\Unit\DataSource;

use Iodani\ArcGIS\Server\DataSource\PostGISDataSource;
use PDO;
use PHPUnit\Framework\TestCase;

class PostGISDataSourceTest extends TestCase
{
    private PDO $pdo;
    private PostGISDataSource $dataSource;

    protected function setUp(): void
    {
        // Create in-memory SQLite for basic testing
        // Note: This doesn't test PostGIS-specific functions
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->dataSource = new PostGISDataSource($this->pdo, [
            'schema' => 'public',
            'geometry_column' => 'geom',
            'srid' => 4326,
        ]);
    }

    public function testConstructorSetsDefaultValues(): void
    {
        $dataSource = new PostGISDataSource($this->pdo);
        $this->assertInstanceOf(PostGISDataSource::class, $dataSource);
    }

    public function testConstructorAcceptsConfiguration(): void
    {
        $dataSource = new PostGISDataSource($this->pdo, [
            'schema' => 'custom_schema',
            'geometry_column' => 'custom_geom',
            'srid' => 3857,
        ]);

        $this->assertInstanceOf(PostGISDataSource::class, $dataSource);
    }

    public function testIsAvailableReturnsFalseWithoutPostGIS(): void
    {
        // SQLite doesn't have PostGIS
        $this->assertFalse($this->dataSource->isAvailable());
    }

    public function testGetDbReturnsConnection(): void
    {
        $db = $this->dataSource->getDb();
        $this->assertInstanceOf(PDO::class, $db);
        $this->assertSame($this->pdo, $db);
    }
}
