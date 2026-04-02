<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Violinist\Slug\Slug;

class SshKeyscanTest extends IntegrationBase
{

    public function testSshKeyscanRunsForGithub()
    {
        $url = $_SERVER['GITHUB_PRIVATE_REPO'];
        $json = $this->getProcessAndRunWithoutError($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $url);
        $this->assertStandardOutput($url, $json);
        $hostname = Slug::createFromUrl($url)->getProvider();
        $this->findMessage(sprintf('Creating command ssh-keyscan -t rsa %s >> ~/.ssh/known_hosts', $hostname), $json);
    }
}
