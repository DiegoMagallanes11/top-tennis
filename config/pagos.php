<?php

return [
    // Pagos reales manuales; la demostración local no conecta con bancos.
    'driver' => env('PAGOS_DRIVER', 'manual'),
    'yape_enabled' => env('YAPE_ENABLED', false),
    'yape_qr' => env('YAPE_QR_PATH', 'images/yape.png'),
    'demo_enabled' => env('PAGOS_DEMO_ENABLED', true),
];
