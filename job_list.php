<?php

include('functions.php');

if (!isset($_POST['token'])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'error_type' => 'no_fill', 'message' => 'Please fill the fildes.']);
    exit;
}

$connect = cta_db_connect();
if ($connect) {
    $user = cta_check_logged_in($connect, $_POST['token']);
    if ($user) {
        $job_list = cta_job_list($connect);
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $job_list, 'message' => '']);
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