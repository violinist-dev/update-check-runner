<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use Violinist\Slug\Slug;

abstract class IntegrationBase extends TestCase
{

    public function setUp() : void
    {
        try {
            $env = new Dotenv();
            $env->load(__DIR__ . '/../../.env');
        } catch (\Throwable $e) {
            // We tried.
        }
    }


    protected function assertStandardOutput($url, $json)
    {
        $this->assertHashLogged($json);
        $this->assertPhpVersionLogged($json);
        $this->assertProjectStarting($url, $json);
        $this->assertRepoCloned($json);
        $this->assertComposerInstalled($json);
        $this->assertComposerVersion($json);
    }

    protected function assertComposerVersion($json)
    {
        $expected_message = sprintf('Composer %d', getenv('COMPOSER_VERSION'));
        foreach ($json as $item) {
            if (strpos($item->message, $expected_message) === 0) {
                return;
            }
        }
        $this->assertTrue(false, 'The message ' . $expected_message . ' was not found in the output.');
    }

    protected function assertPhpVersionLogged($json)
    {
        foreach ($json as $item) {
            if (preg_match('/^PHP \d.\d.\d/', $item->message)) {
                $this->assertEquals(false, strpos(str_replace('.', '', $item->message), getenv('PHP_VERSION')) === false);
                return;
            }
        }
        $this->assertTrue(false, 'The php version was not found in the output');
    }

    protected function assertHashLogged($json)
    {
        $expected_message = sprintf('Queue runner revision %s', substr(getenv('MY_COMMIT'), 0, 7));
        foreach ($json as $item) {
            if ($item->message === $expected_message) {
                return;
            }
        }
        // So it was not found. Let's look at what we actually have.
        foreach ($json as $item) {
            if (strpos($item->message, 'Queue runner revision') === 0) {
                print_r("POSSIBLE HASH MSG: " . $item->message . "\n");
            }
        }
        $this->assertTrue(false, 'The message ' . $expected_message . ' was not found in the output.');
    }

    protected function findMessage($message, $json)
    {
        foreach ($json as $item) {
            if ($item->message === $message) {
                return $item;
            }
        }
        $this->assertTrue(false, 'The message ' . $message . ' was not found in the output.');
    }

    protected function assertProjectStarting($url, $json)
    {
        $this->findMessage(sprintf('Starting update check for %s', Slug::createFromUrl($url)->getSlug()), $json);
    }

    protected function assertRepoCloned($json)
    {
        $this->findMessage('Repository cloned', $json);
    }

    protected function assertComposerInstalled($json)
    {
        $this->findMessage('composer install completed successfully', $json);
    }

    protected function getProcessAndRunWithoutError($token, $url, $other_env = [])
    {
        $env_part = sprintf('-e user_token=%s -e project_url=%s', $token, $url);
        foreach ($other_env as $var => $value) {
            $env_part .= sprintf(' -e %s=%s', $var, $value);
        }
        $process = new Process(sprintf(
            'docker run -i --rm %s update-check-runner',
            $env_part
        ), null, null, null, 600);
        $process->run();
        if ($process->getExitCode()) {
            var_export($process->getOutput());
        }
        $this->assertEquals(0, $process->getExitCode(), 'Docker did not exit with exit code 0');
        $json = @json_decode($process->getOutput());
        $this->assertFalse(empty($json));
        return $json;
    }

    protected function getGitlabToken($url)
    {
        $client = new HttpClient();
        $request = new Request('GET', getenv('GITLAB_SUPER_SECRET_URL_FOR_TOKEN') . '&url=' . $url);
        $response = $client->sendRequest($request);
        $json = json_decode($response->getBody());
        if (empty($json->token)) {
            throw new \Exception('No token found for this test to run');
        }
        return $json->token;
    }

}
