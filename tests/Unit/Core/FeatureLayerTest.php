<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Tests\Unit\Core;

use Iodani\ArcGIS\Server\Constants\FieldType;
use Iodani\ArcGIS\Server\Constants\GeometryType;
use Iodani\ArcGIS\Server\Contracts\DataSourceInterface;
use Iodani\ArcGIS\Server\Core\FeatureLayer;
use PHPUnit\Framework\TestCase;

class FeatureLayerTest extends TestCase
{
    public function testGetIdReturnsLayerId(): void
    {
        $dataSource = $this->createMock(DataSourceInterface::class);
        $layer = new TestLayer($dataSource);

        $this->assertEquals(1, $layer->getId());
    }

    public function testGetNameReturnsLayerName(): void
    {
        $dataSource = $this->createMock(DataSourceInterface::class);
        $layer = new TestLayer($dataSource);

        $this->assertEquals('Test Layer', $layer->getName());
    }
}

// Test layer for unit tests
class TestLayer extends FeatureLayer
{
    protected function getTableName(): string
    {
        return 'test_table';
    }

    protected function getLayerConfig(): array
    {
        return [
            'id' => 1,
            'name' => 'Test Layer',
            'geometryType' => GeometryType::POINT,
            'fields' => [
                ['name' => 'id', 'type' => FieldType::OID],
            ],
        ];
    }
}
