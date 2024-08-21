<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class DefaultBranchTest extends IntegrationBase
{

    public function testDefaultBranch()
    {
        $json = $this->getProcessAndRunWithoutError($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_DEFAULT_BRANCH_IN_CONFIG']);
        $this->assertStandardOutput($_SERVER['GITHUB_DEFAULT_BRANCH_IN_CONFIG'], $json);
        $found_no_updates = false;
        foreach ($json as $value) {
            if ($value->message !== 'No updates found') {
                continue;
            }
            $found_no_updates = true;
        }
        // There should for sure be updates. Just on the default branch of the repo, there are none.
        self::assertEquals(false, $found_no_updates);
        $this->findMessage('Successfully ran command composer update for package psr/log', $json);
    }

}
