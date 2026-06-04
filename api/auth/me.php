<?php
/* ============================================================
   GET /api/v1/auth/me
   ============================================================
   Returns the currently authenticated user from session.
   Used by auth.js → checkAuth() on every page load.
   ============================================================ */

require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$user = requireAuth();
jsonResponse($user);
