<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Symfony\Component\Process\Process;
use Violinist\Slug\Slug;

class IntegrationTest extends TestCase
{
    /**
     * This is so not the way phpunit is supposed to be used.
     */
    public function testOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('user_token'), getenv('project_url'));
        $this->assertStandardOutput(getenv('project_url'), $json);
        // Test for error messages of the type "PHP Warning".
        foreach ($json as $item) {
            if (strpos($item->message, 'PHP Warning') === 0) {
                $this->assertTrue(false, 'The update run contained PHP warnings');
            }
        }
    }

    public function testGitlabOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('user_gitlab_token'), getenv('gitlab_project_url'));
        $this->assertStandardOutput(getenv('gitlab_project_url'), $json);
    }

    public function testGitlabSelfhostedOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('user_token_self_hosted'), getenv('project_url_self_hosted'));
        $this->assertStandardOutput(getenv('project_url_self_hosted'), $json);
    }

    public function testBitbucketOutput()
    {
        if (version_compare(phpversion(), "7.1.0", "<=")) {
            $this->assertTrue(true, 'Skipping bitbucket test for version ' . phpversion());
            return;
        }
        $provider = new Bitbucket([
            'clientId' => getenv('bitbucket_client_id'),
            'clientSecret' => getenv('bitbucket_client_secret'),
            'redirectUri' => 'http://violinist.localhost/league_oauth_login/login/bitbucket',
        ]);
        $new_token = $provider->getAccessToken('refresh_token', [
            'refresh_token' => getenv('bitbucket_refresh_token'),
        ]);
        $json = $this->getProcessAndRunWithoutError($new_token->getToken(), getenv('project_url_bitbucket'));
        $this->assertStandardOutput(getenv('project_url_bitbucket'), $json);
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
        $expected_message = sprintf('Queue runner revision %s', substr(getenv('TRAVIS_COMMIT'), 0, 7));
        $this->findMessage($expected_message, $json);
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

    protected function getProcessAndRunWithoutError($token, $url)
    {
        $process = new Process(sprintf(
            'docker run -i --rm -e user_token=%s -e project_url=%s update-check-runner',
            $token,
            $url
        ), null, null, null, 600);
        $process->run();
        $this->assertEquals(0, $process->getExitCode());
        $json = @json_decode($process->getOutput());
        $this->assertFalse(empty($json));
        return $json;
    }
}
