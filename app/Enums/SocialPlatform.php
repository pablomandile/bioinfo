<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Instagram = 'instagram';
    case X = 'x';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';
    case Facebook = 'facebook';
    case LinkedIn = 'linkedin';
    case GitHub = 'github';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Website = 'website';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::X => 'X (Twitter)',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
            self::Facebook => 'Facebook',
            self::LinkedIn => 'LinkedIn',
            self::GitHub => 'GitHub',
            self::WhatsApp => 'WhatsApp',
            self::Email => 'Email',
            self::Website => 'Sitio web',
        };
    }

    /**
     * Nombre del ícono de lucide-vue-next usado en el frontend.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Instagram => 'instagram',
            self::X => 'twitter',
            self::TikTok => 'music',
            self::YouTube => 'youtube',
            self::Facebook => 'facebook',
            self::LinkedIn => 'linkedin',
            self::GitHub => 'github',
            self::WhatsApp => 'message-circle',
            self::Email => 'mail',
            self::Website => 'globe',
        };
    }
}
