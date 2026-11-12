<?php

return [
    // Naskah yang lebih lama dari ini di satu tahap tampil sebagai "tertahan" di Laporan dan
    // pemegang gilirannya diingatkan setiap kelipatan hari ini.
    'hari_tertahan' => (int) env('SIFKUMDA_HARI_TERTAHAN', 7),
];
