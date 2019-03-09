<?php

include('functions.php');

if (!isset($_POST['token']) || !isset($_POST['arrived_time']) || !isset($_POST['all_items_pickup']) || !isset($_POST['no_damage_item']) || !isset($_POST['depart_time'])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'error_type' => 'no_fill', 'message' => 'Please fill the fildes.']);
    exit;
}

$connect = cta_db_connect();
if ($connect) {
    $user_id = cta_check_logged_in($connect, $_POST['token']);
    if ($user_id) {
        cta_pickup($connect, $user_id, $_POST['arrived_time'], $_POST['all_items_pickup'], $_POST['no_damage_item'], $_POST['depart_time']);
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => '']);
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