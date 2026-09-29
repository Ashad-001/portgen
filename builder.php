<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?message=' . urlencode('Please log in to access the portfolio builder.'));
    exit;
}

include 'builder.html';
