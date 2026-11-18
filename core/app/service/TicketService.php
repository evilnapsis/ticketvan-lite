<?php

namespace App\Service;

use Database;
use TripData;
use TicketData;

class TicketService {
	/**
	 * Emite uno o varios boletos de forma atómica para una corrida.
	 * Retorna ['success' => bool, 'ticket_ids' => int[], 'message' => string]
	 */
	public static function sellSeats(
		int $tripId,
		array $seats,
		string $passengerName,
		?string $passengerPhone,
		?int $passengerId,
		string $paymentMethod,
		int $userId
	): array {
		$trip = TripData::getByIdWithDetails($tripId);
		if (!$trip) {
			return ['success' => false, 'ticket_ids' => [], 'message' => 'La corrida seleccionada no existe.'];
		}
		if (!in_array($trip->status, ['scheduled', 'boarding'], true)) {
			return ['success' => false, 'ticket_ids' => [], 'message' => 'Esta corrida ya no tiene venta abierta.'];
		}

		$cleanSeats = array_values(array_unique(array_map('intval', $seats)));
		$cleanSeats = array_filter($cleanSeats, fn($s) => $s >= 1 && $s <= (int)$trip->total_seat);
		if (empty($cleanSeats)) {
			return ['success' => false, 'ticket_ids' => [], 'message' => 'Selecciona al menos un asiento válido.'];
		}

		$passengerName = trim($passengerName);
		if ($passengerName === '') {
			return ['success' => false, 'ticket_ids' => [], 'message' => 'El nombre del pasajero es obligatorio.'];
		}

		if (!in_array($paymentMethod, ['cash', 'card', 'transfer'], true)) {
			$paymentMethod = 'cash';
		}

		$pdo = Database::getPdo();
		$ticketIds = [];

		try {
			$pdo->beginTransaction();

			foreach ($cleanSeats as $seat) {
				$ticket = new TicketData();
				$ticket->trip_id = $tripId;
				$ticket->seat_number = $seat;
				$ticket->passenger_id = $passengerId ?: null;
				$ticket->passenger_name = $passengerName;
				$ticket->passenger_phone = $passengerPhone ?: null;
				$ticket->price = (float)$trip->price;
				$ticket->payment_method = $paymentMethod;
				$ticket->status = 'sold';
				$ticket->user_id = $userId;
				$ticketIds[] = $ticket->add();
			}

			$pdo->commit();
			return [
				'success' => true,
				'ticket_ids' => $ticketIds,
				'message' => count($ticketIds) === 1
					? 'Boleto emitido correctamente.'
					: count($ticketIds) . ' boletos emitidos correctamente.'
			];
		} catch (\PDOException $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			if ($e->getCode() === '23000') {
				return ['success' => false, 'ticket_ids' => [], 'message' => 'Uno o más asientos seleccionados ya fueron vendidos. Actualiza el mapa.'];
			}
			return ['success' => false, 'ticket_ids' => [], 'message' => 'Error al emitir boleto: ' . $e->getMessage()];
		}
	}
}
