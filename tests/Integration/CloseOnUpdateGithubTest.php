<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Github\AuthMethod;
use Github\Client;
use Violinist\Slug\Slug;

class CloseOnUpdateGithubTest extends CloseOnUpdateBase
{
    protected $token;
    protected $url;
    /**
     * @var Client
     */
    protected $client;

    protected function getBranchSlug() : Slug
    {
        return Slug::createFromUrl($this->url);
    }

    protected function getPullRequestHead() : string
    {
        return $this->branchName;
    }

    protected function deleteBranch($branch_name)
    {
        $slug = $this->getBranchSlug();
        $token = $this->token;
        $this->client->authenticate($token, null, AuthMethod::ACCESS_TOKEN);
        /** @var \Github\Api\GitData $git */
        $git = $this->client->api('git');
        $git->references()->remove($slug->getUserName(), $slug->getUserRepo(), sprintf('heads/%s', $branch_name));
    }

    protected function assertBranchDeleted($branch_name) : void
    {
        $slug = $this->getBranchSlug();
        /** @var \Github\Api\GitData $git */
        $git = $this->client->api('git');
        try {
            $git->references()->show($slug->getUserName(), $slug->getUserRepo(), sprintf('heads/%s', $branch_name));
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === 404) {
                return;
            }
            throw $e;
        }
        self::fail(sprintf('Expected branch %s to have been deleted', $branch_name));
    }

    public function setUp() : void
    {
        parent::setUp();
        $this->token = $_SERVER['GITHUB_PRIVATE_USER_TOKEN'];
        $this->url = $_SERVER['GITHUB_PRIVATE_REPO'];
        $this->client = new Client();
    }

    public function testPrsClosedGithub(&$retries = 0)
    {
        sleep(random_int(15, 45));
        $slug = Slug::createFromUrl($this->url);
        $branch_slug = $this->getBranchSlug();
        try {
            $this->deleteBranch($this->branchName);
        } catch (\Throwable $e) {
        }
        try {
            $e = null;
            $token = $this->token;
            $this->client->authenticate($token, null, AuthMethod::ACCESS_TOKEN);
            $client = $this->client;
            /** @var \Github\Api\Repo $repo */
            $repo = $client->api('repo');
            $info = $repo->show($slug->getUserName(), $slug->getUserRepo());
            $default_branch = $info['default_branch'];
            $branch = $repo->branches($branch_slug->getUserName(), $branch_slug->getUserRepo(), $default_branch);
            $sha = $branch["commit"]["sha"];
            /** @var \Github\Api\GitData $api */
            $api = $client->api('git');
            $tree = [];
            $data = $api->blobs()->create($branch_slug->getUserName(), $branch_slug->getUserRepo(), [
                'content' => 'temp file',
                'encoding' => 'utf-8',
            ]);
            $tree[] = [
                'sha' => $data["sha"],
                'mode' => '100644',
                'type' => 'blob',
                'path' => 'test.txt',
            ];
            $data = $api->trees()->create($branch_slug->getUserName(), $branch_slug->getUserRepo(), [
                'tree' => $tree,
                'base_tree' => $sha,
                'parents' => [
                    $sha,
                ],
            ]);
            $data = $api->commits()->create($branch_slug->getUserName(), $branch_slug->getUserRepo(), [
                'message' => self::getValidTempCommitMessage(),
                'tree' => $data["sha"],
                'parents' => [
                    $sha,
                ],
            ]);
            $branch_name = $this->branchName;
            $data = $api->references()->create($branch_slug->getUserName(), $branch_slug->getUserRepo(), [
                'ref' => 'refs/heads/' . $branch_name,
                'sha' => $data['sha'],
                'force' => true,
            ]);
            $user_name = $slug->getUserName();
            $user_repo = $slug->getUserRepo();
            /** @var \Github\Api\PullRequest $prs */
            $prs = $client->api('pull_request');
            $data = $prs->create($user_name, $user_repo, [
                'base'  => $default_branch,
                'head'  => $this->getPullRequestHead(),
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
            if ($e && strpos($e->getMessage(), 'You have exceeded a secondary rate limit') === 0) {
                sleep(random_int(90, 120));
            }
            return $this->testPrsClosedGithub($retries);
        }
        if ($e) {
            var_dump([$e->getMessage(), $e->getTraceAsString()]);
        }
        self::assertTrue($closed_with_success, 'PR was not both attempted and succeeded with being closed');
        self::assertTrue(
            self::hasBranchDeletedSuccess($json, $this->branchName),
            'The runner did not report that the superseded branch was deleted'
        );
        $this->assertBranchDeleted($this->branchName);
    }

    protected function getExtraParams()
    {
        return [
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ];
    }
}
