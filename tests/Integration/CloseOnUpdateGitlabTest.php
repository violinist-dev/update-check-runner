<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Bitbucket\Client;
use Bitbucket\HttpClient\Message\FileResource;
use Gitlab\Api\MergeRequests;
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

    protected function createBranchName()
    {
        return 'psrlog101' . random_int(400, 999);
    }

    public function testPrsClosedGitlab(&$retries = 0)
    {
        $url = $this->url;
        $token = $this->getGitlabToken($url);
        // We want to just, I guess, open all the PRs again?
        $client = new GitlabClient();
        $client->authenticate($token, GitlabClient::AUTH_OAUTH_TOKEN);
        $this->handleAfterAuthenticate($client);
        $url_parsed = parse_url($url);
        $project_id = ltrim($url_parsed['path'], '/');
        $branch_name = $this->createBranchName();
        $client->repositories()->createCommit($project_id, [
            'branch' => $branch_name,
            'start_branch' => 'master',
            'commit_message' => 'temp',
            'actions' => [
                [
                    'action' => 'create',
                    'file_path' => 'test.txt',
                    'content' => 'temp',
                ]
            ]
        ]);
        /** @var MergeRequests $mr */
        $mr = $client->api('mr');
        $assignee = null;
        $data = $mr->create($project_id, $branch_name, 'master', 'test pr', $assignee, null, 'test pr');
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
