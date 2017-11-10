<?php


namespace rsu\service\mail;

use Phalcon\Mvc\User\Component;
use rsu\service\mail\MailerException;

class Mailer extends Component
{
    private $host;

    private $port;

    private $username;

    private $password;

    private $from;

    /**
     * Массив $options должен содержать следующие параметры:
     *   host     - адрес SMTP-сервера;
     *   port     - порт SMTP-сервера;
     *   username - имя пользователя для авторизации на SMTP-сервере (адрес отправителя);
     *   password - пароль пользователя для авторизации на SMTP-сервере;
     *   from     - имя отправителя.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->host     = isset($options['host']) ? $options['host'] : '';
        $this->port     = isset($options['port']) ? $options['port'] : '';
        $this->username = isset($options['username']) ? $options['username'] : '';
        $this->password = isset($options['password']) ? $options['password'] : '';
        $this->from     = isset($options['from']) ? $options['from'] : '';
    }

    /**
     * @param string      $to
     * @param string      $subject
     * @param string      $message
     * @param string|null $fromEmail
     * @param string|null $fromName
     * @return void
     * @throws MailerException
     */
    public function sendMail($to, $subject, $message, $fromEmail = null, $fromName = null)
    {
        $localhost = 'localhost';
        $rn = "\r\n";

        $connect = fsockopen($this->host, $this->port, $errno, $errstr, 20);
        if (empty($connect)) {
            if (empty($errstr)) {
                $errstr = 'Failed to connect.';
            }
            throw new MailerException($errstr, $errno);
        }
        fgets($connect, 515);

        if ($fromEmail === null) {
            $fromEmail = $this->from;
            $fromName = $this->username;
        }

        $headers = [
            'HELO ' . $localhost,
            'AUTH LOGIN',
            base64_encode($this->username),
            base64_encode($this->password),
            'MAIL FROM: <' . $this->username . '>',
            'RCPT TO: <' . $to . '>',
            'DATA',

            'Subject: =?utf-8?B?' . base64_encode($subject) . '?=' . $rn .
            'Organization: РСУ 7 Управляющая компания' . $rn .
            'MIME-Version: 1.0' . $rn .
            'Content-type: text/html; charset=utf-8' . $rn .
            'X-Priority: 1' .  $rn .
//            'X-MSMail-Priority: High' .  $rn .
            'Importance: High' .  $rn .
            'To: ' . $to . $rn .
            'From: =?utf-8?B?' . base64_encode($fromEmail) . '?= <' . $fromName . '>' . $rn . $rn .
            $message . $rn . '.',

            'QUIT'
        ];

        foreach ($headers as $str) {
            fputs($connect, $str . $rn);
            $response = fgets($connect, 515);
            $responseCode = (int) $response;

            if ($responseCode < 100 || $responseCode > 399) {
                $exception = new MailerException(rtrim((string) $response), $responseCode);
                trigger_error($exception, E_USER_WARNING);
                throw new $exception;
            }
        }
    }
}