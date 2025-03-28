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
use violinist\LicenceCheck\Licence;
use violinist\LicenceCheck\LicenceChecker;

require_once "vendor/autoload.php";

function create_output_and_exit($output, $code)
{
    $json = [];
    foreach ($output as $message) {
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
}

// These are legacy variables, and should not be used.
$legacy_to_new_variables = [
    'user_token' => 'REPO_TOKEN',
    'project_url' => 'PROJECT_URL',
    'tokens' => 'TOKENS',
    'project' => 'PROJECT_DATA',
    // These ones are considered aliases.
    'USER_TOKEN' => 'REPO_TOKEN',
    'REPO_URL' => 'PROJECT_URL',
];
foreach ($legacy_to_new_variables as $old => $new) {
    if (!empty($_SERVER[$old])) {
        $_SERVER[$new] = $_SERVER[$old];
    }
}

// These are variables needed for the SaaS version, and only applies to runs
// for public repos with a token without the repo scope. It also only applies to
// github repos. These variables should therefore be considered internal, and
// should not be used, and have no effect, for running the runner in a
// self-hosted environment. For consistency though, we make sure the variables
// are set, since we pass them to the runner class.
foreach (['token_url', 'fork_to'] as $key) {
    if (!empty($_SERVER[$key])) {
        continue;
    }
    $_SERVER[$key] = '';
}
$fork_to = $_SERVER['fork_to'];
$token_url = $_SERVER['token_url'];

// Now make sure we have the couple of actual required variables.
$required_variables = [
    'REPO_TOKEN',
    'PROJECT_URL',
];
$messages = [];
foreach ($required_variables as $required_variable) {
    if (empty($_SERVER[$required_variable])) {
        $messages[] = new Message('Missing required environment variable ' . $required_variable, Message::ERROR);
    }
    if (!empty($messages)) {
        create_output_and_exit($messages, 1);
    }
}

$user_token = $_SERVER['REPO_TOKEN'];
$project = null;
$url = $_SERVER['PROJECT_URL'];
$tokens = [];
if (!empty($_SERVER['TOKENS'])) {
    $tokens = @json_decode($_SERVER['TOKENS'], true);
}
if (!empty($_SERVER['PROJECT_DATA'])) {
    $project = @unserialize(@json_decode($_SERVER['PROJECT_DATA']));
}

$valid_public_keys = [
    // Production key for violinist.io.
    '7f21f9fdf700d388dda33e1463b541c7ccbdcbf9e35a120fbfbf97c0dccc2385',
    // CI key for violinist.io.
    '8e342a2dd1229474e5b3e0a9553e0af239acf42eabf5cf12c8bdb5dc864fbe7e',
];

$pre_run_messages = [];
$allow_assignees = false;

if (!empty($_SERVER['LICENCE_KEY'])) {
    $pre_run_messages[] = new Message('Licence key found in environment. Checking validity.', Message::COMMAND);
    $has_valid_key = false;
    foreach ($valid_public_keys as $valid_public_key) {
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
        create_output_and_exit($pre_run_messages, 1);
    } else {
        // Check if the licence is valid for the repository.
        $licence = $checked->getPayload();
        if (!$licence->isValidForRepository($url)) {
            $pre_run_messages[] = new Message('Licence key is not valid for the repository ' . $url . '. The required URL prefix is ' . $licence->getData()[Licence::PREFIX_DATA_KEY], Message::COMMAND);
            create_output_and_exit($pre_run_messages, 1);
        }
        $pre_run_messages[] = new Message('Licence key expiry: ' . date('c', $checked->getPayload()->getExpiry()), Message::COMMAND);
        $data = $checked->getPayload()->getData();
        $pre_run_messages[] = new Message('Licence key data: ' . json_encode($data), Message::COMMAND);
        if (empty($data['is_saas'])) {
            // This means the licence is not for the SaaS version, which means
            // its for the self hosted version. Which means we should allow the
            // assignees.
            $allow_assignees = true;
        }
    }
} else {
    // Print exactly one message in the same format as we would have, had we run an actual update run.
    $messages = [];
    $messages[] = new Message('No licence key found in environment. Aborting update job');
    create_output_and_exit($messages, 1);
}

$hostname = '';
if (!empty($_SERVER['violinist_hostname'])) {
    $hostname = $_SERVER['violinist_hostname'];
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
    ->addMethodCall('setUrl', [$url])
    ->addMethodCall('setViolinistHostname', [$hostname]);

/* @var \eiriksm\CosyComposer\CosyComposer $cosy */
$cosy = $container->get('cosy');
$cosy->setAuthentication($user_token);
$cosy->setUserToken($user_token);
$cosy->setForkUser($fork_to);
if ($project instanceof \Violinist\ProjectData\ProjectData) {
    $cosy->setProject($project);
}
$cosy->setAssigneesAllowed($allow_assignees);
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
$git = new GitInfo();
if ($hash = $git->getShortHash()) {
    $_SERVER['queue_runner_revision'] = $hash;
} else {
    $file = __DIR__ . '/VERSION';
    if (file_exists($file)) {
        $_SERVER['queue_runner_revision'] = trim(file_get_contents($file));
    }
}

// This is useful for overriding env vars in local development.
try {
    $env = new Dotenv();
    $env->load(__DIR__ . '/.env');
} catch (Throwable $e) {
    // We tried.
}

// Keep track of what status code we should exit with.
$code = 0;
try {
    $cosy->run();
    $output = $cosy->getOutput();
} catch (Exception $e) {
    $output = $cosy->getOutput();
    $output[] = new Message('Caught Exception: ' . $e->getMessage() . ' with the stack trace ' . $e->getTraceAsString(), Message::ERROR);
    $code = 1;
}

// Prepend the pre-run messages we have stored.
$output = array_merge($pre_run_messages, $output);
create_output_and_exit($output, $code);
