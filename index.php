<?php
/**
 * TicketVan Lite - Front Controller (FastRoute + Twig + CSRF + RBAC)
 */

define("ROOT", __DIR__);

$debug = true;
if ($debug) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

require_once __DIR__ . "/core/autoload.php";
require_once __DIR__ . "/core/app/autoload.php";

ob_start();
session_start();
Core::$root = "";

use App\Service\AuthService;

$routes = require __DIR__ . '/core/app/routes.php';
$dispatcher = FastRoute\simpleDispatcher($routes);

$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (false !== $pos = strpos($uri, '?')) {
	$uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if (!empty($baseFolder) && strpos($uri, $baseFolder) === 0) {
	$uri = substr($uri, strlen($baseFolder));
}
if (empty($uri)) {
	$uri = '/';
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
	case FastRoute\Dispatcher::NOT_FOUND:
		http_response_code(404);
		ViewEngine::render('404.html.twig');
		break;

	case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
		http_response_code(405);
		echo "<h1>405 - Método no permitido</h1>";
		break;

	case FastRoute\Dispatcher::FOUND:
		if ($httpMethod === 'POST') {
			$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
			if (!Session::validateCsrf($token)) {
				$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
					|| (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
				if ($isAjax) {
					http_response_code(403);
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode(['success' => false, 'message' => 'Token CSRF inválido. Recarga la página e intenta de nuevo.']);
					exit;
				}
				http_response_code(403);
				echo "<h1>403 - Token CSRF inválido</h1><p>Recarga el formulario e intenta de nuevo.</p>";
				exit;
			}
		}

		$protectedControllers = [
			App\Controller\HomeController::class,
			App\Controller\PosController::class,
			App\Controller\TicketController::class,
			App\Controller\TripController::class,
			App\Controller\VehicleController::class,
			App\Controller\BranchController::class,
			App\Controller\PassengerController::class,
			App\Controller\ReportController::class,
			App\Controller\SettingController::class,
			App\Controller\UserController::class,
			App\Controller\ProfileController::class,
		];

		$handler = $routeInfo[1];
		$vars = $routeInfo[2];

		if (in_array($handler[0], $protectedControllers, true) && !AuthService::check()) {
			$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
				|| (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
			if ($isAjax) {
				http_response_code(401);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(['success' => false, 'message' => 'Sesión expirada. Inicia sesión nuevamente.']);
				exit;
			}
			header('Location: ' . ($baseFolder ?: '') . '/login');
			exit;
		}

		[$controllerClass, $method] = $handler;

		if (AuthService::check()) {
			$authUser = AuthService::user();
			$userRole = $authUser ? $authUser->role : ($_SESSION['user_role'] ?? 'seller');
			$_SESSION['user_role'] = $userRole;

			if ($userRole !== 'admin') {
				$forbiddenControllers = [
					App\Controller\ReportController::class,
					App\Controller\SettingController::class,
					App\Controller\UserController::class,
				];
				if (in_array($controllerClass, $forbiddenControllers, true)) {
					$_SESSION['error'] = 'No tienes permiso para acceder a este módulo.';
					header('Location: ' . ($baseFolder ?: '') . '/home');
					exit;
				}

				$readOnlyMap = [
					App\Controller\TripController::class => ['index', 'detail', 'manifest'],
					App\Controller\VehicleController::class => ['index'],
					App\Controller\BranchController::class => ['index'],
					App\Controller\PassengerController::class => ['index', 'searchAjax', 'new', 'create', 'edit', 'update'],
				];
				if (isset($readOnlyMap[$controllerClass]) && !in_array($method, $readOnlyMap[$controllerClass], true)) {
					$_SESSION['error'] = 'No tienes permiso para realizar esta acción.';
					header('Location: ' . ($baseFolder ?: '') . '/home');
					exit;
				}
			}
		}

		$controller = new $controllerClass();
		$controller->$method($vars);
		break;
}
