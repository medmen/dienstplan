<?php
namespace Dienstplan;

// Load Composer packages, and the car.php file
require_once(__DIR__ . '/vendor/autoload.php');


$username = null;
$password = null;

if(isset($_SERVER['REQUEST_METHOD'])) {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            require_once('config/people.php');
            if (isset($_POST["purpose"]) and "changepw" == $_POST["purpose"]) {
                // check if a user is logged in
                session_start();
                if(!isset($_SESSION['username'])) {
                    session_destroy();
                    $msg.= '<p style="color:red">Error: it seems no one is logged in. so U should not be here</p>';
                    show_message_only($msg);
                    break;
                }

                $username = $_SESSION["username"];
                $old_password = $_POST["old_password"];
                $new_password1 = $_POST["new_password1"];
                $new_password2 = $_POST["new_password2"];

                if (!empty($_POST["new_password1"]) and ($_POST["new_password2"] !== $_POST["new_password1"])) {
                    $msg = '<p style="color:red">Die neuen passwörter stimmen nicht überein!</p>';
                    show_message_only($msg);
                    break;
                }
                
                if (array_key_exists($username, $config['people']) and password_verify($old_password, $config['people'][$username]['pw'])) {
                    $config['people'][$username]['pw'] =  password_hash($new_password2);
                    $file_content = "<?php\nglobal \$config;\n";
                    $file_content.= '$config[\'people\'] = '.var_export($config['people'], true).";\n";
                    if (false == file_put_contents('config/people.php', $file_content)) {
                        $msg = '<p style="color:red">Zugriffsfehler beim Ändern des Passworts</p>';
                        show_message_only($msg);
                        break;
                    }

                    $msg = '<p style="color:green"> Passwort erfolgreich geändert! <br> Ihr Passwort ist nirgendwo im Systemim Klartext gespeichert, es kann daher nicht wiederhergestellt werden<br> Falls Sie ihr Passwort einmal vergessen sollten, kann der Administrator ein neues vergeben. </p>';
                    $msg.= '<p style="color:red">TODO: IMplement real PW update!</p>';
                    show_message_only($msg);
                    break;
                } else {
                    $msg.= '<p style="color:red">'.$username.', the original password you provided was wrong</p>';
                    show_message_only($msg);
                    break;
                }
                $msg.= '<p style="color:red">Unknown Error while changin password, no changes were made!</p>';
                show_message_only($msg);
                break;
            }

            if (!empty($_POST["username"]) && !empty($_POST["password"])) {
                $username = $_POST["username"];
                $password = $_POST["password"];

                if (array_key_exists($username, $config['people']) and
                    password_verify($password, $config['people'][$username]['pw'])) {
                    // login granted
                    session_start();
                    $_SESSION['key'] = session_id();
                    $_SESSION['username'] = $username;
                    header('Location: index.php');
                    break;
                } else {
                    $msg = '<p style="color:red">login und/oder passwort stimmen nicht.<br> Bitte nochmal versuchen. <br> Falls Sie ihr Passwort vergessen haben, kann der Administrator ein neues vergeben. </p>';
                    show_login_form($msg);
                    break;
                }
            } else {
                $msg = '<p style="color:red">login und/oder passwort nicht ausgefüllt.<br> Bitte nochmal versuchen. <br> Falls Sie ihr Passwort vergessen haben, kann der Administrator ein neues vergeben. </p>';
                show_login_form($msg);
                break;
            }
            break;

        case 'GET':
            if ('logout' === $_GET['logout']) {
                $_SESSION = array();
                // Session-Cookie löschen!
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params["path"],
                        $params["domain"], $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                header('Location: index.php');
                break;
            }

            if ('changepw' === $_GET['changepw']) {
                show_changepw_form();
                break;
            }

        default:
            // $msg = '<p style="color:red"> Entschuldigung - ein interner Fehler ist aufgetreten ;-( </p>';
            // show_message_only($msg);
            show_login_form();
    }
} else {
    show_login_form();
}


function show_message_only($msg = false) {
    include 'header.html';
    echo <<< EOT
    <html lang="de">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title>Dienstplan - ! Fehler !</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
    <div class="container">
EOT;
    include 'navigation.php';
    echo <<< EOT
        <header id="banner">
            <h1>Fehler</h1>
        </header>
        <section id="content">
            $msg
        </section>
        <section id="goback">
            <h2> Was jetzt?</h2>
            <ul>
                <li><a href="{$_SERVER['PHP_SELF']}">Diese Seite nochmal laden</a></li>
                <li><a href="/">zur Startseite</a></li>
            </ul>
        </section>
        
    
        <footer id="footer">
            <details>
                <summary>Simple Login Stuff</summary>
                <p>Based on Idea by Jonathan Schnittger. All Rights Reserved.</p>
            </details>
        </footer>
    </div>
    </body>
    </html>
EOT;
}

function show_login_form($msg = false) {
include 'header.html';
    echo <<< EOT
    <html lang="de">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title>Dienstplan - Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
    <div class="container">
EOT;
    include 'navigation.php';
    echo <<< EOT
        <header id="banner">
            <h1>Login</h1>
        </header>
        <section id="content">
            $msg
            <form id="login" method="post">
                <label for="username">Username:</label>
                <input id="username" name="username" type="text" required>
                <label for="password">Password:</label>
                <input id="password" name="password" type="password" required>
                <br />
                <input type="submit" name="purpose" value="Login">
            </form>
        </section>
    
        <footer id="footer">
            <details>
                <summary>Simple Login Stuff</summary>
                <p>Based on Idea by Jonathan Schnittger. All Rights Reserved.</p>
            </details>
        </footer>
    </div>
    </body>
    </html>
EOT;
}

function show_changepw_form($msg = false) {
    include 'header.html';
    echo <<< EOT
    <html lang="de">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title>Dienstplan - Passwort ändern</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
    <div class="container">
EOT;
    include 'navigation.php';
    echo <<< EOT
        <header id="banner">
            <h1>Passwort ändern</h1>
        </header>
        <section id="content">
            $msg
            <form id="login" method="post" target="{$_SERVER['PHP_SELF']}">
                <label for="old_password">Altes Passwort:</label>
                <input id="old_password" name="old_password" type="password" required>
                <br />
                <label for="new_password1">Neues Passwort:</label>
                <input id="new_password1" name="new_password1" type="password" required>
                <br />
                <label for="new_password2">Neues Passwort (nochmal):</label>
                <input id="new_password2" name="new_password2" type="password" required>
                <br />
                <input type="submit" name="purpose" value="changepw">
            </form>
        </section>
    
        <footer id="footer">
            <details>
                <summary>Simple Login Stuff</summary>
                <p>Based on Idea by Jonathan Schnittger. All Rights Reserved.</p>
            </details>
        </footer>
    </div>
    </body>
    </html>
EOT;
}
