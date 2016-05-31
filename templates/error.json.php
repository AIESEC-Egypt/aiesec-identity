{
    "status": {
        <?php if(isset($code)) echo '"code": "' . $code . '",' . PHP_EOL; ?>
        <?php if(isset($message)) echo '"message": "' . $message . '"' . PHP_EOL; ?>
    }
}