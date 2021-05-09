<?php

//
// SESSION
//

session_start();


// Constants used for switching between development/production

$devOrProd = 1;

// If development
if ($devOrProd == 1) {
    // URL (menu.php, etc)
    define("URL", "http://localhost/adt_July_2020_(db)_ajax2");

    // PDO request (header.php)
    define("DB_HOST", "localhost");
    define("DB_NAME", "tanigawa");
    define("DB_USER", "root");
    define("DB_PASSWORD", "");

    // Live Search query (determines which database to connect to)
    define("LS_QUERY", "ls_query");

// If production
} else {
    // URL
    define("URL", "https://www.alaindtanigawa.com");

    // PDO request
    define("DB_HOST", "db5000573806.hosting-data.io");
    define("DB_NAME", "dbs551321");
    define("DB_USER", "dbu399319");
    define("DB_PASSWORD", "bUnD9rz75Mt4TVS@qsd677Shfjsqdgifdf676((-_");

    // Live Search query
    define("LS_QUERY", "ls_query_2");
}


//$newid = session_create_id('myprefix-');
//$session_id = session_id($newid);

//
// IP ADDRESS
//

// Check data from the IP address of the visitor & save to database
//
// It should be noted that HTTP_X_FORWARDED_FOR and HTTP_CLIENT_IP used in the deep_detect option will get you the real IP of the user if he is using a proxy server -transparent proxy offcurse - , not the IP of the proxy server which is the client how is actually tcp-connected to my website.

$ip = NULL;

if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
    $ip = $_SERVER["REMOTE_ADDR"];
    if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
        $ip = $_SERVER['HTTP_CLIENT_IP'];
}

//
// DATABASE/USER
//

// Add ip to user's session
$_SESSION['ip'] = $ip;

// Save date-time info as user connects
date_default_timezone_set("Europe/Paris");
$visit_datetime = date('Y-m-d H:i:s');
// The current page user is visiting
$current_page = validateInput(basename($_SERVER['PHP_SELF']));

// Create/check profile in the database with the IP address
// Reach Tanigawa Database
try {
    // Constants on top of this page
    $db = new PDO('mysql:host='.DB_HOST.'; dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASSWORD);

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

// If ip corresponds to a user's ip, we update/add data to user's profile
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

    // If login attempts were done from user, save them on his profile
    //
    // Case login_password
    if (isset($_POST['login_password'])) {

        // Validate input after the form has been sent
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

        // Case login_doorI
    } elseif (isset($_POST['login_doorI'])) {

        // Validate input after the form has been sent
        $_POST['login_doorI'] = validateInput($_POST['login_doorI']);

        $req = $db->prepare('
              UPDATE users
              SET attempted_inputs = concat("[login_doorI] ", :input, "; ", attempted_inputs)
              WHERE ip = :ip
            ');

        $req->execute(array(
            ':input' => $_POST['login_doorI'],
            ':ip' => $ip
        ));

        $req->closeCursor();
    }

    /*
    // Send mail to admin with user's data
    if ($data['name'] == "XXX") {

        $to = 'info@alaindtanigawa.com';

        $user_name = validateInput($data['name']);
        $body = $user_name . "visited your website !\n\n=========================================\n Date-time: $visit_datetime\n=========================================\n\n";

        $subject = "ADT | She came";

        // Send mail
        mail($to, $subject, $body);
    }*/

    //  If user is new, create new profile on database
} else {
    $req = $db->prepare('
            INSERT INTO users(ip, date_time, viewed_pages, attempted_inputs)
            VALUES(:ip, :datetime, :current_page, "")
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

function ipLocation($ip = NULL, $purpose = "location", $deep_detect = TRUE)
{
    $output = NULL;
    $ip = $_SESSION['ip'];

    $purpose = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support = array("country", "countrycode", "state", "region", "city", "location", "address");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );
    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city" => @$ipdat->geoplugin_city,
                        "state" => @$ipdat->geoplugin_regionName,
                        "country" => @$ipdat->geoplugin_countryName,
                        "country_code" => @$ipdat->geoplugin_countryCode,
                        "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}

// Save address into user's profile

$_SESSION['location'] = $ip_full_address = ipLocation("Visitor", "Address");
$ip_country = ipLocation("Visitor", "Country Code");

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

// Notify unwanted visits

if ($ip_country == "AL") {

    // Send mail to admin with user's data
    $to = 'info@alaindtanigawa.com';

    $body = "You have a visitor from:\n=========================================\n Albania \n=========================================\n\n";

    $subject = "ADT | WARNING: UNWANTED VISITOR";

    // Send mail
    mail($to, $subject, $body);

    // Redirect to Paypal-access page
    ?>
    <script> window.location.replace("<?php echo URL; ?>" + "/access_SHDK677662GJ32S")</script>";
    <?php
}

if ($ip == "184.154.76.12" || $ip == "2a00:5ba0:10:2242:3c52:7dff:fee6:7714") {
    ?>
    <script> window.location.replace("<?php echo URL; ?>" + "/access_SHDK677662GJ32S")</script>";
    <?php
}

/*
 * Example 1
 *
echo ipLocation("Visitor", "Country"); // India
echo ipLocation("Visitor", "Country Code"); // IN
echo ipLocation("Visitor", "State"); // Andhra Pradesh
echo ipLocation("Visitor", "City"); // Proddatur
echo ipLocation("Visitor", "Address"); // Proddatur, Andhra Pradesh, India

print_r(ipLocation("Visitor", "Location")); // Array ( [city] => Proddatur [state] => Andhra Pradesh [country] => India [country_code] => IN [continent] => Asia [continent_code] => AS )


 * Example 2
 *
echo ipLocation("173.252.110.27", "Country"); // United States
echo ipLocation("173.252.110.27", "Country Code"); // US
echo ipLocation("173.252.110.27", "State"); // California
echo ipLocation("173.252.110.27", "City"); // Menlo Park
echo ipLocation("173.252.110.27", "Address"); // Menlo Park, California, United States

print_r(ipLocation("173.252.110.27", "Location")); // Array ( [city] => Menlo Park [state] => California [country] => United States [country_code] => US [continent] => North America [continent_code] => NA )

*/


//
// INPUT VALIDATION
//

// A function that validates form data by removing/replacing unwanted characters for better security
function validateInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>

