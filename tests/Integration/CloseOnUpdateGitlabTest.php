<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Bitbucket\Client;
use Bitbucket\HttpClient\Message\FileResource;
use Gitlab\Client as GitlabClient;
use Gitlab\ResultPager;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\Slug\Slug;

class CloseOnUpdateGitlabTest extends CloseOnUpdateBase
{
    protected $url;

    public function setUp()
    {
        parent::setUp();
        $this->url = getenv('GITLAB_PRIVATE_REPO');
    }

    protected function handleAfterAuthenticate(GitlabClient $client)
    {
    }

    public function testPrsClosedGitlab(&$retries = 0)
    {
        $url = $this->url;
        $token = $this->getGitlabToken($url);
        // We want to just, I guess, open all the PRs again?
        $client = new GitlabClient();
        $client->authenticate($token, GitlabClient::AUTH_OAUTH_TOKEN);
        $this->handleAfterAuthenticate($client);
        $pager = new ResultPager($client);
        $api = $client->api('mr');
        $method = 'all';
        $url_parsed = parse_url($url);
        $project_id = ltrim($url_parsed['path'], '/');
        $prs = $pager->fetchAll($api, $method, [$project_id]);
        foreach ($prs as $pr) {
            if ($pr['state'] === 'opened') {
                continue;
            }
            try {
                $client->mergeRequests()->update($project_id, $pr['iid'], [
                    'state_event' => 'reopen',
                ]);
            } catch (\Throwable $e) {
                // Meh, what can you do.
            }
        }
        // Now, let's run this stuff. It should certainly contain some closing action.
        $extra_params = [
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ];
        $json = $this->getProcessAndRunWithoutError($token, $url, $extra_params);
        $has_it = self::hasPrClosedAndPrClosedSuccess($json);
        if ($retries < 20 && !$has_it) {
            $retries++;
            return $this->testPrsClosedGitlab($retries);
        }
        self::assertTrue($has_it);
    }
}
