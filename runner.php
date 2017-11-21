<?php
use eiriksm\CosyComposer\CosyComposer;

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
$cosy->run();
$data = [];
$output = $cosy->getOutput();
$json = [];
foreach ($output as $type => $message) {
  if (empty($message)) {
    continue;
  }
  $json[] = [
    'message' => $message->getMessage(),
    'timestamp' => $message->getTimestamp(),
  ];
}
print json_encode($json);
