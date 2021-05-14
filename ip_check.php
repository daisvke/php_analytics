<?php
function get_ip_address()
{
    $ip = 0;

    $ip = validateInput($_SERVER['REMOTE_ADDR']);
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE
		    | FILTER_FLAG_NO_RES_RANGE) !== false)
        return $ip;
    return 0;
}

function ipLocation($ip, $key)
{
    $output = NULL;
    $details = NULL;

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
        if ($key == "full") {
            return "$details->country, $details->region, $details->city";
        } else {
            return $details->$key;
        }
    }
    return NULL;
}

/*
$ curl ipinfo.io/8.8.8.8
{
  "ip": "8.8.8.8",
  "hostname": "google-public-dns-a.google.com",
  "loc": "37.385999999999996,-122.0838",
  "org": "AS15169 Google Inc.",
  "city": "Mountain View",
  "region": "CA",
  "country": "US",
  "phone": 650
}
*/
?>
