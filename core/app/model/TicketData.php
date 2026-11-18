<?php

#[\AllowDynamicProperties]
class TicketData {
	public static $tablename = "ticket";

	public $id;
	public $trip_id;
	public $seat_number;
	public $passenger_id;
	public $passenger_name;
	public $passenger_phone;
	public $price;
	public $payment_method;
	public $status;
	public $user_id;
	public $created_at;
	public $updated_at;
	public $cancelled_at;
	public $active_seat;

	// Navegación / JOINs
	public $trip = null;
	public $passenger = null;
	public $user = null;
	public $departure_at = "";
	public $origin_name = "";
	public $destination_name = "";
	public $vehicle_name = "";
	public $vehicle_plate = "";
	public $seller_name = "";

	public function __construct() {
		$this->trip_id = 0;
		$this->seat_number = 0;
		$this->passenger_id = null;
		$this->passenger_name = "";
		$this->passenger_phone = "";
		$this->price = 0.00;
		$this->payment_method = "cash";
		$this->status = "sold";
		$this->user_id = 0;
		$this->created_at = date("Y-m-d H:i:s");
		$this->updated_at = date("Y-m-d H:i:s");
		$this->cancelled_at = null;
		$this->active_seat = null;
	}

	private static function db(): \PDO {
		return Database::getPdo();
	}

	public function add(): int {
		$stmt = self::db()->prepare(
			"INSERT INTO " . self::$tablename . " (trip_id, seat_number, passenger_id, passenger_name, passenger_phone, price, payment_method, status, user_id) " .
			"VALUES (:trip_id, :seat_number, :passenger_id, :passenger_name, :passenger_phone, :price, :payment_method, :status, :user_id)"
		);
		$stmt->execute([
			'trip_id' => (int)$this->trip_id,
			'seat_number' => (int)$this->seat_number,
			'passenger_id' => $this->passenger_id ?: null,
			'passenger_name' => $this->passenger_name,
			'passenger_phone' => $this->passenger_phone ?: null,
			'price' => (float)$this->price,
			'payment_method' => $this->payment_method ?: 'cash',
			'status' => $this->status ?: 'sold',
			'user_id' => (int)$this->user_id,
		]);
		$this->id = (int)self::db()->lastInsertId();
		return $this->id;
	}

	public static function cancel(int $id): bool {
		$stmt = self::db()->prepare(
			"UPDATE " . self::$tablename . " SET status = 'cancelled', cancelled_at = NOW() WHERE id = :id AND status = 'sold'"
		);
		$stmt->execute(['id' => $id]);
		return $stmt->rowCount() > 0;
	}

	public static function getById($id): ?self {
		$stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE id = :id LIMIT 1");
		$stmt->execute(['id' => (int)$id]);
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
		return $stmt->fetch() ?: null;
	}

	private static function baseSelectSql(): string {
		return "SELECT tk.*, " .
			"tr.departure_at, " .
			"ob.name AS origin_name, db.name AS destination_name, " .
			"v.name AS vehicle_name, v.plate AS vehicle_plate, " .
			"u.name AS seller_name " .
			"FROM " . self::$tablename . " tk " .
			"INNER JOIN trip tr ON tr.id = tk.trip_id " .
			"INNER JOIN branch ob ON ob.id = tr.origin_branch_id " .
			"INNER JOIN branch db ON db.id = tr.destination_branch_id " .
			"INNER JOIN vehicle v ON v.id = tr.vehicle_id " .
			"INNER JOIN `user` u ON u.id = tk.user_id ";
	}

	public static function getByIdWithDetails(int $id): ?self {
		$sql = self::baseSelectSql() . "WHERE tk.id = :id LIMIT 1";
		$stmt = self::db()->prepare($sql);
		$stmt->execute(['id' => $id]);
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
		return $stmt->fetch() ?: null;
	}

	public static function getByTrip(int $tripId): array {
		$sql = self::baseSelectSql() . "WHERE tk.trip_id = :trip_id ORDER BY tk.seat_number ASC, tk.id DESC";
		$stmt = self::db()->prepare($sql);
		$stmt->execute(['trip_id' => $tripId]);
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function getSoldSeatsByTrip(int $tripId): array {
		$stmt = self::db()->prepare(
			"SELECT id, seat_number, passenger_name, passenger_phone, price, payment_method " .
			"FROM " . self::$tablename . " WHERE trip_id = :trip_id AND status = 'sold' ORDER BY seat_number ASC"
		);
		$stmt->execute(['trip_id' => $tripId]);
		$rows = $stmt->fetchAll();
		$map = [];
		foreach ($rows as $row) {
			$map[(int)$row->seat_number] = $row;
		}
		return $map;
	}

	public static function getAllWithDetails(?string $startDate = null, ?string $endDate = null, ?string $status = null, ?int $userId = null): array {
		$where = [];
		$params = [];
		if ($startDate !== null && $startDate !== '') {
			$where[] = "DATE(tk.created_at) >= :start_date";
			$params['start_date'] = $startDate;
		}
		if ($endDate !== null && $endDate !== '') {
			$where[] = "DATE(tk.created_at) <= :end_date";
			$params['end_date'] = $endDate;
		}
		if ($status !== null && $status !== '') {
			$where[] = "tk.status = :status";
			$params['status'] = $status;
		}
		if ($userId !== null && $userId > 0) {
			$where[] = "tk.user_id = :user_id";
			$params['user_id'] = $userId;
		}
		$sql = self::baseSelectSql();
		if ($where) {
			$sql .= "WHERE " . implode(" AND ", $where) . " ";
		}
		$sql .= "ORDER BY tk.id DESC LIMIT 500";
		$stmt = self::db()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function getTodaySummary(?int $userId = null): object {
		$sql = "SELECT COUNT(*) AS total_tickets, COALESCE(SUM(price), 0) AS total_amount " .
			"FROM " . self::$tablename . " WHERE status = 'sold' AND DATE(created_at) = CURDATE()";
		$params = [];
		if ($userId !== null && $userId > 0) {
			$sql .= " AND user_id = :uid";
			$params['uid'] = $userId;
		}
		$stmt = self::db()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetch() ?: (object)['total_tickets' => 0, 'total_amount' => 0];
	}

	public static function getLast30DaysChart(): array {
		$stmt = self::db()->query(
			"SELECT DATE(created_at) AS sale_date, COUNT(*) AS total_tickets, COALESCE(SUM(price), 0) AS total_amount " .
			"FROM " . self::$tablename . " " .
			"WHERE status = 'sold' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) " .
			"GROUP BY DATE(created_at) ORDER BY sale_date ASC"
		);
		return $stmt->fetchAll();
	}

	public static function getReportSummary(string $startDate, string $endDate): array {
		$stmt = self::db()->prepare(
			"SELECT payment_method, COUNT(*) AS total_tickets, COALESCE(SUM(price), 0) AS total_amount " .
			"FROM " . self::$tablename . " " .
			"WHERE status = 'sold' AND DATE(created_at) BETWEEN :s AND :e " .
			"GROUP BY payment_method"
		);
		$stmt->execute(['s' => $startDate, 'e' => $endDate]);
		return $stmt->fetchAll();
	}

	public static function getReportByTrip(string $startDate, string $endDate): array {
		$stmt = self::db()->prepare(
			"SELECT tr.id, tr.departure_at, tr.price, tr.status, " .
			"ob.name AS origin_name, db.name AS destination_name, " .
			"v.plate AS vehicle_plate, v.name AS vehicle_name, v.total_seat, " .
			"COUNT(CASE WHEN tk.status = 'sold' THEN 1 END) AS sold_tickets, " .
			"COALESCE(SUM(CASE WHEN tk.status = 'sold' THEN tk.price ELSE 0 END), 0) AS total_amount " .
			"FROM trip tr " .
			"INNER JOIN branch ob ON ob.id = tr.origin_branch_id " .
			"INNER JOIN branch db ON db.id = tr.destination_branch_id " .
			"INNER JOIN vehicle v ON v.id = tr.vehicle_id " .
			"LEFT JOIN ticket tk ON tk.trip_id = tr.id " .
			"WHERE DATE(tr.departure_at) BETWEEN :s AND :e " .
			"GROUP BY tr.id " .
			"ORDER BY tr.departure_at DESC"
		);
		$stmt->execute(['s' => $startDate, 'e' => $endDate]);
		return $stmt->fetchAll();
	}
}
