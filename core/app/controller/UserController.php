<?php

namespace App\Controller;

use UserData;
use BranchData;
use ViewEngine;
use Req;
use Response;

class UserController {
	private string $baseFolder;

	public function __construct() {
		$this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
	}

	public function index(): void {
		ViewEngine::render('users/index.html.twig', [
			'users' => UserData::getAll(),
		]);
	}

	public function new(): void {
		ViewEngine::render('users/new.html.twig', [
			'branches' => BranchData::getAllActive(),
		]);
	}

	public function create(): void {
		$name = trim((string)Req::post('name', ''));
		$username = trim((string)Req::post('username', ''));
		$email = trim((string)Req::post('email', ''));
		$password = (string)Req::post('password', '');
		$role = (string)Req::post('role', 'seller');
		$branchId = (int)Req::post('branch_id', 0) ?: null;

		if ($name === '' || $email === '' || $password === '') {
			$_SESSION['error'] = 'Nombre, correo y contraseña son obligatorios.';
			Response::redirect($this->baseFolder . '/users/new');
		}

		try {
			$u = new UserData();
			$u->name = $name;
			$u->username = $username ?: null;
			$u->email = $email;
			$u->role = in_array($role, ['admin', 'seller'], true) ? $role : 'seller';
			$u->branch_id = $branchId;
			$u->password_hash = password_hash($password, PASSWORD_DEFAULT);
			$u->is_active = isset($_POST['is_active']) ? 1 : 0;
			$u->add();
			$_SESSION['success'] = 'Usuario creado correctamente.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'No se pudo crear el usuario (correo o usuario duplicado).';
		}
		Response::redirect($this->baseFolder . '/users');
	}

	public function edit(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$user = UserData::getById($id);
		if (!$user) {
			$_SESSION['error'] = 'Usuario no encontrado.';
			Response::redirect($this->baseFolder . '/users');
		}
		ViewEngine::render('users/edit.html.twig', [
			'user' => $user,
			'branches' => BranchData::getAllActive(),
		]);
	}

	public function update(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		$u = UserData::getById($id);
		if (!$u) {
			$_SESSION['error'] = 'Usuario no encontrado.';
			Response::redirect($this->baseFolder . '/users');
		}

		$u->name = trim((string)Req::post('name', ''));
		$u->username = trim((string)Req::post('username', '')) ?: null;
		$u->email = trim((string)Req::post('email', ''));
		$role = (string)Req::post('role', 'seller');
		$u->role = in_array($role, ['admin', 'seller'], true) ? $role : 'seller';
		$u->branch_id = (int)Req::post('branch_id', 0) ?: null;
		$u->is_active = isset($_POST['is_active']) ? 1 : 0;

		try {
			$u->update();
			$newPass = (string)Req::post('password', '');
			if ($newPass !== '') {
				$u->updatePassword(password_hash($newPass, PASSWORD_DEFAULT));
			}
			$_SESSION['updated'] = 'Usuario actualizado.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'Error al actualizar usuario.';
		}
		Response::redirect($this->baseFolder . '/users');
	}

	public function delete(array $vars): void {
		$id = (int)($vars['id'] ?? 0);
		if ($id === (int)($_SESSION['user_id'] ?? 0)) {
			$_SESSION['error'] = 'No puedes eliminar tu propio usuario.';
			Response::redirect($this->baseFolder . '/users');
		}
		try {
			UserData::delById($id);
			$_SESSION['deleted'] = 'Usuario eliminado.';
		} catch (\PDOException $e) {
			$_SESSION['error'] = 'No se puede eliminar: tiene boletos emitidos.';
		}
		Response::redirect($this->baseFolder . '/users');
	}
}
