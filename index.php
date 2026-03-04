<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/auth.php';

$route = defined('ROUTE_OVERRIDE') ? ROUTE_OVERRIDE : null;

if ($route === null) {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? trim($path, '/') : '';

    if (isset($_GET['route'])) {
        $route = strtolower(trim((string)$_GET['route']));
    } elseif ($path === '' || $path === 'index.php') {
        $route = 'promo';
    } elseif (in_array($path, ['admin', 'template', 'kiosk', 'preview', 'content', 'login', 'logout', 'users', 'promo', 'queue', 'settings'], true)) {
        $route = $path;
    } else {
        http_response_code(404);
        echo 'Маршрут не найден';
        exit;
    }
}

$allowedRoutes = ['admin', 'template', 'kiosk', 'preview', 'content', 'login', 'logout', 'users', 'promo', 'queue', 'settings'];
if (!in_array($route, $allowedRoutes, true)) {
    http_response_code(404);
    echo 'Маршрут не найден';
    exit;
}

switch ($route) {
    case 'admin':
        if (authIsLoggedIn()) {
            requireAdminAuth();
            require __DIR__ . '/controllers/admin.php';
        } else {
            require __DIR__ . '/controllers/login.php';
        }
        break;

    case 'queue':
        requirePanelAuth();
        require __DIR__ . '/controllers/queue.php';
        break;

    case 'promo':
        require __DIR__ . '/controllers/promo.php';
        break;

    case 'login':
        authRedirect('/admin/');
        break;

    case 'logout':
        require __DIR__ . '/controllers/logout.php';
        break;

    case 'users':
        requireAdministratorRole();
        require __DIR__ . '/controllers/users.php';
        break;

    case 'settings':
        requireAdministratorRole();
        require __DIR__ . '/controllers/settings.php';
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
