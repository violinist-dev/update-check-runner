<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\Slug\Slug;

class CloseOnUpdateGithubTest extends CloseOnUpdateBase
{
    public function testPrsClosedGithub(&$retries = 0)
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_PRIVATE_REPO'));
        $closed_with_success = self::hasPrClosedAndPrClosedSuccess($json);
        if ($retries < 20 && !$closed_with_success) {
            $retries++;
            return $this->testPrsClosedGithub($retries);
        }
        self::assertTrue($closed_with_success, 'PR was not both attempted and succeeded with being closed');
    }
}
