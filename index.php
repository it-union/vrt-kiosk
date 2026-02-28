<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/auth.php';

$route = defined('ROUTE_OVERRIDE') ? ROUTE_OVERRIDE : null;

if ($route === null) {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? trim($path, '/') : '';

    if ($path === '' || $path === 'index.php') {
        $route = 'kiosk';
    } elseif (in_array($path, ['admin', 'template', 'kiosk', 'preview', 'content'], true)) {
        $route = $path;
    } elseif (isset($_GET['route'])) {
        // Backward compatibility
        $route = strtolower(trim((string)$_GET['route']));
    } else {
        http_response_code(404);
        echo 'Маршрут не найден';
        exit;
    }
}

$allowedRoutes = ['admin', 'template', 'kiosk', 'preview', 'content'];
if (!in_array($route, $allowedRoutes, true)) {
    http_response_code(404);
    echo 'Маршрут не найден';
    exit;
}

switch ($route) {
    case 'admin':
        requireAdminAuth();
        require __DIR__ . '/controllers/admin.php';
        break;

    case 'template':
        requireTemplateAuth();
        require __DIR__ . '/controllers/template.php';
        break;

    case 'content':
        requireTemplateAuth();
        require __DIR__ . '/controllers/content.php';
        break;

    case 'preview':
        require __DIR__ . '/controllers/preview.php';
        break;

    case 'kiosk':
    default:
        require __DIR__ . '/controllers/kiosk.php';
        break;
}
