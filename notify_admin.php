<?php
// Send email to notify the admin of the visit of a particular user
function notify_visit_name($name, $datetime)
{
    if ($name == "") {
        $to = 'info@d.zd.lu';
        $name = validateInput($name);
        $body = "$name visited your website !\n=========================================\n Date-time: $datetime\n=========================================\n\n";
        $subject = "d. | That user came";
// Send mail
        mail($to, $subject, $body);
    }
}

// Notify of unwanted visits and redirect to another page
// By county code
function notify_visit_country($country_code, $datetime, $code, $page = "/access_SHDK677662GJ32S.php")
{
    if ($country_code == $code) {
        $to = 'info@d.zd.lu';
        $body = "Visit notification:\n=========================================\n ISO country code: $code\n Date-time: $datetime\n =========================================\n\n";
        $subject = "d. | WARNING: UNWANTED VISITOR";
        // Send mail
        mail($to, $subject, $body);
        // Redirect to Paypal-access page
        ?>
        <script> window.location.replace("<?php echo URL; echo $page; ?>")</script>";
        <?php
    }
}

// By IP address
function notify_visit_ip($ip, $datetime, $page = "/access_SHDK677662GJ32S.php")
{
    if ($ip == "184.154.76.12" || $ip == "2a00:5ba0:10:2242:3c52:7dff:fee6:7714") {
        $to = 'info@d.zd.lu';
        $body = "Visit notification:\n=========================================\n IP: $ip\n Date-time: $datetime\n=========================================\n\n";
        $subject = "d. | WARNING: UNWANTED VISITOR";
        // Send mail
        mail($to, $subject, $body);
        // Redirect to Paypal-access page
        ?>
        <script> window.location.replace("<?php echo URL; echo $page; ?>")</script>";
        <?php
    }
}
?>
