# php_analytics

php_analytics is a web analytics module.<br />
Its purpose is to determine and store users' identity/behavior by checking their IP addresses,<br />
the pages they have visited, the posts they have made, etc.

### Description

* This module checks if the IP of the user is already registred in the database.
  If it is the case, it is going to update its associated "profile" which columns are :
    - ip
    - name (has to be guessed and entered by admin)
    - date_time (visited datetime)
    - location (concatenated list of all the locations (according to user's ip) from which the user visited the page)
    - viewed_pages (concatenated list of all the pages visited by the user on the website)
    - queries (queries made by user on search fields)
    - attempted_inputs (tried passwords on login pages (login1 and login2 as examples))

* In most cases the original IP of a user surfing behind proxies is not going to be revealed with this code.
  We tried using the code above and realized that SERVER['REMOTE_ADDR'], which would only wrap the IP address
  of the last used proxy, contained the only reliable value:
  ```php
  function get_ip_address(){
    $ip = 0;

    foreach (array(
                 'HTTP_VIA ', 'HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP',
                 'HTTP_INCAP_CLIENT_IP', 'HTTP_X_SUCURI_CLIENTIP',
                 'HTTP_FASTLY_CLIENT_IP', 'HTTP_CLIENT_IP',
                 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR_IP ', 'VIA',
                 'HTTP_FORWARDED_FOR', 'X_FORWARDED_FOR', 'FORWARDED_FOR',
                 'X_FORWARDED FORWARDED', 'CLIENT IP', 'FORWARDED_FOR_IP',
                 'HTTP_FORWARDED', 'REMOTE_ADDR', 'HTTP_PROXY_CONNECTION'
             ) as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip); // just to be safe
                if (filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    ) !== false) {
                    echo "ip: $ip     key: $key<br />";
                    return $ip;
                }
            }
        }
    }
    return $ip;
  }
* The full location of the user is determined by an API (http://ipinfo.io/) in the following form: 
```
country_code, region, city
```
Unfortunately it is not always accurate.<br />
For more accuracy, you would have to ask permission to the visitor to reveal his location,<br />
which is not what we want here.

* The following type of database is going to be needed :
  <p align="center">
    <img src="/screenshot/phpMyAdmin.png" width="80%" />
  </p>

* Finally, notify_admin.php sends a notification email to the admin.
  
