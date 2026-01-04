<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Gitlab\Client as GitlabClient;

class CloseOnUpdateGitlabTest extends CloseOnUpdateBase
{
    protected $url;
    protected $psrLogVersion = '101';
    /**
     * @var GitlabClient
     */
    protected $client;

    public function setUp() : void
    {
        parent::setUp();
        $this->url = $_SERVER['GITLAB_PRIVATE_REPO'];
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
        sleep(random_int(15, 45));
        $token = null;
        try {
            $this->deleteBranch($this->branchName);
        } catch (\Throwable $e) {
        }
        $e = null;
        try {
            $url = $this->url;
            $token = $this->getGitlabToken($url);
            $client = $this->client;
            $client->authenticate($token, GitlabClient::AUTH_OAUTH_TOKEN);
            $this->handleAfterAuthenticate($client);
            $project_id = $this->getProjectId();
            $branch_name = $this->branchName;
            $client->repositories()->createCommit($project_id, [
                'branch' => $branch_name,
                'start_branch' => 'master',
                'author_email' => 'test@test.com',
                'author_name'  => 'violinist-bot',
                'commit_message' => self::getValidTempCommitMessage(),
                'actions' => [
                    [
                        'action' => 'create',
                        'file_path' => 'test.txt',
                        'content' => 'temp',
                    ],
                ],
            ]);
            /** @var \Gitlab\Api\MergeRequests $mr */
            $mr = $client->mergeRequests();
            $assignee = null;
            $data = $mr->create($project_id, $branch_name, 'master', 'test pr', [
                'description' => 'test pr',
            ]);
        } catch (\Throwable $e) {
        }
        // Now, let's run this stuff. It should certainly contain some closing action.
        $extra_params = [
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ];
        $has_it = false;
        try {
            $json = $this->getProcessAndRunWithoutError($token, $url, $extra_params);
            $has_it = self::hasPrClosedAndPrClosedSuccess($json);
        } catch (\Throwable $e) {
        }
        if ($retries < 20 && !$has_it) {
            $retries++;
            return $this->testPrsClosedGitlab($retries);
        }
        if ($e) {
            var_dump([$e->getMessage(), $e->getTraceAsString()]);
        }
        self::assertTrue($has_it);
    }
}
