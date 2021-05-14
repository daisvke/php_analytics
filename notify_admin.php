<?php
// Send email to notify the admin of the visit of a particular user
function notify_visit_name($name, $datetime)
{
	$to = 'my@mail.com';
	$name = validateInput($name);
	$body = "$name visited your website !\n"
	. "=========================================\n"
	. " Date-time: $datetime\n"
	. "=========================================\n";
	$subject = "MYWEBSITE | That user came";
	// Send mail
	mail($to, $subject, $body);
}

// Notify of unwanted visits and redirect to another page
// By county code
function notify_visit_country($datetime, $code, $page = "/mypage.php")
{
    $to = 'my@mail.com';
    $body = "Visit notification:\n"
    . "=========================================\n"
    . " ISO country code: $code\n" 
    . " Date-time: $datetime\n"
    . "=========================================\n";
    $subject = "MYWEBSITE | WARNING: UNWANTED VISITOR";
    // Send mail
    mail($to, $subject, $body);
    // Redirect to mypage.php
    ?>
    <script>window.location.replace("<?php echo URL; echo $page; ?>")</script>;
    <?php
}

// By IP address
function notify_visit_ip($ip, $datetime, $page = "/mypage.php")
{ 
	$to = 'my@mail.com';
	$body = "Visit notification:\n"
	. "=========================================\n"
	. " IP: $ip\n Date-time: $datetime\n"
	. "=========================================\n";
	$subject = "MYWEBSITE | WARNING: UNWANTED VISITOR";
	// Send mail
	mail($to, $subject, $body);
	// Redirect to mypage.php
	?>
	<script>
		window.location.replace("<?php echo URL; echo $page; ?>")
	</script>;
	<?php
}
?>
