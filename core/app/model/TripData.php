<?php

#[\AllowDynamicProperties]
class TripData {
	public static $tablename = "trip";

	public $id;
	public $origin_branch_id;
	public $destination_branch_id;
	public $vehicle_id;
	public $departure_at;
	public $price;
	public $status;
	public $notes;
	public $created_at;
	public $updated_at;

	// Navegación / JOINs
	public $origin_branch = null;
	public $destination_branch = null;
	public $vehicle = null;
	public $origin_name = "";
	public $origin_city = "";
	public $destination_name = "";
	public $destination_city = "";
	public $vehicle_name = "";
	public $vehicle_plate = "";
	public $vehicle_type = "van";
	public $total_seat = 0;
	public $sold_seats = 0;
	public $available_seats = 0;

	public function __construct() {
		$this->origin_branch_id = 0;
		$this->destination_branch_id = 0;
		$this->vehicle_id = 0;
		$this->departure_at = date("Y-m-d H:i:s");
		$this->price = 0.00;
		$this->status = "scheduled";
		$this->notes = "";
		$this->created_at = date("Y-m-d H:i:s");
		$this->updated_at = date("Y-m-d H:i:s");
	}

	private static function db(): \PDO {
		return Database::getPdo();
	}

	public function add(): int {
		$stmt = self::db()->prepare(
			"INSERT INTO " . self::$tablename . " (origin_branch_id, destination_branch_id, vehicle_id, departure_at, price, status, notes) " .
			"VALUES (:origin_branch_id, :destination_branch_id, :vehicle_id, :departure_at, :price, :status, :notes)"
		);
		$stmt->execute([
			'origin_branch_id' => (int)$this->origin_branch_id,
			'destination_branch_id' => (int)$this->destination_branch_id,
			'vehicle_id' => (int)$this->vehicle_id,
			'departure_at' => $this->departure_at,
			'price' => (float)$this->price,
			'status' => $this->status ?: 'scheduled',
			'notes' => $this->notes ?: null,
		]);
		$this->id = (int)self::db()->lastInsertId();
		return $this->id;
	}

	public function update(): bool {
		$stmt = self::db()->prepare(
			"UPDATE " . self::$tablename . " SET origin_branch_id = :origin_branch_id, " .
			"destination_branch_id = :destination_branch_id, vehicle_id = :vehicle_id, " .
			"departure_at = :departure_at, price = :price, status = :status, notes = :notes WHERE id = :id"
		);
		return $stmt->execute([
			'origin_branch_id' => (int)$this->origin_branch_id,
			'destination_branch_id' => (int)$this->destination_branch_id,
			'vehicle_id' => (int)$this->vehicle_id,
			'departure_at' => $this->departure_at,
			'price' => (float)$this->price,
			'status' => $this->status,
			'notes' => $this->notes ?: null,
			'id' => (int)$this->id,
		]);
	}

	public static function updateStatus(int $id, string $status): bool {
		$stmt = self::db()->prepare("UPDATE " . self::$tablename . " SET status = :status WHERE id = :id");
		return $stmt->execute(['status' => $status, 'id' => $id]);
	}

	public static function getById($id): ?self {
		$stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE id = :id LIMIT 1");
		$stmt->execute(['id' => (int)$id]);
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
		return $stmt->fetch() ?: null;
	}

	private static function baseSelectSql(): string {
		return "SELECT t.*, " .
			"ob.name AS origin_name, ob.city AS origin_city, " .
			"db.name AS destination_name, db.city AS destination_city, " .
			"v.name AS vehicle_name, v.plate AS vehicle_plate, v.type AS vehicle_type, v.total_seat AS total_seat, " .
			"COALESCE(tk.sold_count, 0) AS sold_seats, " .
			"GREATEST(0, v.total_seat - COALESCE(tk.sold_count, 0)) AS available_seats " .
			"FROM " . self::$tablename . " t " .
			"INNER JOIN branch ob ON ob.id = t.origin_branch_id " .
			"INNER JOIN branch db ON db.id = t.destination_branch_id " .
			"INNER JOIN vehicle v ON v.id = t.vehicle_id " .
			"LEFT JOIN (SELECT trip_id, COUNT(*) AS sold_count FROM ticket WHERE status = 'sold' GROUP BY trip_id) tk ON tk.trip_id = t.id ";
	}

	public static function getByIdWithDetails(int $id): ?self {
		$sql = self::baseSelectSql() . "WHERE t.id = :id LIMIT 1";
		$stmt = self::db()->prepare($sql);
		$stmt->execute(['id' => $id]);
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
		return $stmt->fetch() ?: null;
	}

	public static function getAllWithDetails(?string $status = null, ?string $date = null): array {
		$where = [];
		$params = [];
		if ($status !== null && $status !== '') {
			$where[] = "t.status = :status";
			$params['status'] = $status;
		}
		if ($date !== null && $date !== '') {
			$where[] = "DATE(t.departure_at) = :dep_date";
			$params['dep_date'] = $date;
		}
		$sql = self::baseSelectSql();
		if ($where) {
			$sql .= "WHERE " . implode(" AND ", $where) . " ";
		}
		$sql .= "ORDER BY t.departure_at DESC, t.id DESC";

		$stmt = self::db()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function getUpcomingForPos(): array {
		$sql = self::baseSelectSql() .
			"WHERE t.status IN ('scheduled', 'boarding') " .
			"ORDER BY t.departure_at ASC";
		$stmt = self::db()->query($sql);
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function getTodayTrips(): array {
		$sql = self::baseSelectSql() .
			"WHERE DATE(t.departure_at) = CURDATE() " .
			"ORDER BY t.departure_at ASC";
		$stmt = self::db()->query($sql);
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function delById($id): bool {
		$stmt = self::db()->prepare("DELETE FROM " . self::$tablename . " WHERE id = :id");
		return $stmt->execute(['id' => (int)$id]);
	}
}
