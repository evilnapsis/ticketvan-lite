<?php

namespace App\Controller;

use App\Service\AuthService;
use TripData;
use TicketData;
use ViewEngine;

class HomeController {
	public function index(): void {
		$user = AuthService::user();
		$isAdmin = $user && $user->role === 'admin';
		$filterUserId = $isAdmin ? null : ($user ? (int)$user->id : null);

		$todaySummary = TicketData::getTodaySummary($filterUserId);
		$todayTrips = TripData::getTodayTrips();
		$upcomingTrips = TripData::getUpcomingForPos();
		$chartRaw = TicketData::getLast30DaysChart();

		$chartLabels = [];
		$chartAmounts = [];
		$chartTickets = [];
		$mapByDate = [];
		foreach ($chartRaw as $row) {
			$mapByDate[$row->sale_date] = $row;
		}
		for ($i = 29; $i >= 0; $i--) {
			$d = date('Y-m-d', strtotime("-{$i} days"));
			$chartLabels[] = date('d/m', strtotime($d));
			$chartAmounts[] = isset($mapByDate[$d]) ? (float)$mapByDate[$d]->total_amount : 0;
			$chartTickets[] = isset($mapByDate[$d]) ? (int)$mapByDate[$d]->total_tickets : 0;
		}

		ViewEngine::render('home/index.html.twig', [
			'today_tickets' => (int)$todaySummary->total_tickets,
			'today_amount' => (float)$todaySummary->total_amount,
			'today_trips_count' => count($todayTrips),
			'upcoming_trips_count' => count($upcomingTrips),
			'today_trips' => $todayTrips,
			'chart_labels' => json_encode($chartLabels),
			'chart_amounts' => json_encode($chartAmounts),
			'chart_tickets' => json_encode($chartTickets),
		]);
	}
}
