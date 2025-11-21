<?php
// Test script to debug stores__server.php
session_start();

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test@test.com';
$_SESSION['bu'] = '9999';
$_SESSION['login_time'] = time();

// Set GET parameters
$_GET['where'] = "country='IT' OR country IS NULL OR country=''";
$_GET['start'] = 0;
$_GET['length'] = 10;
$_GET['draw'] = 1;

// Include the script
include 'scripts/stores__server.php';
?>
