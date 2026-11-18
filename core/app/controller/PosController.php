<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\TicketService;
use TripData;
use TicketData;
use PassengerData;
use ViewEngine;
use Req;
use Response;

class PosController {
	public function index(): void {
		$trips = TripData::getUpcomingForPos();
		$selectedTripId = (int)Req::get('trip_id', 0);
		if ($selectedTripId === 0 && !empty($trips)) {
			$selectedTripId = (int)$trips[0]->id;
		}

		$selectedTrip = $selectedTripId > 0 ? TripData::getByIdWithDetails($selectedTripId) : null;
		$soldSeats = $selectedTrip ? TicketData::getSoldSeatsByTrip($selectedTrip->id) : [];
		$passengers = PassengerData::getAll();

		ViewEngine::render('pos/index.html.twig', [
			'trips' => $trips,
			'selected_trip' => $selectedTrip,
			'sold_seats' => $soldSeats,
			'passengers' => $passengers,
		]);
	}

	public function seatsAjax(array $vars): void {
		$tripId = (int)($vars['id'] ?? 0);
		$trip = TripData::getByIdWithDetails($tripId);
		if (!$trip) {
			Response::json(['success' => false, 'message' => 'Corrida no encontrada'], 404);
		}
		$sold = TicketData::getSoldSeatsByTrip($tripId);
		Response::json([
			'success' => true,
			'trip' => [
				'id' => (int)$trip->id,
				'origin_name' => $trip->origin_name,
				'destination_name' => $trip->destination_name,
				'departure_at' => $trip->departure_at,
				'price' => (float)$trip->price,
				'status' => $trip->status,
				'vehicle_name' => $trip->vehicle_name,
				'vehicle_plate' => $trip->vehicle_plate,
				'vehicle_type' => $trip->vehicle_type,
				'total_seat' => (int)$trip->total_seat,
				'sold_seats' => (int)$trip->sold_seats,
				'available_seats' => (int)$trip->available_seats,
			],
			'sold_map' => $sold,
		]);
	}

	public function sell(): void {
		$user = AuthService::user();
		if (!$user) {
			Response::json(['success' => false, 'message' => 'Sesión no válida'], 401);
		}

		$tripId = (int)Req::post('trip_id', 0);
		$seatsRaw = $_POST['seats'] ?? [];
		if (!is_array($seatsRaw)) {
			$seatsRaw = array_filter(explode(',', (string)$seatsRaw));
		}
		$passengerId = (int)Req::post('passenger_id', 0) ?: null;
		$passengerName = trim((string)Req::post('passenger_name', ''));
		$passengerPhone = trim((string)Req::post('passenger_phone', ''));
		$paymentMethod = (string)Req::post('payment_method', 'cash');

		if ($passengerId && $passengerName === '') {
			$p = PassengerData::getById($passengerId);
			if ($p) {
				$passengerName = $p->getFullName();
				if ($passengerPhone === '') {
					$passengerPhone = (string)$p->phone;
				}
			}
		}

		$result = TicketService::sellSeats(
			$tripId,
			$seatsRaw,
			$passengerName,
			$passengerPhone,
			$passengerId,
			$paymentMethod,
			(int)$user->id
		);

		Response::json($result, $result['success'] ? 200 : 422);
	}
}
