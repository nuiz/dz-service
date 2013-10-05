<?php

// Put your device token here (without spaces):
$deviceToken = '557595f96b5d0fd00bd7303241d440ad90a0a9433da563ae89d34cca5a6f54b2';

// Put your private key's passphrase here:
$passphrase = 'DanceZone';

// Put your alert message here:
$message = 'Hello test push notification form Dance Zone app';

////////////////////////////////////////////////////////////////////////////////

$ctx = stream_context_create();
stream_context_set_option($ctx, 'ssl', 'local_cert', 'Certificates.pem');
stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
$fp = stream_socket_client(
	'ssl://gateway.sandbox.push.apple.com:2195', $err,
	$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

if (!$fp)
	exit("Failed to connect: $err $errstr" . PHP_EOL);

echo 'Connected to APNS' . PHP_EOL;

// Create the payload body
$body = array();
$body['aps'] = array(
	'alert' => $message,
	'sound' => 'default'
	);
$body['dz'] = array(
    'action'=> 'news',
    'news'=> array('id'=> 100)
    );

// Encode the payload as JSON
$payload = json_encode($body);

// Build the binary notification
$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
$result = fwrite($fp, $msg, strlen($msg));

if (!$result)
	echo 'Message not delivered' . PHP_EOL;
else
	echo 'Message successfully delivered' . PHP_EOL;

// Close the connection to the server
fclose($fp);
