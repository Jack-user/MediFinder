<?php
session_start();
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'pharmacy_owner')) {
    header('Location: /medi/auth/login.php');
    exit;
}
require_once __DIR__ . '/../includes/db.php';
// Reuse existing pharmacy dashboard
include __DIR__ . '/../pharmacy_dashboard.php';


