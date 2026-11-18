<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
	// Autenticación
	$r->addRoute('GET', '/', [App\Controller\AuthController::class, 'loginForm']);
	$r->addRoute('GET', '/login', [App\Controller\AuthController::class, 'loginForm']);
	$r->addRoute('POST', '/login', [App\Controller\AuthController::class, 'login']);
	$r->addRoute(['GET', 'POST'], '/logout', [App\Controller\AuthController::class, 'logout']);

	// Dashboard
	$r->addRoute('GET', '/home', [App\Controller\HomeController::class, 'index']);

	// Punto de Venta (POS)
	$r->addRoute('GET', '/pos', [App\Controller\PosController::class, 'index']);
	$r->addRoute('GET', '/pos/seats/{id:\d+}', [App\Controller\PosController::class, 'seatsAjax']);
	$r->addRoute('POST', '/pos/sell', [App\Controller\PosController::class, 'sell']);

	// Boletos Emitidos
	$r->addRoute('GET', '/tickets', [App\Controller\TicketController::class, 'index']);
	$r->addRoute('GET', '/tickets/print', [App\Controller\TicketController::class, 'printMultiple']);
	$r->addRoute('GET', '/tickets/{id:\d+}', [App\Controller\TicketController::class, 'detail']);
	$r->addRoute('GET', '/tickets/{id:\d+}/print', [App\Controller\TicketController::class, 'printTicket']);
	$r->addRoute('POST', '/tickets/{id:\d+}/cancel', [App\Controller\TicketController::class, 'cancel']);

	// Corridas (Trips)
	$r->addRoute('GET', '/trips', [App\Controller\TripController::class, 'index']);
	$r->addRoute('GET', '/trips/new', [App\Controller\TripController::class, 'new']);
	$r->addRoute('POST', '/trips/create', [App\Controller\TripController::class, 'create']);
	$r->addRoute('GET', '/trips/{id:\d+}', [App\Controller\TripController::class, 'detail']);
	$r->addRoute('GET', '/trips/{id:\d+}/edit', [App\Controller\TripController::class, 'edit']);
	$r->addRoute('POST', '/trips/{id:\d+}/update', [App\Controller\TripController::class, 'update']);
	$r->addRoute('POST', '/trips/{id:\d+}/status', [App\Controller\TripController::class, 'updateStatus']);
	$r->addRoute('GET', '/trips/{id:\d+}/manifest', [App\Controller\TripController::class, 'manifest']);
	$r->addRoute('POST', '/trips/{id:\d+}/delete', [App\Controller\TripController::class, 'delete']);

	// Vehículos
	$r->addRoute('GET', '/vehicles', [App\Controller\VehicleController::class, 'index']);
	$r->addRoute('GET', '/vehicles/new', [App\Controller\VehicleController::class, 'new']);
	$r->addRoute('POST', '/vehicles/create', [App\Controller\VehicleController::class, 'create']);
	$r->addRoute('GET', '/vehicles/{id:\d+}/edit', [App\Controller\VehicleController::class, 'edit']);
	$r->addRoute('POST', '/vehicles/{id:\d+}/update', [App\Controller\VehicleController::class, 'update']);
	$r->addRoute('POST', '/vehicles/{id:\d+}/delete', [App\Controller\VehicleController::class, 'delete']);

	// Terminales / Sucursales
	$r->addRoute('GET', '/branches', [App\Controller\BranchController::class, 'index']);
	$r->addRoute('GET', '/branches/new', [App\Controller\BranchController::class, 'new']);
	$r->addRoute('POST', '/branches/create', [App\Controller\BranchController::class, 'create']);
	$r->addRoute('GET', '/branches/{id:\d+}/edit', [App\Controller\BranchController::class, 'edit']);
	$r->addRoute('POST', '/branches/{id:\d+}/update', [App\Controller\BranchController::class, 'update']);
	$r->addRoute('POST', '/branches/{id:\d+}/delete', [App\Controller\BranchController::class, 'delete']);

	// Pasajeros
	$r->addRoute('GET', '/passengers', [App\Controller\PassengerController::class, 'index']);
	$r->addRoute('GET', '/passengers/search', [App\Controller\PassengerController::class, 'searchAjax']);
	$r->addRoute('GET', '/passengers/new', [App\Controller\PassengerController::class, 'new']);
	$r->addRoute('POST', '/passengers/create', [App\Controller\PassengerController::class, 'create']);
	$r->addRoute('GET', '/passengers/{id:\d+}/edit', [App\Controller\PassengerController::class, 'edit']);
	$r->addRoute('POST', '/passengers/{id:\d+}/update', [App\Controller\PassengerController::class, 'update']);
	$r->addRoute('POST', '/passengers/{id:\d+}/delete', [App\Controller\PassengerController::class, 'delete']);

	// Reportes (Solo Admin)
	$r->addRoute('GET', '/reports', [App\Controller\ReportController::class, 'index']);
	$r->addRoute('GET', '/reportbytrip', [App\Controller\ReportController::class, 'byTrip']);

	// Usuarios (Solo Admin)
	$r->addRoute('GET', '/users', [App\Controller\UserController::class, 'index']);
	$r->addRoute('GET', '/users/new', [App\Controller\UserController::class, 'new']);
	$r->addRoute('POST', '/users/create', [App\Controller\UserController::class, 'create']);
	$r->addRoute('GET', '/users/{id:\d+}/edit', [App\Controller\UserController::class, 'edit']);
	$r->addRoute('POST', '/users/{id:\d+}/update', [App\Controller\UserController::class, 'update']);
	$r->addRoute('POST', '/users/{id:\d+}/delete', [App\Controller\UserController::class, 'delete']);

	// Configuración (Solo Admin)
	$r->addRoute('GET', '/settings', [App\Controller\SettingController::class, 'index']);
	$r->addRoute('POST', '/settings/update', [App\Controller\SettingController::class, 'update']);

	// Perfil de usuario
	$r->addRoute('GET', '/profile', [App\Controller\ProfileController::class, 'index']);
	$r->addRoute('POST', '/profile/update', [App\Controller\ProfileController::class, 'update']);
};
