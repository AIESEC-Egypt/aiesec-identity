<?php
/**
 * index.php
 * Front Page of AIESEC Identity with informations about the current user
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 */

ini_set("session.gc_maxlifetime", 604800);
ini_set("session.gc_divisor", "1");
ini_set("session.gc_probability", "1");
session_save_path(realpath(dirname(__FILE__) . '/../sessions'));
session_start();

if(!isset($_SESSION['gis-identity-session']) || !file_exists($_SESSION['gis-identity-session'])) :
    header('Location: login.php');
else :
    require_once(dirname(__FILE__) . '/../plugins/plugin.runner.php');
    require_once(dirname(__FILE__) . '/../config.php');

    $sites = array();
    $scopes = array();
    $error = false;

    // connect to mysql database
    $conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
    if (!$conn) {
        $error = "Connection to Database failed";
        trigger_error("Could not connect to database: " . $conn->error, E_USER_ERROR);
    } else {
        if ($result = $conn->query("SELECT `scope`.`name` as scope, GROUP_CONCAT(`role`.`name` SEPARATOR ', ') as roles FROM `persons_scopes` LEFT JOIN `scopes` scope ON `scope`.`id`=`persons_scopes`.`scope_id` LEFT JOIN `roles` role ON `role`.`id`=`persons_scopes`.`role_id` WHERE `persons_scopes`.`person_id` = " . intval($_SESSION['person_id']) . " AND (`expires_at` > NOW() OR `expires_at` IS NULL) GROUP BY `persons_scopes`.`scope_id`")) {
            if($result->num_rows > 0) {
                while($row = $result->fetch_row()) {
                    $scopes[$row[0]] = $row[1];
                }
            }
            
            // run plugins
            $p = new PluginRunner($ACTIVE_PLUGINS);
            $sites = $p->onListSites($scopes, $_SESSION['person_id'], $conn);
        } else {
            $error = "Database Error";
            trigger_error("Could not retrieve scopes and roles for user: " . $conn->error, E_USER_ERROR);
        }
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AIESEC Identity</title>
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
            <div class="col-xs-6">
                <h1>AIESEC Identity</h1>
            </div>
            <div class="col-xs-6">
                <a class="btn btn-primary pull-xs-right" href="logout.php">Logout</a>
            </div>
        </div>
        <div class="row">
            <?php if($error): ?>
                <div class="col-xs-12 alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <div class="jumbotron col-xs-12 col-md-6">
                Dear <?php echo $_SESSION['full_name']; ?>,<br />
                <?php if(count($sites) > 0): ?>
                it looks like you are lost here, please go to one of the sites you have access to:
                <ul>
                    <?php foreach($sites as $site): ?>
                    <li><a href="<?php echo $site[0]; ?>"><?php echo $site[1]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                at the moment you don't have access to any site!
                <?php endif; ?>
            </div>
            <div class="col-xs-12 col-md-6">
                <h2>Scopes and Roles</h2>
                <small class="text-muted">In our user management you can have different roles in specific scopes. This looks a bit technical, because it is. Nevertheless it can give you a sense what rights your account have and is maybe needed by the support team, if there is a problem.</small>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Scope</th>
                            <th>Roles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($scopes as $scope => $roles): ?>
                        <tr>
                            <td><?php echo $scope; ?></td>
                            <td><?php echo implode(', ', $roles); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?>
