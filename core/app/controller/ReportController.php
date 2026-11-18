<?php

namespace App\Controller;

use TicketData;
use ViewEngine;
use Req;

class ReportController {
	public function index(): void {
		$startDate = Req::get('start_date', date('Y-m-d', strtotime('-29 days')));
		$endDate = Req::get('end_date', date('Y-m-d'));

		$tickets = TicketData::getAllWithDetails($startDate, $endDate, 'sold');
		$summary = TicketData::getReportSummary($startDate, $endDate);

		$grandTotal = 0.0;
		$grandTickets = 0;
		foreach ($summary as $row) {
			$grandTotal += (float)$row->total_amount;
			$grandTickets += (int)$row->total_tickets;
		}

		ViewEngine::render('reports/index.html.twig', [
			'start_date' => $startDate,
			'end_date' => $endDate,
			'tickets' => $tickets,
			'summary' => $summary,
			'grand_total' => $grandTotal,
			'grand_tickets' => $grandTickets,
		]);
	}

	public function byTrip(): void {
		$startDate = Req::get('start_date', date('Y-m-d', strtotime('-29 days')));
		$endDate = Req::get('end_date', date('Y-m-d', strtotime('+7 days')));

		$rows = TicketData::getReportByTrip($startDate, $endDate);

		$grandTotal = 0.0;
		$grandTickets = 0;
		foreach ($rows as $row) {
			$grandTotal += (float)$row->total_amount;
			$grandTickets += (int)$row->sold_tickets;
		}

		ViewEngine::render('reports/by_trip.html.twig', [
			'start_date' => $startDate,
			'end_date' => $endDate,
			'rows' => $rows,
			'grand_total' => $grandTotal,
			'grand_tickets' => $grandTickets,
		]);
	}
}
