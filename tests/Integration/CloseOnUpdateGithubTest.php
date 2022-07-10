<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Github\Api\GitData;
use Github\Api\PullRequest;
use Github\Api\Repo;
use Github\Client;
use Violinist\Slug\Slug;

class CloseOnUpdateGithubTest extends CloseOnUpdateBase
{
    protected $token;
    protected $url;

    public function setUp()
    {
        parent::setUp();
        $this->token = getenv('GITHUB_PRIVATE_USER_TOKEN');
        $this->url = getenv('GITHUB_PRIVATE_REPO');
    }

    public function testPrsClosedGithub(&$retries = 0)
    {
        sleep(random_int(15, 45));
        try {
            $client = new Client();
            $token = $this->token;
            $client->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
            $slug = Slug::createFromUrl($this->url);
            /** @var Repo $repo */
            $repo = $client->api('repo');
            $info = $repo->show($slug->getUserName(), $slug->getUserRepo());
            $default_branch = $info['default_branch'];
            $branch = $repo->branches($slug->getUserName(), $slug->getUserRepo(), $default_branch);
            $sha = $branch["commit"]["sha"];
            /** @var GitData $api */
            $api = $client->api('git');
            $tree = [];
            $data = $api->blobs()->create($slug->getUserName(), $slug->getUserRepo(), [
                'content' => 'temp file',
                'encoding' => 'utf-8',
            ]);
            $tree[] = [
                'sha' => $data["sha"],
                'mode' => '100644',
                'type' => 'blob',
                'path' => 'test.txt',
            ];
            $data = $api->trees()->create($slug->getUserName(), $slug->getUserRepo(), [
                'tree' => $tree,
                'base_tree' => $sha,
                'parents' => [
                    $sha,
                ],
            ]);
            $data = $api->commits()->create($slug->getUserName(), $slug->getUserRepo(), [
                'message' => 'temp commit',
                'tree' => $data["sha"],
                'parents' => [
                    $sha,
                ],
            ]);
            $branch_name = $this->createBranchName();
            $data = $api->references()->create($slug->getUserName(), $slug->getUserRepo(), [
                'ref' => 'refs/heads/' . $branch_name,
                'sha' => $data['sha'],
                'force' => true,
            ]);
            $user_name = $slug->getUserName();
            $user_repo = $slug->getUserRepo();
            /** @var PullRequest $prs */
            $prs = $client->api('pull_request');
            $data = $prs->create($user_name, $user_repo, [
                'base'  => $default_branch,
                'head'  => $branch_name,
                'title' => 'test temp pr',
                'body'  => 'test temp pr',
            ]);
        } catch (\Throwable $e) {
        }
        $extra_params = $this->getExtraParams();
        $json = $this->getProcessAndRunWithoutError($token, $slug->getUrl(), $extra_params);
        $closed_with_success = self::hasPrClosedAndPrClosedSuccess($json);
        if ($retries < 20 && !$closed_with_success) {
            $retries++;
            return $this->testPrsClosedGithub($retries);
        }
        self::assertTrue($closed_with_success, 'PR was not both attempted and succeeded with being closed');
    }

    protected function createBranchName()
    {
        return 'psrlog100' . random_int(400, 999);
    }

    protected function getExtraParams()
    {
        return [
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ];
    }
}
