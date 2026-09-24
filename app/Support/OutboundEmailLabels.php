<?php

namespace App\Support;

/**
 * Port of hresume_frontend/src/utils/outboundEmailLabels.js for Blade admin views.
 */
class OutboundEmailLabels
{
    public const TYPES = [
        'admin_custom' => 'Custom message',
        'resume_incomplete_reminder' => 'Resume reminder',
        'email_verification_reminder' => 'Verification reminder',
        'new_features_announcement' => 'New features',
    ];

    public const STATUS = [
        'queued' => ['label' => 'Queued', 'className' => 'bg-amber-100 text-amber-900'],
        'processing' => ['label' => 'Processing', 'className' => 'bg-blue-100 text-blue-900'],
        'sent' => ['label' => 'Sent', 'className' => 'bg-emerald-100 text-emerald-900'],
        'failed' => ['label' => 'Failed', 'className' => 'bg-red-100 text-red-900'],
        'skipped' => ['label' => 'Skipped', 'className' => 'bg-gray-100 text-gray-700'],
    ];

    public static function type(?string $type): string
    {
        return self::TYPES[$type] ?? ($type ?: '—');
    }

    /** @return array{label: string, className: string} */
    public static function status(?string $status): array
    {
        return self::STATUS[$status] ?? ['label' => (string) $status, 'className' => 'bg-gray-100 text-gray-700'];
    }
}
