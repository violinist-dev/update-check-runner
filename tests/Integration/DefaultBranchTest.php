<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

/**
 * Test case to test that using a default branch behaves like it should.
 *
 * The main purpose of this test is to make sure we are running the outdated and
 * install commands on the correct branch. For example, if it's overridden to be
 * set to "develop", and the repo is set to have "main" as its main branch. We
 * want to make sure we check out the develop branch before we run the commands.
 * Otherwise the outdated command will be... well, potentially outdated but at
 * least potentially wrong.
 */
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
        // And see that we found an update for psr/log. This is an indication
        // that we ran the outdated command on the config branch, and not on the
        // main branch.
        $found_psr_log = false;
        foreach ($json as $value) {
            // Check that it contains the psr/log package.
            if (strpos($value->message, 'psr/log: 1.0.0 installed, ') === false) {
                continue;
            }
            $found_psr_log = true;
        }
        self::assertEquals(true, $found_psr_log);
        // There should for sure be updates. Just on the default branch of the
        // repo, there are none.
        self::assertEquals(false, $found_no_updates);
    }
}
