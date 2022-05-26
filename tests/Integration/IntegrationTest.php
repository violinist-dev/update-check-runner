<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use eiriksm\CosyComposer\Providers\Github;
use eiriksm\CosyComposer\Providers\Gitlab;
use Github\Api\PullRequest;
use Github\Client;
use Github\ResultPager;
use Gitlab\Client as GitlabClient;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client as HttpClient;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\ProjectData\ProjectData;
use Violinist\Slug\Slug;

class IntegrationTest extends IntegrationBase
{

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

    public function testConcurrentPrs()
    {
        $token = getenv('GITHUB_PRIVATE_USER_TOKEN');
        $url = getenv('GITHUB_CONCURRENT_REPO');
        $extra = [
            'fork_to' => getenv('GITHUB_FORK_TO'),
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ];
        $json = $this->getProcessAndRunWithoutError($token, $url, $extra);
        $this->assertStandardOutput($url, $json);
        $this->findMessage('Skipping twig/twig because the number of max concurrent PRs (1) seems to have been reached', $json);
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
    public function testBundledOutput(&$count = 0)
    {
        try {
            if (version_compare(phpversion(), "7.99.0", ">=")) {
                $this->assertTrue(true, 'Skipping bundled test for version ' . phpversion());
                return;
            }
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
            return;
        } catch (\Throwable $e) {
            $count++;
            if ($count > 20) {
                throw new \Exception('More than 20 retries to find bundled output');
            }
            $this->testBundledOutput($count);
        }
    }

    public function testGitlabOutput()
    {
        $url = getenv('GITLAB_PRIVATE_REPO');
        $json = $this->getProcessAndRunWithoutError($this->getGitlabToken($url), getenv('GITLAB_PRIVATE_REPO'));
        $this->assertStandardOutput($url, $json);
    }

    public function testGitlabNestedGroupOutput()
    {
        $url = getenv('GITLAB_PRIVATE_REPO_NESTED_GROUP');
        $json = $this->getProcessAndRunWithoutError($this->getGitlabToken($url), getenv('GITLAB_PRIVATE_REPO_NESTED_GROUP'));
        $this->assertStandardOutput($url, $json);
    }

    public function testGitlabSelfhostedOutput()
    {
        $this->runSelfHostedTest(getenv('SELF_HOSTED_GITLAB_PRIVATE_USER_TOKEN'));
    }

    public function testGitlabSelfhostedOutputPat()
    {
        $this->runSelfHostedTest(getenv('SELF_HOSTED_GITLAB_PERSONAL_ACCESS_TOKEN'));
    }

    protected function runSelfHostedTest($token, $retry = 0)
    {
        try {
            $json = $this->getProcessAndRunWithoutError($token, getenv('SELF_HOSTED_GITLAB_PRIVATE_REPO'));
            $this->assertStandardOutput(getenv('SELF_HOSTED_GITLAB_PRIVATE_REPO'), $json);
        } catch (\Throwable $e) {
            $retry++;
            if ($retry > 20) {
                throw new \Exception('More than 20 retries for testing self hosted. Aborting');
            }
            return $this->runSelfHostedTest($token, $retry);
        }
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
        if (version_compare(phpversion(), "7.99.0", ">=")) {
            $this->assertTrue(true, 'Skipping Drupal 8 contrib test for version ' . phpversion());
            return;
        }
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

    public function testSecurityOnly()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_SECURITY_ONLY_REPO'));
        $this->assertStandardOutput(getenv('GITHUB_SECURITY_ONLY_REPO'), $json);
        $found_update = false;
        $this->findMessage('Running composer update for package twig/twig', $json);
        foreach ($json as $item) {
            if ($item->message === 'Running composer update for package psr/log') {
                $found_update = true;
            }
        }
        $this->assertFalse($found_update, 'psr/log was updated when it should not');
    }

    public function testUpdateAssigneesGitlab(&$count = 0)
    {
        try {
            // This is the ID of the violinist bot user on gitlab. Since this is pretty public knowledge, let's
            // leave it actually here in the tests.
            $violinist_bot_id = 2775347;
            $project = new ProjectData();
            $project->setRoles(['agency']);
            $extra_params = [
                'project' => sprintf("'%s'", json_encode(serialize($project))),
                'fork_to' => getenv('GITHUB_FORK_TO'),
                'token_url' => getenv('TOKEN_URL'),
                'fork_user' => getenv('FORK_USER'),
                'fork_mail' => getenv('FORK_MAIL'),
            ];
            // Close all PRs. Since this will run in parallel with many php versions, we might get the PR from
            // somewhere else. In fact, someone might close it after we open in here. So we need to check the API
            // for this specific one.
            $token = $this->getGitlabToken();
            $url = getenv('GITLAB_ASSIGNEE_REPO');
            $client = new GitlabClient();
            $client->authenticate($token, GitlabClient::AUTH_OAUTH_TOKEN);
            $id = Gitlab::getProjectId($url);
            $params = ['state' => 'opened'];
            $mrs = $client->mergeRequests()->all($id, $params);
            foreach ($mrs as $mr) {
                // Garble the title, so our runner picks it up.
                $new_mr_data = [
                    'assignee_ids' => 0,
                    'title' => md5($mr['title']),
                ];
                $data = $client->mergeRequests()->update($id, $mr['iid'], $new_mr_data);
            }
            $json = $this->getProcessAndRunWithoutError($token, $url, $extra_params);
            $this->assertStandardOutput($url, $json);
            $mrs = $client->mergeRequests()->all($id, $params);
            $has_assignee = false;
            $has_updated = false;
            foreach ($mrs as $mr) {
                if (empty($mr['assignees'])) {
                    continue;
                }
                foreach ($mr['assignees'] as $assignee) {
                    if ($assignee['id'] == $violinist_bot_id) {
                        $has_assignee = true;
                    }
                }
            }
            foreach ($json as $message) {
                if (empty($message->message)) {
                    continue;
                }
                if ($message->message === 'Will try to update the PR based on settings.') {
                    $has_updated = true;
                }
            }
            if ($has_assignee && $has_updated) {
                return $this->assertTrue(true, 'Found the assignee');
            }
        } catch (\Throwable $e) {}
        $count++;
        if ($count > 40) {
            throw new \Exception('More than 20 retries for testing assignee on update. Aborting');
        }
        sleep(rand(1, 10));
        return $this->testUpdateAssigneesGitlab($count);
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

    public function testWhyNot()
    {
        $repo = getenv('GITLAB_REPO_GITHUB_DEP');
        $json = $this->getProcessAndRunWithoutError($this->getGitlabToken(), $repo, [
            'tokens' => sprintf("'%s'", json_encode([
                'github.com' => getenv('GITHUB_PRIVATE_USER_TOKEN'),
            ])),
        ]);
        $has_reason = FALSE;
        foreach ($json as $message) {
            if (empty($message->context)) {
                continue;
            }
            if (empty($message->context->command)) {
                continue;
            }
            if (strpos($message->context->command, 'composer why-not psr/log') === false) {
                continue;
            }
            // The reason should be something along these lines:
            // vendor/other-private-dep  9999999-dev  requires  psr/log (1.0.0)
            $matches = [];
            if (!preg_match('/\S+\s.*requires.*psr\/log.*1\.0\.0/', $message->message, $matches)) {
                continue;
            }
            $has_reason = TRUE;
        }
        self::assertTrue($has_reason);
    }

    /**
     * A test to make sure we are not merging something we are still not ready to take on.
     *
     * This test just makes sure the feature we want in the future might trigger an update all. So this will fail
     * once that is the case. So when we actually implement it, we also have to update this test.
     */
    public function testUpdateAllFromProject()
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
        $this->assertFalse($found_message, 'Could find the unexpected update separate message in a test run');
    }

}
