<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Contracts;

/**
 * TenantFilterInterface
 *
 * Interface for implementing multi-tenancy filtering in layers.
 *
 * Layers that require tenant-based filtering should implement this interface.
 * The tenant filter is automatically applied to all queries (SELECT, COUNT, EXTENT).
 *
 * Example implementation:
 * ```php
 * class MyLayer extends FeatureLayer implements TenantFilterInterface
 * {
 *     public function getTenantWhereClause(): string
 *     {
 *         // Get current tenant identifier from your application
 *         $tenantId = $this->getCurrentTenantId();
 *
 *         // Quote the value using the database connection
 *         $quotedId = $this->quoteValue($tenantId);
 *
 *         return "t.tenant_id = {$quotedId}";
 *     }
 *
 *     public function isTenantFilterEnabled(): bool
 *     {
 *         // Disable for admin users or when tenant is not set
 *         if ($this->isAdmin() || empty($this->getCurrentTenantId())) {
 *             return false;
 *         }
 *
 *         return true;
 *     }
 *
 *     private function getCurrentTenantId(): ?string
 *     {
 *         // Implement according to your framework/application
 *         // Examples:
 *         // - Yii: Yii::app()->user->tenantId
 *         // - Laravel: auth()->user()->tenant_id
 *         // - Standalone: $_SESSION['tenant_id']
 *         return $_SESSION['tenant_id'] ?? null;
 *     }
 * }
 * ```
 */
interface TenantFilterInterface
{
    /**
     * Get tenant WHERE clause condition
     *
     * This method returns the SQL condition to filter data by tenant.
     * The condition is automatically added to all queries with AND.
     *
     * IMPORTANT:
     * - Always use $this->quoteValue() to escape values safely
     * - Do NOT include the WHERE keyword
     * - Use table alias 't' for main table
     *
     * @return string SQL WHERE condition (without WHERE keyword)
     */
    public function getTenantWhereClause(): string;

    /**
     * Check if tenant filtering is enabled for this request
     *
     * Return false to disable tenant filtering for special cases like:
     * - Admin users
     * - Public layers
     * - System queries
     * - When tenant identifier is not available
     *
     * @return bool True if tenant filtering should be applied
     */
    public function isTenantFilterEnabled(): bool;
}
