<?php
include("ip_check.php");
include("notify_admin.php");
session_start();

// Switch between development/production
$devOrProd = 0;

if ($devOrProd == 1) { // If development, enter data from your local settings
    // URL from localhost
    define("URL", "http://localhost/d.zd.lu_2021.05");
    // PDO request
    define("DB_HOST", "localhost");
    define("DB_NAME", "d.zd.lu");
    define("DB_NAME_TELEGRAM", "telegram");
    define("DB_USER", "root");
    define("DB_PASSWORD", "");
    // Live Search query (determines which database to connect to)
    define("LS_QUERY", "ls_query");
    // reCAPTCHA
    define("RECAPTCHA_SUCCESS", "true");
} else { // If production, enter data from your host settings
    define("URL", "https://d.zd.lu");
    define("DB_HOST", "localhost");
    define("DB_NAME", "rdtcuene_adt");
    define("DB_NAME_TELEGRAM", "rdtcuene_telegram");
    define("DB_USER", "rdtcuene_d");
    define("DB_PASSWORD", "bUnD9rz75Mt4TVS@qsd677Shfjsqdgifdf676((-_");
    define("LS_QUERY", "ls_query_2");
    define("RECAPTCHA_SUCCESS", "$recaptcha->success == true && $recaptcha->score >= 0.5 && $recaptcha->action == 'contact'");
}

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

// Save date-time info as user connects
date_default_timezone_set("Europe/Paris");
$visit_datetime = date('Y-m-d H:i:s');
// The current page user is visiting
$current_page = validateInput(basename($_SERVER['PHP_SELF']));

// Create/check profile in the database with the IP address
// Reach adt Database
try {
    // Constants on top of this page
    $db = new PDO('mysql:host='.DB_HOST.'; dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Error : ' . $e->getMessage());
}
$req = $db->prepare('
        SELECT *
        FROM users
        WHERE ip = :ip
    ');
$req->bindValue(':ip', $ip);
$req->execute();

// If $ip corresponds to a user's saved IP, we update/add data to the user's profile
if ($data = $req->fetch()) {
    $req->closeCursor();
    // Update last connected datetime
    // Add or concatenate current page name
    $req = $db->prepare('
            UPDATE users
            SET date_time = :datetime,
              viewed_pages = concat("[", :datetime,  "] ", :current_page, "; ", viewed_pages)
            WHERE ip = :ip
        ');
    $req->execute(array(
        ':datetime' => $visit_datetime,
        ':current_page' => $current_page,
        ':ip' => $ip
    ));
    $req->closeCursor();
    // If login attempts were done, save them on his profile
    if (isset($_POST['login_password'])) {
        $_POST['login_password'] = validateInput($_POST['login_password']);

        $req = $db->prepare('
              UPDATE users
              SET attempted_inputs = concat("[login_password] ", :input, "; ", attempted_inputs)
              WHERE ip = :ip
            ');
        $req->execute(array(
            ':input' => $_POST['login_password'],
            ':ip' => $ip
        ));
        $req->closeCursor();
    } elseif (isset($_POST['login_doorI'])) {
        $_POST['login_doorI'] = validateInput($_POST['login_doorI']);

        $req = $db->prepare('
              UPDATE users
              SET attempted_inputs = concat("[login_doorI] ", :input, "; ", attempted_inputs)
              WHERE ip = :ipé
            ');
        $req->execute(array(
            ':input' => $_POST['login_doorI'],
            ':ip' => $ip
        ));
        $req->closeCursor();
    }
    // Send mail to admin to notify of the visit of a particular user
    //notify_visit_name($data['name'], $visit_datetime);
} else { //  If user is new, create new profile on database
    $req = $db->prepare('
            INSERT INTO users(ip, date_time, location, viewed_pages, queries, attempted_inputs)
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
// IP LOCATION
//
// Determine location of visitor from his IP address

// Save location into user's profile
$_SESSION['location'] = $ip_full_address = ipLocation($_SESSION['ip'], "Address");
$ip_country = ipLocation($_SESSION['ip'], "Country Code");
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

// Notify of unwanted visits and redirect to another page
// By county code
notify_visit_country($ip_country, $visit_datetime, "AL");
// By IP address
notify_visit_ip($ip, $visit_datetime);

// A function that validates form data by removing/replacing unwanted characters for a better security
function validateInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
