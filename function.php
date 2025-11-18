<?php
require 'config.php';

function updateStatusOtomatis($koneksi)
{
    $today = date('Y-m-d');

    $query_overdue = "UPDATE todo_list SET status = 'overdue' 
                     WHERE due_date < '$today' 
                     AND status NOT IN ('selesai', 'done')";
    mysqli_query($koneksi, $query_overdue);

    $query_today = "UPDATE todo_list SET status = 'progress' 
                   WHERE due_date = '$today' 
                   AND status NOT IN ('selesai', 'done', 'overdue')";
    mysqli_query($koneksi, $query_today);
}

updateStatusOtomatis($koneksi);

function ambildata($koneksi, $ambilData)
{
    $hasil = mysqli_query($koneksi, $ambilData);
    $data = [];
    while ($baris = mysqli_fetch_assoc($hasil)) {
        $data[] = $baris;
    }
    return $data;
}

function tambah($koneksi, $data)
{
    $query = "INSERT INTO todo_list (title, description, status, created_at, due_date, priority) 
              VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($koneksi, $query);

    $judul = htmlspecialchars($data["judul"]);
    $deskripsi = htmlspecialchars($data["deskripsi"]);
    $status = $data["status"];
    $dibuat = $data["dibuat"];
    $deadline = htmlspecialchars($data["deadline"]);
    $prioritas = htmlspecialchars($data["prioritas"]);

    mysqli_stmt_bind_param($stmt, "ssssss", $judul, $deskripsi, $status, $dibuat, $deadline, $prioritas);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_affected_rows($stmt);
}

function hapus($koneksi, $data)
{
    $id = intval($data);
    $query = "DELETE FROM todo_list WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_affected_rows($stmt);
}

function ubah($koneksi, $data)
{
    $query = "UPDATE todo_list SET 
                title = ?, 
                description = ?, 
                status = ?, 
                created_at = ?, 
                due_date = ?, 
                priority = ?
              WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    $judul = htmlspecialchars($data["judul"]);
    $deskripsi = htmlspecialchars($data["deskripsi"]);
    $status = $data["status"];
    $dibuat = $data["dibuat"];
    $deadline = htmlspecialchars($data["deadline"]);
    $prioritas = htmlspecialchars($data["prioritas"]);
    $id = intval($data["id"]);

    mysqli_stmt_bind_param($stmt, "ssssssi", $judul, $deskripsi, $status, $dibuat, $deadline, $prioritas, $id);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_affected_rows($stmt);
}

function selesai($koneksi, $id)
{
    $id = intval($id);
    $query = "UPDATE todo_list SET status = 'selesai' WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_affected_rows($stmt);
}

function cari($koneksi, $keyword)
{
    $searchKeyword = "%" . $keyword . "%";
    $query = "SELECT * FROM todo_list WHERE 
              title LIKE ? OR
              description LIKE ? OR
              status LIKE ? OR 
              created_at LIKE ? OR
              due_date LIKE ? OR
              priority LIKE ?";

    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssssss", $searchKeyword, $searchKeyword, $searchKeyword, $searchKeyword, $searchKeyword, $searchKeyword);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($baris = mysqli_fetch_assoc($hasil)) {
        $data[] = $baris;
    }
    return $data;
}