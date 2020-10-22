<?php

/**
 * @file
 * Runner.
 *
 * @author eiriksm <eirik@morland.no>
 */

use eiriksm\CosyComposer\Message;
use eiriksm\GitInfo\GitInfo;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Dotenv\Dotenv;

require_once "vendor/autoload.php";

foreach (['slug', 'fork_user', 'fork_mail', 'token_url', 'fork_to'] as $key) {
    if (!empty($_SERVER[$key])) {
        continue;
    }
    $_SERVER[$key] = '';
}

$slug = $_SERVER['slug'];
$user_token = $_SERVER['user_token'];
$fork_to = $_SERVER['fork_to'];
$fork_user = $_SERVER['fork_user'];
$fork_mail = $_SERVER['fork_mail'];
$token_url = $_SERVER['token_url'];
$project = null;
$url = null;
$tokens = [];
if (!empty($_SERVER['tokens'])) {
    $tokens = @json_decode($_SERVER['tokens'], true);
}
if (!empty($_SERVER['project'])) {
    $project = @unserialize(@json_decode($_SERVER['project']));
}
if (!empty($_SERVER['project_url'])) {
    $url = $_SERVER['project_url'];
}
$container = new ContainerBuilder();
$container->register('app', \Composer\Console\Application::class);
$container->register('output', \eiriksm\ArrayOutput\ArrayOutput::class);
$container->register('logger', 'Wa72\SimpleLogger\ArrayLogger');
$container->register('process.factory', 'eiriksm\CosyComposer\ProcessFactory');
$container->register('command', 'eiriksm\CosyComposer\CommandExecuter')
    ->addArgument(new Reference('logger'))
    ->addArgument(new Reference('process.factory'));
$container->register('cosy', 'eiriksm\CosyComposer\CosyComposer')
    ->addArgument($slug)
    ->addArgument(new Reference('app'))
    ->addArgument(new Reference('output'))
    ->addArgument(new Reference('command'))
    ->addMethodCall('setLogger', [new Reference('logger')])
    ->addMethodCall('setUrl', [$url]);

/* @var \eiriksm\CosyComposer\CosyComposer $cosy */
$cosy = $container->get('cosy');
$cosy->setGithubAuth($user_token, 'x-oauth-basic');
$cosy->setUserToken($user_token);
$cosy->setForkUser($fork_to);
$cosy->setProject($project);
$cosy->setGithubForkAuth($fork_user, $fork_mail);
$cosy->setTokenUrl($token_url);
$cosy->setTokens($tokens);
$cosy
    ->setTmpDir(
        sprintf(
            '/tmp/violinist-%d-%s-%s',
            8,
            date('Y.m.d-H.i.s', time()),
            uniqid()
        )
    );
$cache_dir = $_SERVER['HOME'] . '/.cosy-cache';
if (!file_exists($cache_dir)) {
    mkdir($cache_dir);
}
$git = new GitInfo();
if ($hash = $git->getShortHash()) {
    $_SERVER['queue_runner_revision'] = $hash;
}
$cosy->setCacheDir($cache_dir);
$code = 0;
try {
    $env = new Dotenv();
    $env->load(__DIR__ . '/.env');
}
catch (Throwable $e) {
    // We tried.
}
try {
    $cosy->run();
    $output = $cosy->getOutput();
}
catch (Exception $e) {
    $output = $cosy->getOutput();
    $output[] = new Message('Caught Exception: ' . $e->getMessage(), Message::ERROR);
    $code = 1;
}
$json = [];
foreach ($output as $type => $message) {
    if (empty($message)) {
        continue;
    }
    $json[] = [
      'message' => $message->getMessage(),
      'context' => $message->getContext(),
      'timestamp' => $message->getTimestamp(),
      'type' => $message->getType(),
    ];
}
print json_encode($json);
exit($code);
