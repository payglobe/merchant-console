<?php
/**
 * MC5 Logout Handler
 * Gestisce il logout dell'utente da MC5 v3.0
 */

session_start();
require_once 'authentication.php';

// Esegui logout
logout();

// Redirect al login con messaggio
header("Location: login.php?logged_out=1");
exit;
