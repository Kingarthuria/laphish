<?php
/**
 * Logout admin: mengakhiri session lalu kembali ke halaman login.
 */
require __DIR__ . '/../includes/init.php';

logout();
// Mulai session baru (ID baru) hanya untuk membawa pesan "sudah keluar" ke halaman login
session_start();
session_regenerate_id(true);
flash('info', 'Anda sudah keluar.');
redirect('admin/login.php');
