<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class MultiLineCommitTest extends IntegrationBase
{

    public function testBadLicence()
    {
        $json = $this->getProcessAndRunWithoutError($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_PRIVATE_REPO'], [
            'USE_NEW_COMMIT_MSG' => '1',
            'fork_user' => $_SERVER['FORK_USER'],
            'fork_mail' => $_SERVER['FORK_MAIL'],
        ]);
        $this->assertStandardOutput($_SERVER['GITHUB_PRIVATE_REPO'], $json);
        print_r($json);
    }
}
