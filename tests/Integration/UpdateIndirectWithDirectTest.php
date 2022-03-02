<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class UpdateIndirectWithDirectTest extends IntegrationBase
{

    public function testUpdateIndirect()
    {
        if (version_compare(phpversion(), "7.2.0", "<=")) {
            $this->assertTrue(true, 'Skipping direct-indirect test for version ' . phpversion());
            return;
        }
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_PRIVATE_INDIRECT_WITH_DIRECT'), [
            'fork_to' => getenv('GITHUB_FORK_TO'),
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ]);
        $found_message_indicating_mbstring_found = false;
        $found_message_indicating_branch_name = false;
        foreach ($json as $item) {
            if (strpos($item->message, 'symfony/polyfill-mbstring: v1.23.0 installed') !== false) {
                $found_message_indicating_mbstring_found = true;
            }
            if ($item->message === 'Checking out new branch: symfonyvardumperv545dependencies') {
                $found_message_indicating_branch_name = true;
            }
        }
        self::assertTrue($found_message_indicating_branch_name && $found_message_indicating_mbstring_found);
    }

}
