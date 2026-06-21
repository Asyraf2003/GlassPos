<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;

final class PaymentComponentSelectionIds
{
    /**
     * @param list<string> $selectedIds
     * @return list<string>
     */
    public static function normalize(array $selectedIds): array
    {
        $normalized = [];

        foreach ($selectedIds as $id) {
            $trimmed = trim($id);

            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function fromAllocation(PaymentComponentAllocation $allocation): string
    {
        return self::fromParts(
            $allocation->workItemId(),
            $allocation->componentType(),
            $allocation->componentRefId(),
        );
    }

    public static function fromParts(string $workItemId, string $componentType, string $componentRefId): string
    {
        return trim($workItemId) . '::' . trim($componentType) . '::' . trim($componentRefId);
    }

    public static function isComponentSelector(string $id): bool
    {
        return self::parts($id) !== null;
    }

    public static function workItemId(string $id): string
    {
        $parts = self::parts($id);

        return $parts !== null ? $parts[0] : trim($id);
    }

    /**
     * @param list<string> $selectedIds
     * @return list<string>
     */
    public static function workItemIds(array $selectedIds): array
    {
        return array_values(array_unique(array_map(
            static fn (string $id): string => self::workItemId($id),
            self::normalize($selectedIds),
        )));
    }

    /**
     * @param list<string> $selectedIds
     */
    public static function matches(PaymentComponentAllocation $allocation, array $selectedIds): bool
    {
        $normalized = self::normalize($selectedIds);

        if ($normalized === []) {
            return true;
        }

        return self::matchingIds($allocation, $normalized) !== [];
    }

    /**
     * @param list<string> $selectedIds
     * @return list<string>
     */
    public static function matchingIds(PaymentComponentAllocation $allocation, array $selectedIds): array
    {
        $matches = [];
        $componentId = self::fromAllocation($allocation);

        foreach (self::normalize($selectedIds) as $id) {
            if (self::isComponentSelector($id)) {
                if ($id === $componentId) {
                    $matches[] = $id;
                }

                continue;
            }

            if (trim($id) === $allocation->workItemId()) {
                $matches[] = trim($id);
            }
        }

        return array_values(array_unique($matches));
    }

    /**
     * @return array{0:string,1:string,2:string}|null
     */
    private static function parts(string $id): ?array
    {
        $parts = explode('::', trim($id), 3);

        if (count($parts) !== 3) {
            return null;
        }

        $normalized = array_map(static fn (string $part): string => trim($part), $parts);

        return in_array('', $normalized, true) ? null : $normalized;
    }
}
