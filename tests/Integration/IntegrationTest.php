<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use eiriksm\CosyComposer\Providers\Github;
use Github\Api\PullRequest;
use Github\Client;
use Github\ResultPager;
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

    public function testGithubPublicOutput()
    {
        $project = new ProjectData();
        $project->setNid(getenv('GITHUB_PUBLIC_PROJECT_NID'));
        // First make sure we have created PRs for all of them.
        $extra_params = [
            'project' => sprintf("'%s'", json_encode(serialize($project))),
            'fork_to' => getenv('GITHUB_FORK_TO'),
            'token_url' => getenv('TOKEN_URL'),
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ];
        $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_PUBLIC_REPO'), $extra_params);
        // Then make sure we are not pushing over and over again.
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_PUBLIC_REPO'), $extra_params);
        $this->findMessage('Skipping symfony/polyfill-mbstring because a pull request already exists', $json);
    }

    /**
     * Test that when we indicate bundled packages, we get that as updates.
     */
    public function testBundledOutput()
    {
        // Close all of the pull requests, so we can actually see that we update bundled.
        $client = new Client();
        $token = getenv('GITHUB_PRIVATE_USER_TOKEN');
        $client->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
        $pager = new ResultPager($client);
        /** @var PullRequest $api */
        $api = $client->api('pr');
        $method = 'all';
        $url = getenv('GITHUB_BUNDLED_REPO');
        $slug = Slug::createFromUrl($url);
        $prs = $pager->fetchAll($api, $method, [$slug->getUserName(), $slug->getUserRepo()]);
        foreach ($prs as $pr) {
            $api->update($slug->getUserName(), $slug->getUserRepo(), $pr['number'], [
                'state' => 'closed',
            ]);
        }
        $json = $this->getProcessAndRunWithoutError($token, $url);
        $this->assertStandardOutput(getenv('GITHUB_BUNDLED_REPO'), $json);
        // Check that the bundle thing ran.
        $found_bundle_command = false;
        foreach ($json as $item) {
            if (strpos($item->message, 'Creating command composer update -n --no-ansi drupal/core-recommended drupal/core-composer-scaffold --with-dependencies') === 0) {
                $found_bundle_command = true;
            }
        }
        $this->assertTrue($found_bundle_command, 'The bundled command was not found');
    }

    public function testGitlabOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITLAB_PRIVATE_USER_TOKEN'), getenv('GITLAB_PRIVATE_REPO'));
        $this->assertStandardOutput(getenv('GITLAB_PRIVATE_REPO'), $json);
    }

    public function testGitlabNestedGroupOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITLAB_PRIVATE_USER_TOKEN'), getenv('GITLAB_PRIVATE_REPO_NESTED_GROUP'));
        $this->assertStandardOutput(getenv('GITLAB_PRIVATE_REPO_NESTED_GROUP'), $json);
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

    /**
     * A test to make sure we are not merging something we are still not ready to take on.
     *
     * This test just makes sure the feature we want in the future might trigger an update all. So this will fail
     * once that is the case. So when we actually implement it, we also have to update this test.
     */
    public function testUpdateAllNotReady()
    {
        // First just make sure that all PRs all closed.
        $client = new Client();
        $provider = new Github($client);
        $token = getenv('GITHUB_PRIVATE_USER_TOKEN');
        $provider->authenticate($token, '');
        $url = getenv('GITHUB_PRIVATE_REPO');
        $slug = Slug::createFromUrl($url);
        $prs = $provider->getPrsNamed($slug);
        foreach ($prs as $pr) {
            $client->pullRequests()->update($slug->getUserName(), $slug->getUserRepo(), $pr['number'], [
                'state' => 'closed',
            ]);
        }
        $project = new ProjectData();
        $project->setUpdateAll(true);
        $json = $this->getProcessAndRunWithoutError($token, $url, [
            'project' => sprintf("'%s'", json_encode(serialize($project))),
        ]);
        // So here is a message I would only find if the "update all" sequence would not run:
        $message = 'Successfully ran command composer update for package psr/log';
        $found_message = false;
        foreach ($json as $item) {
            if (!empty($item->message) && $item->message === $message) {
                $found_message = true;
            }
        }
        $this->assertTrue($found_message, 'Could not find the expected update separate message in a test run');
    }

    protected function assertStandardOutput($url, $json)
    {
        $this->assertHashLogged($json);
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
