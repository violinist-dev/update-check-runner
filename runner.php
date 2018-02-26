<?php
use eiriksm\CosyComposer\Message;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

require_once "vendor/autoload.php";

$token = $_SERVER['token'];
$slug = $_SERVER['slug'];
$user_token = $_SERVER['user_token'];
$fork_to = $_SERVER['fork_to'];
$fork_user = $_SERVER['fork_user'];
$fork_mail = $_SERVER['fork_mail'];
$container = new ContainerBuilder();
$container->register('app', \Composer\Console\Application::class);
$container->register('output', \eiriksm\ArrayOutput\ArrayOutput::class);
$container->register('logger', 'Wa72\SimpleLogger\ArrayLogger');
$container->register('process.factory', 'eiriksm\CosyComposer\ProcessFactory');
$container->register('command', 'eiriksm\CosyComposer\CommandExecuter')
  ->addArgument(new Reference('logger'))
  ->addArgument(new Reference('process.factory'));
$container->register('cosy', 'eiriksm\CosyComposer\CosyComposer')
  ->addArgument($token)
  ->addArgument($slug)
  ->addArgument(new Reference('app'))
  ->addArgument(new Reference('output'))
  ->addArgument(new Reference('command'));

/** @var \eiriksm\CosyComposer\CosyComposer $cosy */
$cosy = $container->get('cosy');
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
