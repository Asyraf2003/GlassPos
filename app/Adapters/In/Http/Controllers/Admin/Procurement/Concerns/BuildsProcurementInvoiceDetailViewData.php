<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Concerns;

trait BuildsProcurementInvoiceDetailViewData
{
    use BuildsProcurementInvoiceDetailLinesView;
    use BuildsProcurementInvoiceDetailPolicyView;
    use BuildsProcurementInvoiceDetailSummaryView;
    use BuildsProcurementInvoiceDetailVersionTimelineView;
    use FormatsProcurementInvoiceDetailViewValue;

    /**
     * @param array<string, mixed> $detail
     * @return array<string, mixed>
     */
    private function buildViewData(array $detail): array
    {
        $summary = is_array($detail['summary'] ?? null) ? $detail['summary'] : [];
        $lines = is_array($detail['lines'] ?? null) ? $detail['lines'] : [];
        $versionTimeline = is_array($detail['version_timeline'] ?? null) ? $detail['version_timeline'] : [];

        return [
            'summaryView' => $this->buildSummaryView($summary),
            'linesView' => $this->buildLinesView($lines),
            'policyView' => $this->buildPolicyView($summary),
            'versionTimelineView' => $this->buildVersionTimelineView($versionTimeline),
        ];
    }
}
