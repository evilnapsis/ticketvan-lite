<?php

#[\AllowDynamicProperties]
class UserData {
	public static $tablename = "`user`";

	public $id;
	public $branch_id;
	public $role;
	public $name;
	public $username;
	public $email;
	public $password_hash;
	public $is_active;
	public $created_at;
	public $updated_at;

	// Navegación
	public $branch = null;

	public function __construct() {
		$this->branch_id = null;
		$this->role = "seller";
		$this->name = "";
		$this->username = "";
		$this->email = "";
		$this->password_hash = "";
		$this->is_active = 1;
		$this->created_at = date("Y-m-d H:i:s");
		$this->updated_at = date("Y-m-d H:i:s");
	}

	private static function db(): \PDO {
		return Database::getPdo();
	}

	public function add(): int {
		$stmt = self::db()->prepare(
			"INSERT INTO " . self::$tablename . " (branch_id, role, name, username, email, password_hash, is_active) " .
			"VALUES (:branch_id, :role, :name, :username, :email, :password_hash, :is_active)"
		);
		$stmt->execute([
			'branch_id' => $this->branch_id ?: null,
			'role' => $this->role,
			'name' => $this->name,
			'username' => $this->username ?: null,
			'email' => $this->email,
			'password_hash' => $this->password_hash,
			'is_active' => $this->is_active ? 1 : 0,
		]);
		$this->id = (int)self::db()->lastInsertId();
		return $this->id;
	}

	public function update(): bool {
		$stmt = self::db()->prepare(
			"UPDATE " . self::$tablename . " SET branch_id = :branch_id, role = :role, name = :name, " .
			"username = :username, email = :email, is_active = :is_active WHERE id = :id"
		);
		return $stmt->execute([
			'branch_id' => $this->branch_id ?: null,
			'role' => $this->role,
			'name' => $this->name,
			'username' => $this->username ?: null,
			'email' => $this->email,
			'is_active' => $this->is_active ? 1 : 0,
			'id' => (int)$this->id,
		]);
	}

	public function updatePassword(string $newHash): bool {
		$stmt = self::db()->prepare("UPDATE " . self::$tablename . " SET password_hash = :ph WHERE id = :id");
		return $stmt->execute(['ph' => $newHash, 'id' => (int)$this->id]);
	}

	public static function getById($id): ?self {
		$stmt = self::db()->prepare(
			"SELECT u.*, b.name AS branch_name FROM " . self::$tablename . " u " .
			"LEFT JOIN branch b ON b.id = u.branch_id WHERE u.id = :id LIMIT 1"
		);
		$stmt->execute(['id' => (int)$id]);
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
		return $stmt->fetch() ?: null;
	}

	public static function getByLogin(string $login): ?self {
		$stmt = self::db()->prepare(
			"SELECT * FROM " . self::$tablename . " WHERE (username = :l1 OR email = :l2) AND is_active = 1 LIMIT 1"
		);
		$stmt->execute(['l1' => $login, 'l2' => $login]);
		$stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
		return $stmt->fetch() ?: null;
	}

	public static function getAll(): array {
		$stmt = self::db()->query(
			"SELECT u.*, b.name AS branch_name FROM " . self::$tablename . " u " .
			"LEFT JOIN branch b ON b.id = u.branch_id ORDER BY u.id DESC"
		);
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function delById($id): bool {
		$stmt = self::db()->prepare("DELETE FROM " . self::$tablename . " WHERE id = :id");
		return $stmt->execute(['id' => (int)$id]);
	}
}
