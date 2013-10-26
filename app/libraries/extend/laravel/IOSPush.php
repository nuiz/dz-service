<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 4/10/2556
 * Time: 13:03 น.
 * To change this template use File | Settings | File Templates.
 */

namespace Extend\Laravel;

use Illuminate\Support\Facades\Log;

class IOSPush {
    private static $instance = null;
    private $fp = null;

    private function __construct(){
        $this->makeFp();
    }

    private function makeFp(){
        $passphrase = 'DanceZone';
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'Certificates.pem');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
        $this->fp = stream_socket_client(
            'ssl://gateway.sandbox.push.apple.com:2195', $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$this->fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);
    }

    public static function push($deviceToken, $message, $data = array()){
        if(is_null(self::$instance)){
            self::$instance = new IOSPush();
        }

// Create the payload body
        $body = array();
        $body['aps'] = array(
            'alert' => $message,
            'sound' => 'default'
        );
        $body['dz'] = $data;

// Encode the payload as JSON
        $payload = json_encode($body);

// Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
        $result = fwrite(self::$instance->fp, $msg, strlen($msg));

        if (!$result)
            error_log('Message not delivered' . PHP_EOL);
    }

    public function __destruct(){
        // Close the connection to the server
        if(!is_null($this->fp))
            fclose($this->fp);
    }
}