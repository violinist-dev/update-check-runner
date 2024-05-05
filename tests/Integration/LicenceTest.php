<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class LicenceTest extends IntegrationBase
{

    public function testBadLicence()
    {
        $json = $this->getProcessAndRunWithoutError($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_PRIVATE_REPO'], [
            'LICENCE_KEY' => 'derpy-derp',
        ]);
        $this->assertStandardOutput($_SERVER['GITHUB_PRIVATE_REPO'], $json);
        $this->findMessage('Licence key is not valid for any of the known public keys.', $json);
    }

    public function testValidCiLicence()
    {
        $json = $this->getProcessAndRunWithoutError($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_PRIVATE_REPO'], [
            'LICENCE_KEY' => $_SERVER['VALID_CI_LICENCE'],
        ]);
        $this->assertStandardOutput($_SERVER['GITHUB_PRIVATE_REPO'], $json);
        print_r($json);
    }

}
