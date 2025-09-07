<?php
session_start();  // <-- MUST be first line

include("./src/controller.php"); // Σκέτη / σε path είναι το root path του σέρβερ 
include("./src/database.php");

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = htmlspecialchars($_GET['action']); // htmlspecialchars μετατρέπει ό,τι βάλει ο χρήστης ως String για να μην εκτελλεί κακόβουλο input
    $action = str_replace('auth-button-', '', $action);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = htmlspecialchars($_POST['action']);
}

try {
    $controller = new Controller($conn);
    $controller->setRequestMethod($_SERVER['REQUEST_METHOD']);
    $response = $controller->$action();
    echo json_encode($response);
} catch(Exception $e) {
//     http_response_code(500);
//     echo json_encode($e->getMessage());
    http_response_code(400); // use 400 for validation errors
    echo json_encode(["error" => $e->getMessage()]);
}
exit;