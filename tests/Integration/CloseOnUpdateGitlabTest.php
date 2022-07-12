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
    protected $psrLogVersion = '101';
    /**
     * @var GitlabClient
     */
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->url = getenv('GITLAB_PRIVATE_REPO');
        $this->client = new GitlabClient();
    }

    protected function deleteBranch($branch_name)
    {
        $this->client->repositories()->deleteBranch($this->getProjectId(), $branch_name);
    }

    protected function handleAfterAuthenticate(GitlabClient $client)
    {
    }

    protected function getProjectId()
    {
        $url_parsed = parse_url($this->url);
        $project_id = ltrim($url_parsed['path'], '/');
        return $project_id;
    }

    public function testPrsClosedGitlab(&$retries = 0)
    {
        $url = $this->url;
        $token = $this->getGitlabToken($url);
        // We want to just, I guess, open all the PRs again?
        $client = $this->client;
        $client->authenticate($token, GitlabClient::AUTH_OAUTH_TOKEN);
        $this->handleAfterAuthenticate($client);
        $project_id = $this->getProjectId();
        $branch_name = $this->branchName;
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
