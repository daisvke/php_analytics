<?php
// A function that validates form data by removing/replacing unwanted characters for a better security
function validateInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check string that user entered in login fields
function input_check_login($db, $ip)
{
    if (isset($_POST['login1'])) {
        $_POST['login1'] = validateInput($_POST['login1']);

        $req = $db->prepare('
              UPDATE users
              SET attempted_inputs = concat("[login1] ", :input, "; ",
              attempted_inputs)
              WHERE ip = :ip
            ');
        $req->execute(array(
            ':input' => $_POST['login1'],
            ':ip' => $ip
        ));
        $req->closeCursor();
    } elseif (isset($_POST['login2'])) {
        $_POST['login2'] = validateInput($_POST['login2']);

        $req = $db->prepare('
              UPDATE users
              SET attempted_inputs = concat("[login2] ", :input, "; ",
              attempted_inputs)
              WHERE ip = :ip
            ');
        $req->execute(array(
            ':input' => $_POST['login2'],
            ':ip' => $ip
        ));
        $req->closeCursor();
    }
}

// Check selected elements by user on the page
function input_check_query($db, $ip)
{
    // Array containing all possible categories
    $categories = array(
        "portrait", "photojournalism", "landscape / still life",
        "wedding", "street photography"
    );
    // Array containing all possible picture types
    $pictureTypes = array("color", "black_and_white");
    // If the picture category is set
    if (isset($_GET['cat']) && in_array($_GET['cat'], $categories)) {
        $_GET['cat'] = validateInput($_GET['cat']);

        $req = $db->prepare('
                       UPDATE users
                       SET queries = concat("[cat_query] ", :cat, "; ", queries)
                       WHERE ip = :ip
                    ');
        $req->execute(array(
            ':cat' => $_GET['cat'],
            ':ip' => $_SESSION['ip']
        ));
    }
    // If the picture type is set
    if (isset($_POST['type']) && in_array($_POST['type'], $pictureTypes)) {
        $_POST['type'] = validateInput($_POST['type']);

        $req = $db->prepare('
                       UPDATE users
                       SET queries = concat("[type_query] ", :cat, "; ",
                       queries)
                       WHERE ip = :ip
                    ');
        $req->execute(array(
            ':cat' => $_POST['type'],
            ':ip' => $_SESSION['ip']
        ));
    }
}
?>
