<?php
require_once("ip_check.php");
require_once("input_check.php");
require_once("notify_admin.php");

//
// IP ADDRESS
//
// Determine the IP address of the visitor
$ip = ft_get_ip_address();
$_SESSION['ip'] = $ip;

//
// LOCATION
//
// Determine location of visitor from his IP address
$ip_full_address = ft_ip_location($ip, "full");
$ip_country = ft_ip_location($ip, "country");
if (!$ip_full_address)
    $ip_full_address = "";

//
// DEVICE
//
$device = ft_validate_input($_SERVER['HTTP_USER_AGENT']);

//
// DATABASE/USER
//
// Get info from the IP address of the visitor & save it to the database

// Save datetime info
date_default_timezone_set("Europe/Paris");
$visit_datetime = date('Y-m-d H:i:s');
// The current visited page
$current_page = ft_validate_input_trim(basename($_SERVER['PHP_SELF']));

// First, check profile in the database with the IP address
$req = $db->prepare('
        SELECT *
        FROM users
        WHERE ip = :ip
    ');
$req->bindValue(':ip', $ip);
$req->execute();
// If $ip corresponds to a user's saved IP
// we update/add data to the user's profile
if ($data = $req->fetch()) {
    $_SESSION['id'] = $data['id'];
    $req->closeCursor();
    // Update last connected datetime
    // Add or concatenate current page name
    $req = $db->prepare('
            UPDATE users
            SET date_time = :datetime,
              viewed_pages = concat("[", :datetime,  "] ",
              :current_page, "; ", viewed_pages),
              location = concat("[", :datetime,  "] ",
              :location, "; ", location),
              device = :device,
              visit_count = visit_count + 1
            WHERE ip = :ip
        ');
    $req->execute(array(
        ':datetime' => $visit_datetime,
        ':current_page' => $current_page,
        ':location' => $ip_full_address,
        ':device' => $device,
        ':ip' => $ip
    ));
    $req->closeCursor();

    /*
    // Send mail to admin to notify of the visit of a particular user
    $unwanted_name = "";
    if ($data['name'] == $unwanted_name)
        ft_notify_visit_name($unwanted_name, $visit_datetime);*/

} else { //  If user is new, create new profile on database
    $req = $db->prepare('
            INSERT INTO users(
                ip, date_time, location,
                viewed_pages
            )
            VALUES(:ip, :datetime, :location, :current_page)
        ');
    $req->execute(array(
        ':ip' => $ip,
        ':datetime' => $visit_datetime,
        ':location' => $ip_full_address,
        ':current_page' => $current_page
    ));
}
$req->closeCursor();

//
// NOTIFY ADMIN
//
// Notify of unwanted visits and redirect to another page
/*
// By county code
$unwanted_country_code = "AL";
if ($ip_country == $unwanted_country_code)
    ft_notify_visit_country($visit_datetime, $unwanted_country_code);*/
/* By IP address
if ($ip == "184.154.76.12"
|| $ip == "2a00:5ba0:10:2242:3c52:7dff:fee6:7714")
    ft_notify_visit_ip($ip, $visit_datetime);
*/
