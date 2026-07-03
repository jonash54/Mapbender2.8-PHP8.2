<?php
require_once(__DIR__."/../../core/globalSettings.php");
require_once(__DIR__."/../classes/class_user.php");

$command = $_POST["command"];
$pattern = "/[a-z]/i";
if (!preg_match($pattern, (string) $command)) {
	echo "Command not valid!";
	die;
}

$userId = $_POST["userId"];
if (!is_numeric($userId)) {
	echo "User ID not valid!";
	die;
}

$userTicket = $_POST["userTicket"];
$pattern = "/[a-z0-9]{30}/i";
if (!preg_match($pattern, (string) $userTicket)) {
	echo "User Ticket not valid!";
	die;
}

$userPassword = $_POST["userPassword"];
//$pattern = "/[a-z0-9A-Z]/";
//if (!preg_match($pattern, $userTicket)) {
//	echo "User Ticket not valid!";
//	die;
//}

$user = new user();
$user->id = $userId;

if($command == 'checkTicket') {
	if($user->validUserPasswordTicket($userTicket)) {
		echo "true";
	}
	else {
		echo "false";
	}
}

if($command == 'savePwd') {
	if($user->setPassword($userPassword,$userTicket)) {
		echo "true";
	}
	else {
		echo "false";
	}
}
?>
