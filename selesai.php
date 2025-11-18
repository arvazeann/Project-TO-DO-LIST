<?php
require 'function.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    if (selesai($koneksi, $id) > 0) {
        http_response_code(200);
        echo "Tugas berhasil ditandai selesai";
    } else {
        http_response_code(500);
        echo "Gagal menandai tugas selesai";
    }
} else {
    http_response_code(400);
    echo "ID tidak valid";
}
