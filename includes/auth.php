<?php
require_once 'config.php';

/**
 * Check if a user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the logged in user is an admin
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

/**
 * Redirect to login page if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect to home page if not admin
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}
?>