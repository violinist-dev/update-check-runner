<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class Composer22Test extends IntegrationBase
{

    public function testComposer22Used()
    {
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
