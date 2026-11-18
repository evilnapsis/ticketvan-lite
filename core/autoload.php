<?php

require_once __DIR__ . "/../vendor/autoload.php";

include "controller/Core.php";
include "controller/Database.php";
include "controller/Executor.php";
include "controller/Req.php";
include "controller/Response.php";
include "controller/LbModel.php";
include "controller/ViewEngine.php";
include "controller/Model.php";
include "controller/Request.php";
include "controller/Session.php";
include "controller/class.upload.php";

if (file_exists(__DIR__ . "/fpdf/fpdf.php")) {
	require_once __DIR__ . "/fpdf/fpdf.php";
}
