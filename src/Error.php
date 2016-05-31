<?php
namespace AIESEC\Identity;


class Error extends \Exception
{
    /**
     * Error constructor.
     * @param int $code
     * @param string $message
     */
    public function __construct($code, $message) {
        parent::__construct($message, $code);
    }

    /**
     * render the error
     */
    public function output() {
        if($this->getCode() == 401) header('HTTP/1.0 401 Unauthorized');

        Template::run('error', ['code' => $this->getCode(), 'message' => $this->getMessage()]);
    }
}