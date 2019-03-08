<?php

include('./functions.php');

if (!isset($_POST['username']) | !isset($_POST['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'error_type' => 'no_fill', 'message' => 'Please fill the fildes.']);
    exit;
}

$connect = cta_db_connect();
if ($connect) {
    $email = mysqli_real_escape_string($connect, $_POST['username']);
    $password = $_POST['password'];
    
    $result = cta_login($connect, $email, $password);
    if ($result['status'] == 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'token' => $result['token'], 'message' => 'You are log in successfully.']);
        exit;
    } else if ($result['status'] == -1) {
        http_response_code(400);
        echo json_encode(['status' => 'fail', 'error_type' => 'no_match', 'message' => 'User Name or Password is incorrect.']);
        exit;
    } else if ($result['status'] == -2) {
        http_response_code(400);
        echo json_encode(['status' => 'fail', 'error_type' => 'no_registered', 'message' => 'This user is not registered!']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['status' => 'fail', 'error_type' => 'no_connect_db', 'message' => 'The server can not connect the database.']);
exit;

?>