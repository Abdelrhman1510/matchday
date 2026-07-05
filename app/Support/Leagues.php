<?php

namespace App\Support;

/**
 * Maps English league names to Arabic. Returns null for unknown leagues so the
 * frontend can fall back to the English `league` field.
 */
class Leagues
{
    public const AR = [
        'Premier League'          => 'الدوري الإنجليزي الممتاز',
        'English Premier League'  => 'الدوري الإنجليزي الممتاز',
        'La Liga'                 => 'الدوري الإسباني',
        'Serie A'                 => 'الدوري الإيطالي',
        'Bundesliga'              => 'الدوري الألماني',
        'Ligue 1'                 => 'الدوري الفرنسي',
        'Champions League'        => 'دوري أبطال أوروبا',
        'UEFA Champions League'   => 'دوري أبطال أوروبا',
        'Europa League'           => 'الدوري الأوروبي',
        'Saudi Pro League'        => 'دوري روشن السعودي',
        'Saudi Arabian Pro League'=> 'دوري روشن السعودي',
        'National Teams'          => 'المنتخبات الوطنية',
        'Primeira Liga'           => 'الدوري البرتغالي',
        'Eredivisie'              => 'الدوري الهولندي',
        'Super Lig'               => 'الدوري التركي',
        'Süper Lig'               => 'الدوري التركي',
        'Egyptian Premier League' => 'الدوري المصري الممتاز',
        'Scottish Premiership'    => 'الدوري الاسكتلندي الممتاز',
        'Belgian First Division A'=> 'الدوري البلجيكي',
        'Primera Division'        => 'الدوري الأرجنتيني',
        'Brazilian Serie A'       => 'الدوري البرازيلي',
        'Major League Soccer'     => 'الدوري الأمريكي',
        'Qatar Stars League'      => 'دوري نجوم قطر',
        'UAE Pro League'          => 'دوري المحترفين الإماراتي',
        'World Cup'               => 'كأس العالم',
        'Friendly'                => 'مباراة ودية',
        'Friendlies'              => 'مباريات ودية',
    ];

    public static function ar(?string $league): ?string
    {
        if ($league === null) {
            return null;
        }
        return self::AR[$league] ?? null;
    }
}
