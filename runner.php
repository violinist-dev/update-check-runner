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
use violinist\LicenceCheck\LicenceChecker;

require_once "vendor/autoload.php";

foreach (['token_url', 'fork_to'] as $key) {
    if (!empty($_SERVER[$key])) {
        continue;
    }
    $_SERVER[$key] = '';
}

$user_token = $_SERVER['user_token'];
$fork_to = $_SERVER['fork_to'];
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

$valid_public_keys = [
    // Production key for violinist.io.
    '7f21f9fdf700d388dda33e1463b541c7ccbdcbf9e35a120fbfbf97c0dccc2385',
    // CI key for violinist.io.
    '8e342a2dd1229474e5b3e0a9553e0af239acf42eabf5cf12c8bdb5dc864fbe7e',
];

$pre_run_messages = [];

if (!empty($_SERVER['LICENCE_KEY'])) {
    $pre_run_messages[] = new Message('Licence key found in environment. Checking validity.', Message::COMMAND);
    $has_valid_key = false;
    foreach ($valid_public_keys as $valid_public_key) {
        $checker = new LicenceChecker($valid_public_key);
        $checked = LicenceChecker::createFromLicenceAndKey($_SERVER['LICENCE_KEY'], $valid_public_key);
        if ($checked->isValid()) {
            $has_valid_key = true;
            $pre_run_messages[] = new Message('Licence key is valid for public key ' . $valid_public_key, Message::COMMAND);
            break;
        }
    }
    if (!$has_valid_key) {
        $pre_run_messages[] = new Message('Licence key is not valid for any of the known public keys.', Message::COMMAND);
        $pre_run_messages[] = new Message('Licence key: ' . $_SERVER['LICENCE_KEY'], Message::COMMAND);
    } else {
        $pre_run_messages[] = new Message('Licence key expiry: ' . date('c', $checked->getPayload()->getExpiry()), Message::COMMAND);
        $pre_run_messages[] = new Message('Licence key data: ' . json_encode($checked->getPayload()->getData()), Message::COMMAND);
    }
}

$container = new ContainerBuilder();
$container->register('logger', 'Wa72\SimpleLogger\ArrayLogger');
$container->register('process.factory', 'eiriksm\CosyComposer\ProcessFactory');
$container->register('command', 'eiriksm\CosyComposer\CommandExecuter')
    ->addArgument(new Reference('logger'))
    ->addArgument(new Reference('process.factory'));
$container->register('cosy', 'eiriksm\CosyComposer\CosyComposer')
    ->addArgument(new Reference('command'))
    ->addMethodCall('setLogger', [new Reference('logger')])
    ->addMethodCall('setUrl', [$url]);

/* @var \eiriksm\CosyComposer\CosyComposer $cosy */
$cosy = $container->get('cosy');
$cosy->setGithubAuth($user_token, 'x-oauth-basic');
$cosy->setUserToken($user_token);
$cosy->setForkUser($fork_to);
$cosy->setProject($project);
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
} else {
    if (file_exists(__DIR__ . '/.version')) {
        $_SERVER['queue_runner_revision'] = file_get_contents(__DIR__ . '/.version');
    }
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
// Prepend the pre-run messages we have stored.
$output = array_merge($pre_run_messages, $output);
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
