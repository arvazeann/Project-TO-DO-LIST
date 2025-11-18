<?php
require 'function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (ubah($koneksi, $_POST) > 0) {
        http_response_code(200);
        echo "Data berhasil diubah.";
    } else {
        http_response_code(500);
        echo "Data gagal diubah.";
    }
} else {
    http_response_code(405);
    echo "Metode tidak diizinkan.";
}
