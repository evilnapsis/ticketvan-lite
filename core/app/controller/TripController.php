<?php

namespace App\Controller;

use TripData;
use BranchData;
use VehicleData;
use TicketData;
use SettingData;
use ViewEngine;
use Req;
use Response;

class TripController {
	private string $baseFolder;

	public function __construct() {
		$this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
	}

	public function index(): void {
		$status = Req::get('status', '');
		$date = Req::get('date', '');
		$trips = TripData::getAllWithDetails($status ?: null, $date ?: null);

		ViewEngine::render('trips/index.html.twig', [
			'trips' => $trips,
			'status' => $status,
			'date' => $date,
		]);
	}

	public function new(): void {
		ViewEngine::render('trips/new.html.twig', [
			'branches' => BranchData::getAllActive(),
			'vehicles' => VehicleData::getAllActive(),
		]);
	}

	public function create(): void {
		$originId = (int)Req::post('origin_branch_id', 0);
		$destId = (int)Req::post('destination_branch_id', 0);
		$vehicleId = (int)Req::post('vehicle_id', 0);
		$departureAt = trim((string)Req::post('departure_at', ''));
		$price = (float)Req::post('price', 0);
		$status = (string)Req::post('status', 'scheduled');
		$notes = trim((string)Req::post('notes', ''));

		if ($originId <= 0 || $destId <= 0 || $originId === $destId || $vehicleId <= 0 || $departureAt === '') {
			$_SESSION['error'] = 'Verifica origen, destino distinto, vehículo y fecha/hora de salida.';
			Response::redirect($this->baseFolder . '/trips/new');
		}

		$departureAt = str_replace('T', ' ', $departureAt);
		if (strlen($departureAt) === 16) {
			$departureAt .= ':00';
		}

		$trip = new TripData();
		$trip->origin_branch_id = $originId;
		$trip->destination_branch_id = $destId;
		$trip->vehicle_id = $vehicleId;
		$trip->departure_at = $departureAt;
		$trip->price = max(0, $price);
		$trip->status = in_array($status, ['scheduled', 'boarding', 'departed', 'completed', 'cancelled'], true) ? $status : 'scheduled';
		$trip->notes = $notes;
		$trip->add();

		$_SESSION['success'] = 'Corrida programada correctamente.';
		Response::redirect($this->baseFolder . '/trips');
	}

	public function detail(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$trip = TripData::getByIdWithDetails($id);
		if (!$trip) {
			$_SESSION['error'] = 'Corrida no encontrada.';
			Response::redirect($this->baseFolder . '/trips');
		}
		$tickets = TicketData::getByTrip($id);
		$soldMap = TicketData::getSoldSeatsByTrip($id);

		ViewEngine::render('trips/detail.html.twig', [
			'trip' => $trip,
			'tickets' => $tickets,
			'sold_map' => $soldMap,
		]);
	}

	public function edit(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$trip = TripData::getById($id);
		if (!$trip) {
			$_SESSION['error'] = 'Corrida no encontrada.';
			Response::redirect($this->baseFolder . '/trips');
		}
		ViewEngine::render('trips/edit.html.twig', [
			'trip' => $trip,
			'branches' => BranchData::getAllActive(),
			'vehicles' => VehicleData::getAllActive(),
		]);
	}

	public function update(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$trip = TripData::getById($id);
		if (!$trip) {
			$_SESSION['error'] = 'Corrida no encontrada.';
			Response::redirect($this->baseFolder . '/trips');
		}

		$originId = (int)Req::post('origin_branch_id', 0);
		$destId = (int)Req::post('destination_branch_id', 0);
		$vehicleId = (int)Req::post('vehicle_id', 0);
		$departureAt = trim((string)Req::post('departure_at', ''));
		$price = (float)Req::post('price', 0);
		$status = (string)Req::post('status', 'scheduled');
		$notes = trim((string)Req::post('notes', ''));

		if ($originId <= 0 || $destId <= 0 || $originId === $destId || $vehicleId <= 0 || $departureAt === '') {
			$_SESSION['error'] = 'Verifica origen, destino distinto, vehículo y fecha/hora.';
			Response::redirect($this->baseFolder . '/trips/' . $id . '/edit');
		}

		$departureAt = str_replace('T', ' ', $departureAt);
		if (strlen($departureAt) === 16) {
			$departureAt .= ':00';
		}

		$trip->origin_branch_id = $originId;
		$trip->destination_branch_id = $destId;
		$trip->vehicle_id = $vehicleId;
		$trip->departure_at = $departureAt;
		$trip->price = max(0, $price);
		$trip->status = in_array($status, ['scheduled', 'boarding', 'departed', 'completed', 'cancelled'], true) ? $status : 'scheduled';
		$trip->notes = $notes;
		$trip->update();

		$_SESSION['updated'] = 'Corrida #' . $id . ' actualizada.';
		Response::redirect($this->baseFolder . '/trips');
	}

	public function updateStatus(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$status = (string)Req::post('status', '');
		if (in_array($status, ['scheduled', 'boarding', 'departed', 'completed', 'cancelled'], true)) {
			TripData::updateStatus($id, $status);
			$_SESSION['updated'] = 'Estado de corrida actualizado.';
		}
		Response::redirect($this->baseFolder . '/trips/' . $id);
	}

	public function manifest(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$trip = TripData::getByIdWithDetails($id);
		if (!$trip) {
			http_response_code(404);
			echo "Corrida no encontrada";
			exit;
		}
		$tickets = array_filter(TicketData::getByTrip($id), fn($t) => $t->status === 'sold');
		ViewEngine::render('trips/manifest.html.twig', [
			'trip' => $trip,
			'tickets' => $tickets,
			'settings' => SettingData::getMap(),
		]);
	}

	public function delete(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		try {
			TripData::delById($id);
			$_SESSION['deleted'] = 'Corrida eliminada.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'No se puede eliminar una corrida que ya tiene boletos emitidos.';
		}
		Response::redirect($this->baseFolder . '/trips');
	}
}
