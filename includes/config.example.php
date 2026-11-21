<?php
/**
 * Konfigurasi LaPhish (CONTOH).
 *
 * Cara pakai: salin file ini menjadi `includes/config.php`, lalu sesuaikan nilainya.
 * File `config.php` tidak ikut di-commit ke repo karena berisi kredensial lokal.
 * Nilai di bawah adalah bawaan XAMPP (user root tanpa password).
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'laphish',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'     => 'LaPhish',
        'timezone' => 'Asia/Jakarta',
        // true hanya saat pengembangan: menampilkan pesan galat teknis di layar
        'debug'    => false,
    ],
];
