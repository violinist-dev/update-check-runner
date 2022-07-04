<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\Slug\Slug;

class CloseOnUpdateBitbucketTest extends CloseOnUpdateBase
{

    public function testPrsClosedBitbucket(&$retries = 0)
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
        $branch_name = 'psrlog100' . random_int(400, 999);
        $client = new \GuzzleHttp\Client();
        $access_token = $new_token->getToken();
        $headers = [
            'Authorization' => "Bearer $access_token",
        ];
        try {
            $client->request('DELETE', sprintf('https://api.bitbucket.org/2.0/repositories/%s/%s/refs/branches/%s', $slug->getUserName(), $slug->getUserRepo(), $branch_name), [
                'headers' => $headers,
            ]);
        } catch (\Throwable $e) {
            // Probably nothing to remove?
        }
        $client->request('POST', sprintf('https://api.bitbucket.org/2.0/repositories/%s/%s/refs/branches', $slug->getUserName(), $slug->getUserRepo()), [
            'json' => [
                'name' => $branch_name,
                'target' => [
                    'hash' => 'master',
                ],
            ],
            'headers' => $headers + [
                'Accept' => 'application/json',
            ],
        ]);
        $data = $client->request('POST', sprintf('https://api.bitbucket.org/2.0/repositories/%s/%s/src?branch=%s', $slug->getUserName(), $slug->getUserRepo(), $branch_name), [
            'form_params' => [
                'branch' => $branch_name,
                'test.txt' => 'almost empty file',
            ],
            'headers' => $headers,
        ]);
        $client->request('POST', sprintf('https://api.bitbucket.org/2.0/repositories/%s/%s/pullrequests', $slug->getUserName(), $slug->getUserRepo()), [
            'json' => [
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
            ],
            'headers' => $headers,
        ]);
        $json = $this->getProcessAndRunWithoutError($new_token->getToken(), $url);
        $closed_with_success = self::hasPrClosedAndPrClosedSuccess($json);
        try {
            $client->repositories()->users($slug->getUserName())->refs($slug->getUserRepo())->branches()->remove($branch_name);
        } catch (\Throwable $e) {
            // Probably nothing to remove?
        }
        if ($retries < 20 && !$closed_with_success) {
            $retries++;
            return $this->testPrsClosedBitbucket($retries);
        }
        self::assertTrue($closed_with_success, 'PR was not both attempted and succeeded with being closed');
    }
}
