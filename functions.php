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
    $query = mysqli_query($connect, "SELECT `id`, `pickup_location`, `delivery_location`, `pickup_by`, `vehicle_id` FROM wp_booking_order WHERE `status` = 'Allocated'");
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

function cta_pickup($connect, $user_id, $job_id, $arrived_time, $depart_time, $all_items_pickup, $no_damage_item) {
    $arrived_time_24h_format  = date("H:i A", strtotime($arrived_time));
    $depart_time_24h_format  = date("H:i A", strtotime($depart_time));
    $all_items_pickup_str = strtolower($all_items_pickup);
    $no_damage_item_str = strtolower($no_damage_item);
    $query = mysqli_query($connect, "SELECT * FROM cta_jobs WHERE `user_id` = '$user_id' AND `job_id` = '$job_id'");
    $job_info = mysqli_fetch_assoc($query);
    if ($job_info) {
        $job_info_id = $job_info['id'];
        $query = mysqli_query($connect, "UPDATE `cta_jobs` SET `arrived_time` = '$arrived_time_24h_format', `depart_time` = '$depart_time_24h_format', `all_items_pickup` = '$all_items_pickup_str', `no_damage_item` = '$no_damage_item_str' WHERE `id` = '$job_info_id'");
        mysqli_fetch_assoc($query);
    } else {
        $query = mysqli_query($connect, "INSERT INTO `cta_jobs`(`user_id`, `job_id`, `arrived_time`, `depart_time`, `all_items_pickup`, `no_damage_item`) VALUES ('$user_id', '$job_id', '$arrived_time', '$depart_time', '$all_items_pickup_str', '$no_damage_item')");
        mysqli_fetch_assoc($query);
    }
}

function __cta_upload_image($image_name, $image_path) {
    $new_imagename = '';
    if ($image_name) {
        $expimage = explode('.', $image_name);
        $imagetype = $expimage[1];
        date_default_timezone_set('Australia/Melbourne');
        $date = date('m/d/Yh:i:sa', time());
        $rand = rand(10000, 99999);
        $encname = $date . $rand;
        $new_imagename = md5($encname) . '.' . $imagetype;
        $imagepath = "./uploads/" . $new_imagename;
        move_uploaded_file($image_path, $imagepath);
    }
    return $new_imagename;
}

function _cta_upload_image($image) {
    $imagename = '';
    if ($image) {
        $imagename = __cta_upload_image($image['name'], $image['tmp_name']);
    }
    return $imagename;
}

function _cta_upload_images($images) {
    $imagenames = array();
    if ($images) {
        foreach ($images['name'] as $key => $image) {
            $imagenames[] = __cta_upload_image($images['name'][$key], $images['tmp_name'][$key]);
        }
    }
    return $imagenames;
}


function cta_delivery($connect, $user_id, $job_id, $arrived_delivery, $receiver_signature, $receiver_name, $photo) {
    $query = mysqli_query($connect, "SELECT * FROM cta_jobs WHERE `user_id` = '$user_id' AND `job_id` = '$job_id'");
    $job_info = mysqli_fetch_assoc($query);
    
    if ($job_info) {
        $delete_filepath = "./uploads/" . $job_info['receiver_signature'];
        if (file_exists($delete_filepath)) {
            unlink($delete_filepath);
        }
        $delete_filepath = "./uploads/" . $job_info['photo'];
        if (file_exists($delete_filepath)) {
            unlink($delete_filepath);
        }
        // $original_photos = unserialize($job_info['photos']);
        // foreach ($original_photos as $original_photo) {
        //     $delete_filepath = "./uploads/" . $original_photo;
        //     if (file_exists($delete_filepath)) {
        //         unlink($delete_filepath);
        //     }
        // }
    }
    
    $signature_name = _cta_upload_image($receiver_signature);
    $photo_name = _cta_upload_image($photo);
    // $photos_name = serialize(_cta_upload_images($photos));
    if ($job_info) {
        $job_info_id = $job_info['id'];
        $query = mysqli_query($connect, "UPDATE `cta_jobs` SET `arrived_delivery` = '$arrived_delivery', `receiver_signature` = '$signature_name', `receiver_name` = '$receiver_name', `photo` = '$photo_name' WHERE `id` = '$job_info_id'");
        mysqli_fetch_assoc($query);
    } else {
        $query = mysqli_query($connect, "INSERT INTO `cta_jobs`(`user_id`, `job_id`, `arrived_delivery`, `receiver_signature`, `receiver_name`, `photo`) VALUES ('$user_id', '$job_id', '$arrived_delivery', '$signature_name', '$receiver_name', '$photo_name')");
        mysqli_fetch_assoc($query);
    }
    $query = mysqli_query($connect, "UPDATE `wp_booking_order` SET `status` = 'Completed' WHERE `id` = '$job_id'");
    mysqli_fetch_assoc($query);
}