<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class BitbucketWithPrivateDependencyTest extends IntegrationBase
{
    public function testRunBitbucketWithPrivateDepdendencies() : void
    {
        $repo = $_SERVER['BITBUCKET_WITH_PRIVATE_DEP'];
        $json = $this->getProcessAndRunWithoutError($_SERVER['BITBUCKET_APP_PASSWORD'], $repo);
        $this->findMessage($json, 'composer install completed successfully');
    }
}