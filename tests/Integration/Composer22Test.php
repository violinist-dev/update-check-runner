<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class Composer22Test extends IntegrationBase
{

    public function testComposer22Used()
    {
        // We don't need to run this on PHP 8.4 or later.
        if (version_compare(PHP_VERSION, '8.4', '>=')) {
            $this->markTestSkipped('This test is not relevant on PHP 8.4 or later.');
            return;
        }
        $json = $this->getProcessAndRunGetJson('dummy', 'https://example.com', [
            'ALTERNATE_COMPOSER_PATH' => '/usr/local/bin/composer22',
        ]);
        foreach ($json as $item) {
            if (strpos($item->message, 'Composer version 2.2') === 0) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->assertTrue(false, 'The composer version message was not found in the output.');
    }

}
