<?php

namespace App\Controller;

use VehicleData;
use ViewEngine;
use Req;
use Response;

class VehicleController {
	private string $baseFolder;

	public function __construct() {
		$this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
	}

	public function index(): void {
		ViewEngine::render('vehicles/index.html.twig', [
			'vehicles' => VehicleData::getAll(),
		]);
	}

	public function new(): void {
		ViewEngine::render('vehicles/new.html.twig');
	}

	public function create(): void {
		$plate = strtoupper(trim((string)Req::post('plate', '')));
		$name = trim((string)Req::post('name', ''));
		$type = (string)Req::post('type', 'van');
		$totalSeat = (int)Req::post('total_seat', 14);
		$isActive = isset($_POST['is_active']) ? 1 : 0;

		if ($plate === '' || $totalSeat <= 0) {
			$_SESSION['error'] = 'La placa y el número de asientos son obligatorios.';
			Response::redirect($this->baseFolder . '/vehicles/new');
		}

		try {
			$v = new VehicleData();
			$v->plate = $plate;
			$v->name = $name;
			$v->type = in_array($type, ['van', 'bus'], true) ? $type : 'van';
			$v->total_seat = $totalSeat;
			$v->is_active = $isActive;
			$v->add();
			$_SESSION['success'] = 'Vehículo registrado correctamente.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'Error al registrar vehículo (verifica que la placa no esté duplicada).';
		}
		Response::redirect($this->baseFolder . '/vehicles');
	}

	public function edit(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$vehicle = VehicleData::getById($id);
		if (!$vehicle) {
			$_SESSION['error'] = 'Vehículo no encontrado.';
			Response::redirect($this->baseFolder . '/vehicles');
		}
		ViewEngine::render('vehicles/edit.html.twig', ['vehicle' => $vehicle]);
	}

	public function update(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$v = VehicleData::getById($id);
		if (!$v) {
			$_SESSION['error'] = 'Vehículo no encontrado.';
			Response::redirect($this->baseFolder . '/vehicles');
		}

		$v->plate = strtoupper(trim((string)Req::post('plate', '')));
		$v->name = trim((string)Req::post('name', ''));
		$type = (string)Req::post('type', 'van');
		$v->type = in_array($type, ['van', 'bus'], true) ? $type : 'van';
		$v->total_seat = max(1, (int)Req::post('total_seat', 14));
		$v->is_active = isset($_POST['is_active']) ? 1 : 0;

		try {
			$v->update();
			$_SESSION['updated'] = 'Vehículo actualizado correctamente.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'No se pudo actualizar (placa duplicada).';
		}
		Response::redirect($this->baseFolder . '/vehicles');
	}

	public function delete(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		try {
			VehicleData::delById($id);
			$_SESSION['deleted'] = 'Vehículo eliminado.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'No se puede eliminar: tiene corridas asociadas.';
		}
		Response::redirect($this->baseFolder . '/vehicles');
	}
}
