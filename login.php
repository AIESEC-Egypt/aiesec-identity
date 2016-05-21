<?php
/**
 * login.php
 * Login Form and login routine
 * 
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 */

$error = false;

if(!is_writeable(dirname(__FILE__) . '/sessions/')) {
    $error = "Fatal Error. Please contact the administrator";
} elseif($_SERVER['REQUEST_METHOD'] == "POST") {
    if(isset($_POST['username']) && isset($_POST['password'])) {
        require_once(dirname(__FILE__) . '/plugins/plugin.runner.php');
        require_once(dirname(__FILE__) . '/PHP-GIS-Wrapper/gis-wrapper/AuthProviderCombined.php');
        require_once(dirname(__FILE__) . '/config.php');

        // create plugin runner
        $PR = new PluginRunner($ACTIVE_PLUGINS);

        // login to GIS
        $user = new \GIS\AuthProviderCombined($_POST['username'], $_POST['password'], VERIFY_PEER);
        $user->setSession(dirname(__FILE__) . '/sessions/' . md5(microtime()) . ".txt");
        try {
            $user->getToken();
        } catch (\GIS\InvalidCredentialsException $e) {
            $error = "Username or Password invalid";
        } catch (Exception $e) {
            $error = "There was an unknown error while checking your credentials. Most probably this is a temporary error.";
        }

        // if authentication was successful
        if(!$error) {
            // connect to mysql database
            $conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
            if (!$conn) {
                $error = "Connection to Database failed";
            } else {
                // lookup the person in the database
                $person = $conn->query("SELECT * from `persons` WHERE `id`=" . intval($user->getCurrentPerson()->person->id) . " LIMIT 1;");

                // add the person to the database if it is not in there yet, or update the last login date
                if($person->num_rows !== 1) {
                    $query = "INSERT INTO `persons` (`id`, `email`, `first_name`, `middle_name`, `last_name`, `full_name`,  `last_login`) VALUES (";
                    $query .= intval($user->getCurrentPerson()->person->id) . ", ";
                    $query .= "'" . $conn->real_escape_string($user->getCurrentPerson()->person->email) . "', ";
                    $query .= "'" . $conn->real_escape_string($user->getCurrentPerson()->person->first_name) . "', ";
                    $query .= (isset($user->getCurrentPerson()->person->middle_name)) ? "'" . $conn->real_escape_string($user->getCurrentPerson()->person->middle_name) . "', " : "NULL, ";
                    $query .= "'" . $conn->real_escape_string($user->getCurrentPerson()->person->last_name) . "', ";
                    $query .= "'" . $conn->real_escape_string($user->getCurrentPerson()->person->full_name) . "', ";
                    $query .= "NOW());";

                    if(!$PR->onFirstLogin($user, $conn)) {
                        $error = "Plugin prevented user creation";
                        $conn->close();
                    }
                } else {
                    $query = "UPDATE `persons` SET `last_login`=NOW() WHERE `id`=" . intval($user->getCurrentPerson()->person->id);
                }

                if(!$error && $conn->query($query) === TRUE) {
                    if($PR->onLogin($user, $conn)) {
                        // close mysql connection
                        $conn->close();

                        // start session
                        session_start();

                        // generate redirect link
                        $redirect = 'index.php';
                        if(isset($_SESSION['redirect'])) $redirect = $_SESSION['redirect'];

                        // clear session
                        $_SESSION = array();

                        // set session data
                        $_SESSION['person_id'] = intval($user->getCurrentPerson()->person->id);
                        $_SESSION['full_name'] = $user->getCurrentPerson()->person->full_name;
                        $_SESSION['gis-identity-session'] = $user->getSession();

                        // redirect
                        header('Location: ' . $redirect);
                    } else {
                        $error = "Plugin prevented login";
                        $conn->close();
                    }
                } else {
                    $error = "Database Error";
                    trigger_error("Database Error: " . $conn->error, E_USER_ERROR);
                    $conn->close();
                }
            }
        }
    } else {
        $error = "Invalid Credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AIESEC Identity | Login</title>
    <!-- Bootstrap core CSS-->
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries-->
    <!--if lt IE 9
    script(src='https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js')
    script(src='https://oss.maxcdn.com/respond/1.4.2/respond.min.js')
    -->
    <style>
        header {
            background: #235192;
            padding: 21px 0 10px;
            font: bold 13px/15px Helvetica, Arial, sans-serif;
            position: relative;
        }
    </style>
</head>
<body>
    <header>
        <div class="container"><a href="/"><img alt="Logo" src="assets/logo.png"></a></div>
    </header>
    <br>
    <div class="container">
        <div class="row">
            <div class="col-sm-6 col-sm-offset-3 jumbotron">
                <h1>AIESEC Identity</h1><br/>
                <form method="POST" class="form-signin">
                    <?php if($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    <div class="form-group row">
                        <label for="inputUser" class="col-sm-2 form-control-label">Email</label>
                        <div class="col-sm-10">
                            <input id="inputUser" type="text" placeholder="Username" name="username" class="form-control"/>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputPassword" class="col-sm-2 form-control-label">Password</label>
                        <div class="col-sm-10">
                            <input id="inputPassword" type="password" placeholder="Password" name="password" class="form-control"/>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-lg btn-primary">Sign in</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>