<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\PropertyOptionsSettings;

class PropertyOptionResolver
{
    /**
     * @return array<string, string>
     */
    public static function legalStatusMap(): array
    {
        $legalStatuses = [];

        try {
            $legalStatuses = app(PropertyOptionsSettings::class)->legal_statuses ?? [];
        } catch (\Throwable) {
            $legalStatuses = [];
        }

        if (!is_array($legalStatuses) || empty($legalStatuses)) {
            $legalStatuses = config('property.legal_statuses', []);
        }

        $normalized = [];
        foreach ($legalStatuses as $code => $label) {
            $normalizedCode = strtoupper(trim((string) $code));
            $normalizedLabel = trim((string) $label);

            if ($normalizedCode === '' || $normalizedLabel === '') {
                continue;
            }

            $normalized[$normalizedCode] = $normalizedLabel;
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function allPurposesMap(): array
    {
        return array_merge([
            'PROPERTY_IMAGE' => 'Ảnh bất động sản',
            'AVATAR' => 'Ảnh đại diện',
            'CCCD_FRONT' => 'CCCD mặt trước',
            'CCCD_BACK' => 'CCCD mặt sau',
            'REPORT_EVIDENCE' => 'Bằng chứng báo cáo',
            'LEGAL_DOC' => 'Tài liệu pháp lý',
        ], self::legalStatusMap());
    }

    /**
     * @return array<int, string>
     */
    public static function legalStatusCodes(): array
    {
        return array_keys(self::legalStatusMap());
    }

    /**
     * @return array<int, string>
     */
    public static function uploadFilePurposes(): array
    {
        return array_values(array_unique(array_merge(
            ['PROPERTY_IMAGE', 'AVATAR', 'REPORT_EVIDENCE'],
            self::legalStatusCodes()
        )));
    }

    public static function isLegalDocumentPurpose(?string $purpose): bool
    {
        if (!is_string($purpose) || $purpose === '') {
            return false;
        }

        return strtoupper($purpose) === 'LEGAL_DOC' || in_array(strtoupper($purpose), self::legalStatusCodes(), true);
    }

    public static function defaultLegalPurpose(): ?string
    {
        $codes = self::legalStatusCodes();

        if (in_array('KHAC', $codes, true)) {
            return 'KHAC';
        }

        return $codes[0] ?? null;
    }

    public static function normalizePurpose(?string $purpose): ?string
    {
        if (!is_string($purpose)) {
            return $purpose;
        }

        return strtoupper(trim($purpose));
    }
}
