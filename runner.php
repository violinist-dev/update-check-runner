<?php
use eiriksm\CosyComposer\CosyComposer;
use eiriksm\CosyComposer\Message;

require_once "vendor/autoload.php";

$token = $_SERVER['token'];
$slug = $_SERVER['slug'];
$user_token = $_SERVER['user_token'];
$fork_to = $_SERVER['fork_to'];
$fork_user = $_SERVER['fork_user'];
$fork_mail = $_SERVER['fork_mail'];
$cosy = new CosyComposer($token, $slug);
$cosy->setGithubAuth($user_token, 'x-oauth-basic');
$cosy->setForkUser($fork_to);
$cosy->setGithubForkAuth($fork_user, $token, $fork_mail);
$cosy->setTmpDir(sprintf('/tmp/violinist-%d-%s-%s', 8, date('Y.m.d-H.i.s', time()), uniqid()));
$cosy->setCacheDir('/tmp/cosy-cache');
$code = 0;
try {
  $cosy->run();
  $output = $cosy->getOutput();
}
catch (Exception $e) {
  $output = $cosy->getOutput();
  $output[] = new Message('Caught Exception: ' . $e->getMessage());
  $code = 1;
}
$json = [];
foreach ($output as $type => $message) {
  if (empty($message)) {
    continue;
  }
  $json[] = [
    'message' => $message->getMessage(),
    'timestamp' => $message->getTimestamp(),
    'type' => $message->getType(),
  ];
}
print json_encode($json);
exit($code);
