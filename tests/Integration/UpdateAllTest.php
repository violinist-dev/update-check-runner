<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

class UpdateAllTest extends IntegrationBase
{

    public function testUpdateAll()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('GITHUB_PRIVATE_USER_TOKEN'), getenv('GITHUB_PRIVATE_UPDATE_ALL_REPO'), [
            'fork_to' => getenv('GITHUB_FORK_TO'),
            'fork_user' => getenv('FORK_USER'),
            'fork_mail' => getenv('FORK_MAIL'),
        ]);
        $found_update_all_type = false;
        foreach ($json as $value) {
            if ($value->message !== 'Config suggested update type update_all') {
                continue;
            }
            $found_update_all_type = true;
        }
        // There should for sure be updates. Just on the default branch of the repo, there are none.
        self::assertTrue($found_update_all_type);
    }

}
