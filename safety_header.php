<?php
include("ip_check.php");
include("input_check.php");
include("notify_admin.php");
session_start();

//
// IP ADDRESS
//
// Determine the IP address of the visitor

$ip = get_ip_address();
$_SESSION['ip'] = $ip;

//
// DATABASE/USER
//
// Get info from the IP address of the visitor & save it to the database

// Save datetime info
date_default_timezone_set("Europe/Paris"); // Replace by your timezone
$visit_datetime = date('Y-m-d H:i:s');
// The current visited page
$current_page = validateInput(basename($_SERVER['PHP_SELF']));

// Create/check profile in the database with the IP address
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
    $req->closeCursor();
    // Update last connected datetime
    // Add or concatenate current page name
    $req = $db->prepare('
            UPDATE users
            SET date_time = :datetime,
              viewed_pages = concat("[", :datetime,  "] ",
			  :current_page, "; ", viewed_pages)
            WHERE ip = :ip
        ');
    $req->execute(array(
        ':datetime' => $visit_datetime,
        ':current_page' => $current_page,
        ':ip' => $ip
    ));
    $req->closeCursor();

    // If login attempts were done, save them on his profile
    input_check_login($db, $ip);

    // Save set category into user's profile from database
    input_check_query($db, $ip);

    // Send mail to admin to notify of the visit of a particular user
    //notify_visit_name($data['name'], $visit_datetime);
} else { //  If user is new, create new profile on database
    $req = $db->prepare('
            INSERT INTO users(
                ip, date_time, location,
                viewed_pages, queries, attempted_inputs
            )
            VALUES(:ip, :datetime, "", :current_page, "", "")
        ');
    $req->execute(array(
        ':ip' => $ip,
        ':datetime' => $visit_datetime,
        ':current_page' => $current_page
    ));
}
$req->closeCursor();

//
// SAVE LOCATION
//
// Determine location of visitor from his IP address

// Save location into user's profile
$ip_full_address = ipLocation($ip, "full");
$ip_country = ipLocation($ip, "country");
if (!$ip_full_address)
    $ip_full_address = "";
$req = $db->prepare('
   UPDATE users
   SET location = concat("[", :datetime,  "] ", :location, "; ", location)
   WHERE ip = :ip
');
$req->execute(array(
    ':datetime' => $visit_datetime,
    ':location' => $ip_full_address,
    ':ip' => $ip
));
$req->closeCursor();

//
// NOTIFY ADMIN
//
// Notify of unwanted visits and redirect to another page

// By county code
$unwanted_country_code = "AL";
if ($ip_country == $unwanted_country_code)
    notify_visit_country($visit_datetime, $unwanted_country_code);
// By IP address
notify_visit_ip($ip, $visit_datetime);
?>
