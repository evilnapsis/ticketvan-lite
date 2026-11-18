<?php

namespace App\Controller;

use BranchData;
use ViewEngine;
use Req;
use Response;

class BranchController {
	private string $baseFolder;

	public function __construct() {
		$this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
	}

	public function index(): void {
		ViewEngine::render('branches/index.html.twig', [
			'branches' => BranchData::getAll(),
		]);
	}

	public function new(): void {
		ViewEngine::render('branches/new.html.twig');
	}

	public function create(): void {
		$name = trim((string)Req::post('name', ''));
		$city = trim((string)Req::post('city', ''));
		if ($name === '' || $city === '') {
			$_SESSION['error'] = 'Nombre y ciudad son obligatorios.';
			Response::redirect($this->baseFolder . '/branches/new');
		}

		try {
			$b = new BranchData();
			$b->code = strtoupper(trim((string)Req::post('code', '')));
			$b->name = $name;
			$b->city = $city;
			$b->address = trim((string)Req::post('address', ''));
			$b->phone = trim((string)Req::post('phone', ''));
			$b->is_active = isset($_POST['is_active']) ? 1 : 0;
			$b->add();
			$_SESSION['success'] = 'Terminal registrada correctamente.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'Error al registrar terminal (nombre duplicado).';
		}
		Response::redirect($this->baseFolder . '/branches');
	}

	public function edit(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$branch = BranchData::getById($id);
		if (!$branch) {
			$_SESSION['error'] = 'Terminal no encontrada.';
			Response::redirect($this->baseFolder . '/branches');
		}
		ViewEngine::render('branches/edit.html.twig', ['branch' => $branch]);
	}

	public function update(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$b = BranchData::getById($id);
		if (!$b) {
			$_SESSION['error'] = 'Terminal no encontrada.';
			Response::redirect($this->baseFolder . '/branches');
		}

		$b->code = strtoupper(trim((string)Req::post('code', '')));
		$b->name = trim((string)Req::post('name', ''));
		$b->city = trim((string)Req::post('city', ''));
		$b->address = trim((string)Req::post('address', ''));
		$b->phone = trim((string)Req::post('phone', ''));
		$b->is_active = isset($_POST['is_active']) ? 1 : 0;

		try {
			$b->update();
			$_SESSION['updated'] = 'Terminal actualizada.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'Error al actualizar terminal.';
		}
		Response::redirect($this->baseFolder . '/branches');
	}

	public function delete(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		try {
			BranchData::delById($id);
			$_SESSION['deleted'] = 'Terminal eliminada.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'No se puede eliminar: tiene corridas asociadas.';
		}
		Response::redirect($this->baseFolder . '/branches');
	}
}
