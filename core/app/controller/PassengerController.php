<?php

namespace App\Controller;

use PassengerData;
use ViewEngine;
use Req;
use Response;

class PassengerController {
	private string $baseFolder;

	public function __construct() {
		$this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
	}

	public function index(): void {
		ViewEngine::render('passengers/index.html.twig', [
			'passengers' => PassengerData::getAll(),
		]);
	}

	public function searchAjax(): void {
		$q = trim((string)Req::get('q', ''));
		$list = $q === '' ? [] : PassengerData::search($q);
		$data = array_map(fn($p) => [
			'id' => (int)$p->id,
			'name' => $p->getFullName(),
			'phone' => (string)$p->phone,
			'document_number' => (string)$p->document_number,
		], $list);
		Response::json(['results' => $data]);
	}

	public function new(): void {
		ViewEngine::render('passengers/new.html.twig');
	}

	public function create(): void {
		$firstName = trim((string)Req::post('first_name', ''));
		if ($firstName === '') {
			$_SESSION['error'] = 'El nombre es obligatorio.';
			Response::redirect($this->baseFolder . '/passengers/new');
		}

		$p = new PassengerData();
		$p->first_name = $firstName;
		$p->last_name = trim((string)Req::post('last_name', ''));
		$p->document_number = trim((string)Req::post('document_number', ''));
		$p->phone = trim((string)Req::post('phone', ''));
		$p->email = trim((string)Req::post('email', ''));
		$p->is_active = isset($_POST['is_active']) ? 1 : 0;
		$p->add();

		$_SESSION['success'] = 'Pasajero registrado correctamente.';
		Response::redirect($this->baseFolder . '/passengers');
	}

	public function edit(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$passenger = PassengerData::getById($id);
		if (!$passenger) {
			$_SESSION['error'] = 'Pasajero no encontrado.';
			Response::redirect($this->baseFolder . '/passengers');
		}
		ViewEngine::render('passengers/edit.html.twig', ['passenger' => $passenger]);
	}

	public function update(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$p = PassengerData::getById($id);
		if (!$p) {
			$_SESSION['error'] = 'Pasajero no encontrado.';
			Response::redirect($this->baseFolder . '/passengers');
		}

		$p->first_name = trim((string)Req::post('first_name', ''));
		$p->last_name = trim((string)Req::post('last_name', ''));
		$p->document_number = trim((string)Req::post('document_number', ''));
		$p->phone = trim((string)Req::post('phone', ''));
		$p->email = trim((string)Req::post('email', ''));
		$p->is_active = isset($_POST['is_active']) ? 1 : 0;
		$p->update();

		$_SESSION['updated'] = 'Pasajero actualizado.';
		Response::redirect($this->baseFolder . '/passengers');
	}

	public function delete(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		PassengerData::delById($id);
		$_SESSION['deleted'] = 'Pasajero eliminado.';
		Response::redirect($this->baseFolder . '/passengers');
	}
}
