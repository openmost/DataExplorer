<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Catalog;

final class DimensionEntry
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $category,
        public readonly string $scope,
        public readonly string $table,
        public readonly string $column,
        public readonly ?object $join,
        public readonly ?object $discriminator,
        public readonly string $segment,
        public readonly string $type
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'scope' => $this->scope,
            'segment' => $this->segment,
            'type' => $this->type,
        ];
    }
}
