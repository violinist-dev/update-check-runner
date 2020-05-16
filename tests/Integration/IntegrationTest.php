<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use Violinist\ProjectData\ProjectData;
use Violinist\Slug\Slug;

class IntegrationTest extends TestCase
{

    public function setUp()
    {
        try {
            $env = new Dotenv();
            $env->load(__DIR__ . '/../../.env');
        } catch (\Throwable $e) {
            // We tried.
        }
    }

    /**
     * This is so not the way phpunit is supposed to be used.
     */
    public function testGithubOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_PRIVATE_REPO'));
        $this->assertStandardOutput(getenv('GITHUB_PRIVATE_REPO'), $json);
        // Test for error messages of the type "PHP Warning".
        foreach ($json as $item) {
            if (strpos($item->message, 'PHP Warning') === 0) {
                $this->assertTrue(false, 'The update run contained PHP warnings');
            }
        }
    }

    public function testGitlabOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITLAB_PRIVATE_USER_TOKEN'), getenv('GITLAB_PRIVATE_REPO'));
        $this->assertStandardOutput(getenv('GITLAB_PRIVATE_REPO'), $json);
    }

    public function testGitlabSelfhostedOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('SELF_HOSTED_GITLAB_PRIVATE_USER_TOKEN'), getenv('SELF_HOSTED_GITLAB_PRIVATE_REPO'));
        $this->assertStandardOutput(getenv('SELF_HOSTED_GITLAB_PRIVATE_REPO'), $json);
    }

    public function testBitbucketOutput()
    {
        if (version_compare(phpversion(), "7.1.0", "<=")) {
            $this->assertTrue(true, 'Skipping bitbucket test for version ' . phpversion());
            return;
        }
        $provider = new Bitbucket([
            'clientId' => getenv('BITBUCKET_CLIENT_ID'),
            'clientSecret' => getenv('BITBUCKET_CLIENT_SECRET'),
            'redirectUri' => getenv('BITBUCKET_REDIRECT_URI'),
        ]);
        $new_token = $provider->getAccessToken('refresh_token', [
            'refresh_token' => getenv('BITBUCKET_REFRESH_TOKEN'),
        ]);
        $json = $this->getProcessAndRunWithoutError($new_token->getToken(), getenv('BITBUCKET_PRIVATE_REPO'));
        $this->assertStandardOutput(getenv('BITBUCKET_PRIVATE_REPO'), $json);
    }

    public function testDrupalContribDrupal8()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_DRUPAL8_CONTRIB_PRIVATE_REPO'));
        $this->assertStandardOutput(getenv('GITHUB_DRUPAL8_CONTRIB_PRIVATE_REPO'), $json);
        $found_sa = false;
        foreach ($json as $item) {
            if (strpos($item->message, 'security advisories for packages installed')) {
                if (!empty($item->context->result->{'drupal/metatag'})) {
                    $found_sa = true;
                }
            }
        }
        $this->assertTrue($found_sa, 'Could not find the expected SA for drupal/metatag package (drupal 8) in output');
    }

    protected function assertStandardOutput($url, $json)
    {
        $this->assertHashLogged($json);
        $this->assertProjectStarting($url, $json);
        $this->assertRepoCloned($json);
        $this->assertComposerInstalled($json);
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
}
