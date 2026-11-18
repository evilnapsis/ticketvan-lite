<?php

#[\AllowDynamicProperties]
class VehicleData {
	public static $tablename = "vehicle";

	public $id;
	public $plate;
	public $name;
	public $type;
	public $total_seat;
	public $is_active;
	public $created_at;
	public $updated_at;

	public function __construct() {
		$this->plate = "";
		$this->name = "";
		$this->type = "van";
		$this->total_seat = 14;
		$this->is_active = 1;
		$this->created_at = date("Y-m-d H:i:s");
		$this->updated_at = date("Y-m-d H:i:s");
	}

	private static function db(): \PDO {
		return Database::getPdo();
	}

	public function add(): int {
		$stmt = self::db()->prepare(
			"INSERT INTO " . self::$tablename . " (plate, name, type, total_seat, is_active) " .
			"VALUES (:plate, :name, :type, :total_seat, :is_active)"
		);
		$stmt->execute([
			'plate' => $this->plate,
			'name' => $this->name ?: null,
			'type' => $this->type,
			'total_seat' => (int)$this->total_seat,
			'is_active' => $this->is_active ? 1 : 0,
		]);
		$this->id = (int)self::db()->lastInsertId();
		return $this->id;
	}

	public function update(): bool {
		$stmt = self::db()->prepare(
			"UPDATE " . self::$tablename . " SET plate = :plate, name = :name, type = :type, " .
			"total_seat = :total_seat, is_active = :is_active WHERE id = :id"
		);
		return $stmt->execute([
			'plate' => $this->plate,
			'name' => $this->name ?: null,
			'type' => $this->type,
			'total_seat' => (int)$this->total_seat,
			'is_active' => $this->is_active ? 1 : 0,
			'id' => (int)$this->id,
		]);
	}

	public static function getById($id): ?self {
		$stmt = self::db()->prepare("SELECT * FROM " . self::$tablename . " WHERE id = :id LIMIT 1");
		$stmt->execute(['id' => (int)$id]);
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
		return $stmt->fetch() ?: null;
	}

	public static function getAll(): array {
		$stmt = self::db()->query("SELECT * FROM " . self::$tablename . " ORDER BY id DESC");
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function getAllActive(): array {
		$stmt = self::db()->query("SELECT * FROM " . self::$tablename . " WHERE is_active = 1 ORDER BY plate ASC");
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function delById($id): bool {
		$stmt = self::db()->prepare("DELETE FROM " . self::$tablename . " WHERE id = :id");
		return $stmt->execute(['id' => (int)$id]);
	}
}
