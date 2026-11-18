<?php

namespace App\Controller;

use App\Service\AuthService;
use TicketData;
use SettingData;
use ViewEngine;
use Req;
use Response;

class TicketController {
	private string $baseFolder;

	public function __construct() {
		$this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
	}

	public function index(): void {
		$user = AuthService::user();
		$isAdmin = $user && $user->role === 'admin';

		$startDate = Req::get('start_date', date('Y-m-d', strtotime('-7 days')));
		$endDate = Req::get('end_date', date('Y-m-d'));
		$status = Req::get('status', '');

		$tickets = TicketData::getAllWithDetails(
			$startDate ?: null,
			$endDate ?: null,
			$status ?: null,
			$isAdmin ? null : (int)$user->id
		);

		ViewEngine::render('tickets/index.html.twig', [
			'tickets' => $tickets,
			'start_date' => $startDate,
			'end_date' => $endDate,
			'status' => $status,
		]);
	}

	public function detail(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$ticket = TicketData::getByIdWithDetails($id);
		if (!$ticket) {
			$_SESSION['error'] = 'Boleto no encontrado.';
			Response::redirect($this->baseFolder . '/tickets');
		}
		ViewEngine::render('tickets/detail.html.twig', [
			'ticket' => $ticket,
			'settings' => SettingData::getMap(),
		]);
	}

	public function printTicket(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$ticket = TicketData::getByIdWithDetails($id);
		if (!$ticket) {
			$_SESSION['error'] = 'Boleto no encontrado.';
			Response::redirect($this->baseFolder . '/tickets');
		}
		$this->renderThermalPdf([$ticket]);
	}

	public function printMultiple(): void {
		$idsParam = (string)Req::get('ids', Req::get('id', ''));
		$idList = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsParam)))));

		$tickets = [];
		foreach ($idList as $id) {
			$tk = TicketData::getByIdWithDetails($id);
			if ($tk) {
				$tickets[] = $tk;
			}
		}

		if (empty($tickets)) {
			$_SESSION['error'] = 'No se encontraron boletos para imprimir.';
			Response::redirect($this->baseFolder . '/pos');
		}

		$this->renderThermalPdf($tickets);
	}

	private function pdfText(string $text): string {
		$converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
		return $converted !== false ? $converted : $text;
	}

	/**
	 * Impresión de Boletos en Formato Térmico de 80 mm con FPDF.
	 * Genera una tira continua con todos los boletos solicitados.
	 */
	private function renderThermalPdf(array $tickets): void {
		$settings = SettingData::getMap();
		$companyName = $settings['company_name'] ?? 'TicketVan Lite';
		$companyRfc = $settings['company_rfc'] ?? '';
		$companyPhone = $settings['company_phone'] ?? '';
		$companyAddress = $settings['company_address'] ?? '';
		$ticketNote = $settings['ticket_note'] ?? 'Conserve este boleto. Es su comprobante y seguro de viajero. Presentarse 15 minutos antes de la salida.';

		$totalSalePrice = 0.0;
		foreach ($tickets as $tk) {
			$totalSalePrice += (float)$tk->price;
		}

		if (!class_exists('FPDF')) {
			if (file_exists(ROOT . '/core/fpdf/fpdf.php')) {
				require_once ROOT . '/core/fpdf/fpdf.php';
			}
		}

		$totalCount = count($tickets);
		$addrLines = !empty($companyAddress) ? max(1, (int)ceil(strlen($companyAddress) / 45)) : 0;
		$noteLines = !empty($ticketNote) ? max(1, (int)ceil(strlen($ticketNote) / 50)) : 0;
		$ticketItemHeight = 76 + ($addrLines * 2.8) + ($noteLines * 2.6) + 6;
		$totalStripHeight = max(100, (int)round(($totalCount * $ticketItemHeight) + 8));

		$pdf = new \FPDF('P', 'mm', [80, $totalStripHeight]);
		$pdf->SetMargins(4, 4, 4);
		$pdf->SetAutoPageBreak(false);
		$pdf->AddPage();

		foreach ($tickets as $index => $tk) {
			// 1. Encabezado de empresa
			$pdf->SetFont('Arial', 'B', 10);
			$pdf->Cell(72, 4.5, $this->pdfText($companyName), 0, 1, 'C');

			if (!empty($companyRfc)) {
				$pdf->SetFont('Arial', 'B', 7);
				$pdf->Cell(72, 3.2, $this->pdfText("RFC: " . $companyRfc), 0, 1, 'C');
			}
			if (!empty($companyAddress)) {
				$pdf->SetFont('Arial', '', 6.5);
				$pdf->MultiCell(72, 2.8, $this->pdfText($companyAddress), 0, 'C');
			}
			if (!empty($companyPhone)) {
				$pdf->SetFont('Arial', '', 7);
				$pdf->Cell(72, 3.2, $this->pdfText("Tel: " . $companyPhone), 0, 1, 'C');
			}

			$pdf->SetFont('Arial', '', 7);
			$pdf->Cell(72, 2.5, "----------------------------------------------------------------", 0, 1, 'C');

			// 2. Datos de emisión
			$pdf->SetFont('Arial', 'B', 8);
			$statusLabel = $tk->status === 'sold' ? 'VIGENTE' : 'CANCELADO';
			$pdf->Cell(36, 3.8, $this->pdfText("BOLETO #" . $tk->id), 0, 0, 'L');
			$pdf->Cell(36, 3.8, $this->pdfText($statusLabel), 0, 1, 'R');

			$pdf->SetFont('Arial', '', 7);
			$pdf->Cell(36, 3.2, $this->pdfText("FECHA: " . substr((string)$tk->created_at, 0, 16)), 0, 0, 'L');
			$pdf->Cell(36, 3.2, $this->pdfText("CAJERO: " . substr((string)($tk->seller_name ?: 'Taquilla'), 0, 13)), 0, 1, 'R');

			$pdf->Cell(72, 2.5, "----------------------------------------------------------------", 0, 1, 'C');

			// 3. Trayecto y salida
			$pdf->SetFont('Arial', 'B', 8.5);
			$pdf->MultiCell(72, 4, $this->pdfText(strtoupper($tk->origin_name) . " -> " . strtoupper($tk->destination_name)), 0, 'C');

			$pdf->SetFont('Arial', 'B', 8);
			$pdf->Cell(72, 3.8, $this->pdfText("SALIDA: " . substr((string)$tk->departure_at, 0, 16)), 0, 1, 'C');

			if (!empty($tk->vehicle_plate)) {
				$pdf->SetFont('Arial', '', 7);
				$pdf->Cell(72, 3.2, $this->pdfText("UNIDAD: " . ($tk->vehicle_name ?: 'Unidad') . " (" . $tk->vehicle_plate . ")"), 0, 1, 'C');
			}

			$pdf->Cell(72, 2.5, "----------------------------------------------------------------", 0, 1, 'C');

			// 4. Asiento destacado
			$pdf->Ln(0.5);
			$pdf->SetFont('Arial', 'B', 14);
			$pdf->Cell(72, 7.5, $this->pdfText("ASIENTO #" . $tk->seat_number), 1, 1, 'C');
			$pdf->Ln(1);

			// Pasajero
			$pdf->SetFont('Arial', 'B', 7.5);
			$pdf->Cell(20, 3.6, "PASAJERO: ", 0, 0, 'L');
			$pdf->SetFont('Arial', '', 7.5);
			$pdf->Cell(52, 3.6, $this->pdfText((string)$tk->passenger_name), 0, 1, 'L');

			if (!empty($tk->passenger_phone)) {
				$pdf->SetFont('Arial', 'B', 7.5);
				$pdf->Cell(20, 3.4, "TELEFONO: ", 0, 0, 'L');
				$pdf->SetFont('Arial', '', 7.5);
				$pdf->Cell(52, 3.4, $this->pdfText((string)$tk->passenger_phone), 0, 1, 'L');
			}

			// Método de pago
			$payLabel = $tk->payment_method === 'cash' ? 'EFECTIVO' : ($tk->payment_method === 'card' ? 'TARJETA' : 'TRANSFERENCIA');
			$pdf->SetFont('Arial', 'B', 7.5);
			$pdf->Cell(20, 3.4, "PAGO: ", 0, 0, 'L');
			$pdf->SetFont('Arial', '', 7.5);
			$pdf->Cell(52, 3.4, $this->pdfText($payLabel), 0, 1, 'L');

			// Costo
			$pdf->SetFont('Arial', 'B', 10);
			$pdf->Cell(36, 5, "COSTO BOLETO:", 0, 0, 'L');
			$pdf->Cell(36, 5, "$" . number_format((float)$tk->price, 2) . " MXN", 0, 1, 'R');

			$pdf->SetFont('Arial', '', 7);
			$pdf->Cell(72, 2.5, "================================================", 0, 1, 'C');

			// 5. Leyenda
			$pdf->SetFont('Arial', '', 6);
			$pdf->MultiCell(72, 2.6, $this->pdfText($ticketNote), 0, 'C');

			if ($totalCount > 1) {
				$pdf->SetFont('Arial', 'I', 6.5);
				$pdf->Cell(72, 3.2, $this->pdfText("Boleto " . ($index + 1) . " de " . $totalCount . " (Total venta: $" . number_format($totalSalePrice, 2) . " MXN)"), 0, 1, 'C');
			}

			$pdf->SetFont('Arial', 'B', 8);
			$pdf->Cell(72, 3.8, $this->pdfText("*TVL-" . $tk->id . "-T" . $tk->trip_id . "-S" . $tk->seat_number . "*"), 0, 1, 'C');

			$pdf->SetFont('Arial', '', 6.5);
			$pdf->Cell(72, 3, $this->pdfText("¡BUEN VIAJE! GRACIAS POR SU PREFERENCIA"), 0, 1, 'C');

			$pdf->SetFont('Arial', 'B', 7);
			$pdf->Cell(72, 3, $this->pdfText("- - - - - - - - - CORTE AQUI - - - - - - - - -"), 0, 1, 'C');

			if ($index < $totalCount - 1) {
				$pdf->Ln(4);
			}
		}

		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		$filename = $totalCount === 1
			? "Ticket_Boleto_" . $tickets[0]->id . ".pdf"
			: "Tickets_Venta_" . implode('_', array_map(fn($t) => $t->id, $tickets)) . ".pdf";
		$pdf->Output('I', $filename);
		exit;
	}

	public function cancel(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		if (TicketData::cancel($id)) {
			$_SESSION['updated'] = 'Boleto #' . $id . ' cancelado. El asiento fue liberado.';
		} else {
			$_SESSION['error'] = 'No se pudo cancelar el boleto (quizá ya estaba cancelado).';
		}
		$referer = $_SERVER['HTTP_REFERER'] ?? ($this->baseFolder . '/tickets');
		Response::redirect($referer);
	}
}
