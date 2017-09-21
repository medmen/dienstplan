<?php
$username = null;
$password = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    require_once('config/people.php');

    if(!empty($_POST["username"]) && !empty($_POST["password"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];

        if(array_key_exists($username, $config['people']) and
            password_verify($password, $config['people'][$username]['pw'])) {
            // login granted
            session_start();
            $_SESSION['key'] = session_id();
            $_SESSION['username'] = $username;
            header('Location: index.php');
        } else {
            $msg = '<p style="color:red">login und/oder passwort stimmen nicht.<br> Bitte nochmal versuchen. <br> Falls Sie ihr Passwort vergessen haben, kann der Administrator ein neues vergeben. </p>';
            show_login_form($msg);
        }

    } else {
        $msg = '<p style="color:red">login und/oder passwort nicht ausgefüllt.<br> Bitte nochmal versuchen. <br> Falls Sie ihr Passwort vergessen haben, kann der Administrator ein neues vergeben. </p>';
        show_login_form($msg);
    }
} else {
    show_login_form();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if('logout' === $_GET['logout']) {
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
    }
}

function show_login_form($msg) {
include 'header.html';
    echo <<< EOT
    <html lang="de">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title>Creating a simple to-do application - Part 1</title>
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
                <input type="submit" value="Login">
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
