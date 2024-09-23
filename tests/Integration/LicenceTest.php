<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class LicenceTest extends IntegrationBase
{

    public function testBadLicence()
    {
        $process = $this->getProcessAndRun($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_PRIVATE_REPO'], [
            'LICENCE_KEY' => 'derpy-derp',
        ]);
        $json = @json_decode($process->getOutput());
        self::assertNotEmpty($json);
        $this->findMessage('Licence key is not valid for any of the known public keys.', $json);
    }

    public function testValidCiLicence()
    {
        $json = $this->getProcessAndRunWithoutError($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_PRIVATE_REPO'], [
            'LICENCE_KEY' => $_SERVER['VALID_CI_LICENCE'],
        ]);
        $this->assertStandardOutput($_SERVER['GITHUB_PRIVATE_REPO'], $json);
        $this->findMessage('Licence key data: {"ci_test":1}', $json);
        $this->findMessage('Licence key is valid for public key 8e342a2dd1229474e5b3e0a9553e0af239acf42eabf5cf12c8bdb5dc864fbe7e', $json);
    }

    public function testValidForWrongKey()
    {
        $process = $this->getProcessAndRun($_SERVER['GITHUB_PRIVATE_USER_TOKEN'], $_SERVER['GITHUB_PRIVATE_REPO'], [
            'LICENCE_KEY' => 'fYtLakIxFEBdy1vB_SU3iaPrTRwVugFnj9AGxRYVsRSha-ju3m7qpFNHhwPn_C5vS38tDGW6jo_DOI7zZfcy5n6cu7_3ef8vU8HyfS6cyrR6Xq767XOcvqb1KKgoCKqo6_vyI02pWk6YgyU3gsrqgaS5pwcVo9aNY2AQbS1TZABJjwWRHCUqNrCK7pTd2TE6hA01rMQKTJUNmjlLjbYlYc4c3TQxS6iqYH8',
        ]);
        $json = @json_decode($process->getOutput());
        self::assertNotEmpty($json);
        $this->findMessage('Licence key is not valid for any of the known public keys.', $json);
    }
}
