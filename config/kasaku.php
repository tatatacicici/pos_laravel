<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KasaKu Application Branding & Configuration
    |--------------------------------------------------------------------------
    |
    | Modular branding configuration for multi-client deployment.
    | Easily customized via .env without modifying core code.
    |
    */

    'app_name' => env('KASAKU_APP_NAME', 'KasaKu'),
    'app_tagline' => env('KASAKU_TAGLINE', 'Smart & Modular Point of Sale'),
    'logo_url' => env('KASAKU_LOGO_URL', null),

    'theme' => [
        'palette' => 'dark_coffee',
        'primary' => env('KASAKU_PRIMARY_COLOR', '#6F4E37'),      // Coffee brown
        'accent' => env('KASAKU_ACCENT_COLOR', '#D4A574'),        // Caramel gold
        'bg_dark' => env('KASAKU_BG_DARK', '#1A120B'),           // Deep espresso
        'card_dark' => env('KASAKU_CARD_DARK', '#2B1E16'),       // Roasted mocha
        'surface' => env('KASAKU_SURFACE', '#3C2A21'),           // Walnut surface
        'text_cream' => env('KASAKU_TEXT_CREAM', '#F5ECE5'),     // Vanilla cream
    ],

    'receipt' => [
        'powered_by' => env('KASAKU_POWERED_BY', 'Powered by KasaKu PoS'),
        'default_footer' => env('KASAKU_RECEIPT_FOOTER', 'Terima kasih atas kunjungan Anda!'),
    ],
];
