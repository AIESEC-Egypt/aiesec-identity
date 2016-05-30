<?php require __DIR__ . '/header.php'; ?>
    <div class="container">
        <div class="row">
            <div class="col-sm-6 col-sm-offset-3 jumbotron">
                <h1>AIESEC Identity</h1><br/>
                <form method="POST" class="form-signin">
                    <?php if(isset($error)): ?>
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
<?php require __DIR__ . '/footer.php'; ?>