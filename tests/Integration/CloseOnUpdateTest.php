<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Bitbucket\Client;
use Bitbucket\HttpClient\Message\FileResource;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\Slug\Slug;

class CloseOnUpdateTest extends IntegrationBase
{
    public function testPrsClosed(&$retries = 0)
    {
        if (version_compare(phpversion(), "7.1.0", "<=")) {
            $this->assertTrue(true, 'Skipping bitbucket test for version ' . phpversion());
            return;
        }
        $provider = new Bitbucket([
            'clientId' => getenv('BITBUCKET_CLIENT_ID'),
            'clientSecret' => getenv('BITBUCKET_CLIENT_SECRET'),
            'redirectUri' => getenv('BITBUCKET_REDIRECT_URI'),
        ]);
        $new_token = $provider->getAccessToken('refresh_token', [
            'refresh_token' => getenv('BITBUCKET_REFRESH_TOKEN'),
        ]);
        $url = getenv('BITBUCKET_PRIVATE_REPO');
        $slug = Slug::createFromUrl($url);
        $client = new Client();
        $client->authenticate(Client::AUTH_OAUTH_TOKEN, $new_token->getToken());

        $branch_name = 'psrlog100' . random_int(400, 999);
        try {
            $client->repositories()->users($slug->getUserName())->refs($slug->getUserRepo())->branches()->remove($branch_name);
        } catch (\Throwable $e) {
            // Probably nothing to remove?
        }
        $client->repositories()->users($slug->getUserName())->refs($slug->getUserRepo())->branches()->create([
            'name' => $branch_name,
            'target' => [
                'hash' => 'master',
            ],
        ]);
        $files = [new FileResource('test.txt', 'almost empty file')];
        $client->repositories()->users($slug->getUserName())->src($slug->getUserRepo())->createWithFiles($files, [
            'branch' => $branch_name,
        ]);
        $client->repositories()->users($slug->getUserName())->pullRequests($slug->getUserRepo())->create([
            'title' => 'temp pr',
            'source' => [
                'branch' => [
                    'name' => $branch_name,
                ]
            ],
            'destination' => [
                'branch' => [
                    'name' => 'master',
                ],
            ],
            'description' => 'test will be closed',
        ]);
        $json = $this->getProcessAndRunWithoutError($new_token->getToken(), $url);
        $pr_closed_found = false;
        $pr_closed_success_found = false;
        foreach ($json as $item) {
            if (strpos($item->message, 'Trying to close PR number') !== false) {
                $pr_closed_found = true;
            }
            if (strpos($item->message, 'Successfully closed PR') !== false) {
                $pr_closed_success_found = true;
            }
        }
        try {
            $client->repositories()->users($slug->getUserName())->refs($slug->getUserRepo())->branches()->remove($branch_name);
        } catch (\Throwable $e) {
            // Probably nothing to remove?
        }
        if ($retries < 20 && (!$pr_closed_success_found || !$pr_closed_found)) {
            $retries++;
            return $this->testPrsClosed($retries);
        }
        self::assertTrue($pr_closed_found && $pr_closed_success_found, 'PR was not both attempted and succeeded with being closed');
    }
}
