<?php

include('../wp-includes/class-phpass.php');

function cta_db_connect() {
    $connect = mysqli_connect("DB_HOST", "DB_USER", "DB_PASS", "DB_NAME") or mysqli_error();
    return $connect;
}

function cta_gensalt($count = 64)
{
	# This one needs to use a different order of characters and a
	# different encoding scheme from the one in encode64() above.
	# We care because the last character in our encoded string will
	# only represent 2 bits.  While two known implementations of
	# bcrypt will happily accept and correct a salt string which
	# has the 4 unused bits set to non-zero, we do not want to take
	# chances and we also do not want to waste an additional byte
	# of entropy.
	$itoa64 = 'U345FGijkloV672tHcCJKM.N9OPQRSawTbdqefghWAmnEuIXYBLDvZxy/z01prs8';

	$output = '';
	for ($index = 0; $index < $count; $index++) {
	    $random = rand(0, strlen($itoa64) - 1);
	    $output .= $itoa64[$random];
	}

	return $output;
}

function cta_login($connect, $email, $password) {
    $result = array(
        'status' => -2,
        'token' => ''
    );
    $query = mysqli_query($connect, "SELECT * FROM `wp_users` WHERE `user_email` = '$email'");
    $user_info = mysqli_fetch_assoc($query);
    if (!$user_info) {
        $query = mysqli_query($connect, "SELECT * FROM `wp_users` WHERE `user_nicename` = '$email'");
        $user_info = mysqli_fetch_assoc($query);
    }
    
    if ($user_info) {
        $result['status'] = -1;
        $our_hasher = new PasswordHash(8, true);
        if ($our_hasher->CheckPassword( $password, $user_info['user_pass'])) {
            $token = cta_gensalt();
            $user_id = $user_info['ID'];
            $query = mysqli_query($connect, "SELECT * FROM `cta_users` WHERE `user_id` = '$user_id'");
            $user_token = mysqli_fetch_assoc($query);
            if ($user_token) {
                $query = mysqli_query($connect, "UPDATE `cta_users` SET `token`='$token' WHERE `user_id`='$user_id'");
                mysqli_fetch_assoc($query);
            } else {
                $query = mysqli_query($connect, "INSERT INTO `cta_users`(`user_id`, `token`) VALUES ('$user_id','$token')");
                mysqli_fetch_assoc($query);
            }
            $result['status'] = 0;
            $result['token'] = $token;
        }
    }
    return $result;
}

function cta_logout($connect, $token) {
    $query = mysqli_query($connect, "SELECT * FROM `cta_users` WHERE `token` = '$token'");
    $user_info = mysqli_fetch_assoc($query);
    if ($user_info) {
        $user_id = $user_info['ID'];
        $query = mysqli_query($connect, "UPDATE `cta_users` SET `token` = '' WHERE `user_id` = '$user_id'");
        $user_token = mysqli_fetch_assoc($query);
        return 1;
    }
    return 0;
}

function cta_check_logged_in($connect, $token) {
    $query = mysqli_query($connect, "SELECT * FROM `cta_users` WHERE `token` = '$token'");
    $user_info = mysqli_fetch_assoc($query);
    if ($user_info) {
        return $user_info['user_id'];
    }
    return 0;
}

function cta_job_list($connect) {
    $query = mysqli_query($connect, "SELECT `id`, `pickup_location`, `delivery_location`, `pickup_by`, `vehicle_id` FROM wp_booking_order");
    $job_list_info = array();
    while ($job_info = mysqli_fetch_assoc($query)) {
        $job_list_info[] = $job_info;
    }
    return $job_list_info;
}

function cta_job_detail($connect, $id) {
    $query = mysqli_query($connect, "SELECT * FROM wp_booking_order WHERE `id` = '$id'");
    $job_detail_info = mysqli_fetch_assoc($query);
    return $job_detail_info;
}

function cta_pickup($connect, $user_id, $arrived_time, $all_items_pickup, $no_damage_item, $depart_time) {
    $arrived_time_24h_format  = date("H:i A", strtotime($arrived_time));
    $depart_time_24h_format  = date("H:i A", strtotime($depart_time));
    $all_items_pickup_str = strtolower($all_items_pickup);
    $no_damage_item_str = strtolower($no_damage_item);
    $query = mysqli_query($connect, "UPDATE `cta_users` SET `arrived_time` = '$arrived_time_24h_format', `depart_time` = '$depart_time_24h_format', `all_items_pickup` = '$all_items_pickup_str', `no_damage_item` = '$no_damage_item_str' WHERE `user_id` = '$user_id'");
    $user_token = mysqli_fetch_assoc($query);
}