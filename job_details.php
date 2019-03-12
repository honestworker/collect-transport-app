<?php

include('functions.php');

if (!isset($_POST['token']) || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'error_type' => 'no_fill', 'message' => 'Please fill the fildes.']);
    exit;
}

$connect = cta_db_connect();
if ($connect) {
    $user_id = cta_check_logged_in($connect, $_POST['token']);
    if ($user_id) {
        $job_detail = cta_job_detail($connect, $_POST['id']);
        if ($job_detail) {
            if ($job_detail['status'] != 'Allocated') {
                http_response_code(200);
                echo json_encode(['status' => 'success', 'error_type' => 'completed', 'message' => 'This job is already completed.']);
                exit;
            }
            http_response_code(200);
            echo json_encode(['status' => 'success', 'data' => $job_detail, 'message' => '']);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'fail', 'error_type' => 'no_job', 'message' => 'We can not find this job.']);
            exit;
        }
    }
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'error_type' => 'token_error', 'message' => 'Your token is incorrect.']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'fail', 'error_type' => 'no_connect_db', 'message' => 'The server can not connect the database.']);
exit;

?>