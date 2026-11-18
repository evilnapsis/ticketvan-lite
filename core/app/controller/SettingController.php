<?php

namespace App\Controller;

use SettingData;
use ViewEngine;
use Req;
use Response;

class SettingController {
	private string $baseFolder;

	public function __construct() {
		$this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
	}

	public function index(): void {
		ViewEngine::render('settings/index.html.twig', [
			'settings' => SettingData::getMap(),
		]);
	}

	public function update(): void {
		$keys = ['company_name', 'company_rfc', 'company_phone', 'company_address', 'company_email', 'ticket_note'];
		foreach ($keys as $key) {
			$val = trim((string)Req::post($key, ''));
			SettingData::setValue($key, $val);
		}
		$_SESSION['updated'] = 'Configuración guardada correctamente.';
		Response::redirect($this->baseFolder . '/settings');
	}
}
