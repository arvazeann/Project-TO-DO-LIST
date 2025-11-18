<?php
require 'function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (tambah($koneksi, $_POST) > 0) {
        http_response_code(200);
        echo "Data berhasil ditambahkan.";
    } else {
        http_response_code(500);
        echo "Data gagal ditambahkan.";
    }
} else {
    http_response_code(405);
    echo "Metode tidak diizinkan.";
}
