<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\ProjectData\ProjectData;
use Violinist\Slug\Slug;

class CloseOnUpdateGithubPublicTest extends CloseOnUpdateBase
{
    public function testPrsClosedGithubPublic(&$retries = 0)
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
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_PUBLIC_REPO'), $extra_params);
        $closed_with_success = self::hasPrClosedAndPrClosedSuccess($json);
        if ($retries < 20 && !$closed_with_success) {
            $retries++;
            return $this->testPrsClosedGithub($retries);
        }
        self::assertTrue($closed_with_success, 'PR was not both attempted and succeeded with being closed');
    }
}
