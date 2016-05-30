{
    "status": {
        <?php if(isset($code)) echo '"code": "' . $code . '"'; ?>
        <?php if(isset($error)) echo '"message": "' . $error . '"'; ?>
    }
}