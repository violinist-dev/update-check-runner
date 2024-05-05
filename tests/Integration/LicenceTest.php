<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class LicenceTest extends IntegrationBase
{

    public function testBadLicence()
    {
        $json = $this->getProcessAndRunWithoutError($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_PRIVATE_REPO'], [
            'LICENCE_KEY' => 'derpy-derp',
        ]);
        print_r($json);
        $this->assertStandardOutput($_SERVER['GITHUB_PRIVATE_REPO'], $json);
    }

}
