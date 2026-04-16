<?php
$cFile = curl_file_create('c:/Users/Amal/AgriGo/user-qrs/user_10_khairicha_gmail.com.png');
$post = array('file'=> $cFile);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,'http://api.qrserver.com/v1/read-qr-code/');
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);
print_r($result);
