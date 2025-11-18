<?php
require 'function.php';
$id = $_GET["id"];
hapus($koneksi, $id) > 0;
header("Location: index.php");
exit;
