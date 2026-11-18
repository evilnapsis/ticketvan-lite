<?php

#[\AllowDynamicProperties]
class SettingData {
	public static $tablename = "setting";

	public $id;
	public $key;
	public $value;
	public $created_at;
	public $updated_at;

	public function __construct() {
		$this->key = "";
		$this->value = "";
		$this->created_at = date("Y-m-d H:i:s");
		$this->updated_at = date("Y-m-d H:i:s");
	}

	private static function db(): \PDO {
		return Database::getPdo();
	}

	public static function getAll(): array {
		$stmt = self::db()->query("SELECT * FROM " . self::$tablename . " ORDER BY id ASC");
		return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
	}

	public static function getMap(): array {
		$rows = self::getAll();
		$map = [];
		foreach ($rows as $row) {
			$map[$row->key] = $row->value;
		}
		return $map;
	}

	public static function getByKey(string $key, string $default = ''): string {
		$stmt = self::db()->prepare("SELECT `value` FROM " . self::$tablename . " WHERE `key` = :k LIMIT 1");
		$stmt->execute(['k' => $key]);
		$val = $stmt->fetchColumn();
		return ($val !== false && $val !== null) ? (string)$val : $default;
	}

	public static function setValue(string $key, ?string $value): bool {
		$stmt = self::db()->prepare(
			"INSERT INTO " . self::$tablename . " (`key`, `value`) VALUES (:k, :v) " .
			"ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
		);
		return $stmt->execute(['k' => $key, 'v' => $value]);
	}
}
