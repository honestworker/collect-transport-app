<?php

include('functions.php');

if (!isset($_POST['token']) || !isset($_POST['id']) || !isset($_POST['arrived_delivery']) || !isset($_FILES['receiver_signature']) || !isset($_POST['receiver_name']) || !isset($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'error_type' => 'no_fill', 'message' => 'Please fill the fildes.']);
    exit;
}

$connect = cta_db_connect();
if ($connect) {
    $user_id = cta_check_logged_in($connect, $_POST['token']);
    if ($user_id) {
        cta_delivery($connect, $user_id, $_POST['id'], $_POST['arrived_delivery'], $_FILES['receiver_signature'], $_POST['receiver_name'], $_FILES['photo']);
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'The job has been deliveried successfully.']);
        exit;
    }
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'error_type' => 'token_error', 'message' => 'Your token is incorrect.']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'fail', 'error_type' => 'no_connect_db', 'message' => 'The server can not connect the database.']);
exit;

?>