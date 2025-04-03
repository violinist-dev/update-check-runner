<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use eiriksm\CosyComposer\Providers\Github;
use Github\AuthMethod;
use Github\Client;
use Github\ResultPager;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\ProjectData\ProjectData;
use Violinist\Slug\Slug;

class GroupTest extends IntegrationBase
{
    /**
     * Test that when we indicate bundled packages, we get that as updates.
     */
    public function testGroupedOutput(&$count = 0)
    {
        try {
            // Close all of the pull requests, so we can actually see that we update bundled.
            $client = new Client();
            $token = $_SERVER['GITHUB_PRIVATE_USER_TOKEN'];
            $client->authenticate($token, null, AuthMethod::ACCESS_TOKEN);
            $pager = new ResultPager($client);
            /** @var \Github\Api\PullRequest $api */
            $api = $client->api('pr');
            $method = 'all';
            $url = $_SERVER['GITHUB_GROUP_REPO'];
            $slug = Slug::createFromUrl($url);
            $prs = $pager->fetchAll($api, $method, [$slug->getUserName(), $slug->getUserRepo()]);
            foreach ($prs as $pr) {
                $api->update($slug->getUserName(), $slug->getUserRepo(), $pr['number'], [
                    'state' => 'closed',
                ]);
            }

            $json = $this->getProcessAndRunWithoutError($token, $url);
            $this->assertStandardOutput($_SERVER['GITHUB_GROUP_REPO'], $json);
            // Check that the bundle thing ran.
            self::findMessage('Creating pull request from pee-ess-arr', $json);
            self::findMessage('Trying to retrieve changelog for psr/cache', $json);
            self::findMessage('Trying to retrieve changelog for psr/log', $json);
            $found_group_command = false;
            foreach ($json as $item) {
                if (strpos($item->message, 'Creating command composer update -n --no-ansi psr/log psr/cache --with-dependencies') === 0) {
                    $found_group_command = true;
                }
            }
            $this->assertTrue($found_group_command, 'The bundled command was not found');
            // Let's also fetch all the PRs and check its named after a group
            // and not an individual package.
            $prs = $pager->fetchAll($api, $method, [$slug->getUserName(), $slug->getUserRepo()]);
            // The title is this.
            $expected_title = 'Update group `PSR group`';
            foreach ($prs as $pr) {
                if ($pr['title'] === $expected_title) {
                    return;
                }
            }
            throw new \Exception('The PR was not found with the expected title: ' . $expected_title);
        } catch (\Throwable $e) {
            $count++;
            if ($count > 20) {
                throw new \Exception('More than 20 retries to find bundled output. Last exception message: ' . $e->getMessage());
            }
            $this->testGroupedOutput($count);
        }
    }
}
