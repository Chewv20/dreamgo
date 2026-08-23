<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';

use Core\Exceptions\ForbiddenException;
use Core\Exceptions\NotFoundException;
use Core\Request;
use Core\Router;

$router = new Router();
require BASE_PATH . '/config/routes.php';

$request = new Request();

try {
    $router->dispatch($request);
} catch (NotFoundException $e) {
    http_response_code(404);
    (new App\Controllers\Public\ErrorController($request))->notFound();
} catch (ForbiddenException $e) {
    http_response_code(403);
    (new App\Controllers\Public\ErrorController($request))->forbidden($e->getMessage());
} catch (\Throwable $e) {
    error_log('[APP] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    (new App\Controllers\Public\ErrorController($request))->serverError();
}
