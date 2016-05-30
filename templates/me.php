<?php require __DIR__ . '/header.php'; ?>
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
<?php require __DIR__ . '/footer.php'; ?>
