<?php

return [
    // Provider default: Fonnte (https://fonnte.com). Kompatibel dengan gateway lain
    // yang menerima field "target" & "message" + header Authorization token.
    'provider' => env('WHATSAPP_PROVIDER', 'fonnte'),
    'token' => env('WHATSAPP_TOKEN', ''),
    'api_url' => env('WHATSAPP_API_URL', 'https://api.fonnte.com/send'),
    // Nomor admin untuk notifikasi internal (format 62xxxx). Kosong = tidak dikirim.
    'admin_number' => env('WHATSAPP_ADMIN_NUMBER', ''),
];
