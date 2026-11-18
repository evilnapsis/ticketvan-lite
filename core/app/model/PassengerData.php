<?php

#[\AllowDynamicProperties]
class PassengerData {
	public static $tablename = "passenger";

	public $id;
	public $first_name;
	public $last_name;
	public $document_number;
	public $phone;
	public $email;
	public $is_active;
	public $created_at;
	public $updated_at;

	public function __construct() {
		$this->first_name = "";
		$this->last_name = "";
		$this->document_number = "";
		$this->phone = "";
		$this->email = "";
		$this->is_active = 1;
		$this->created_at = date("Y-m-d H:i:s");
		$this->updated_at = date("Y-m-d H:i:s");
	}

	private static function db(): \PDO {
		return Database::getPdo();
	}

	public function getFullName(): string {
		return trim($this->first_name . ' ' . ($this->last_name ?? ''));
	}

	public function add(): int {
		$stmt = self::db()->prepare(
			"INSERT INTO " . self::$tablename . " (first_name, last_name, document_number, phone, email, is_active) " .
			"VALUES (:first_name, :last_name, :document_number, :phone, :email, :is_active)"
		);
		$stmt->execute([
			'first_name' => $this->first_name,
			'last_name' => $this->last_name ?: null,
			'document_number' => $this->document_number ?: null,
			'phone' => $this->phone ?: null,
			'email' => $this->email ?: null,
			'is_active' => $this->is_active ? 1 : 0,
		]);
		$this->id = (int)self::db()->lastInsertId();
		return $this->id;
	}

	public function update(): bool {
		$stmt = self::db()->prepare(
			"UPDATE " . self::$tablename . " SET first_name = :first_name, last_name = :last_name, " .
			"document_number = :document_number, phone = :phone, email = :email, is_active = :is_active WHERE id = :id"
		);
		return $stmt->execute([
			'first_name' => $this->first_name,
			'last_name' => $this->last_name ?: null,
			'document_number' => $this->document_number ?: null,
			'phone' => $this->phone ?: null,
			'email' => $this->email ?: null,
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

	public static function search(string $q): array {
		$like = '%' . $q . '%';
		$stmt = self::db()->prepare(
			"SELECT * FROM " . self::$tablename . " WHERE is_active = 1 AND " .
			"(first_name LIKE :q1 OR last_name LIKE :q2 OR phone LIKE :q3 OR document_number LIKE :q4) " .
			"ORDER BY first_name ASC LIMIT 20"
		);
		$stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like]);
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function delById($id): bool {
		$stmt = self::db()->prepare("DELETE FROM " . self::$tablename . " WHERE id = :id");
		return $stmt->execute(['id' => (int)$id]);
	}
}
