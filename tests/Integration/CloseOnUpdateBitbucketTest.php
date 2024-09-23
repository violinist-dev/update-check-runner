<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use GuzzleHttp\Client;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\Slug\Slug;

class CloseOnUpdateBitbucketTest extends CloseOnUpdateBase
{

    protected $headers;
    protected $client;
    protected $url;

    protected function deleteBranch($branch_name)
    {
            $slug = Slug::createFromUrl($this->url);
            $this->client->request('DELETE', sprintf('https://api.bitbucket.org/2.0/repositories/%s/%s/refs/branches/%s', $slug->getUserName(), $slug->getUserRepo(), $branch_name), [
                'headers' => $this->headers,
            ]);
    }

    public function testPrsClosedBitbucket(&$retries = 0)
    {
        sleep(random_int(15, 45));
        $url = $_SERVER['BITBUCKET_PRIVATE_REPO'];
        $this->url = $url;
        $slug = Slug::createFromUrl($url);
        try {
            $this->deleteBranch($this->branchName);
        } catch (\Throwable $e) {
        }
        $provider = new Bitbucket([
            'clientId' => $_SERVER['BITBUCKET_CLIENT_ID'],
            'clientSecret' => $_SERVER['BITBUCKET_CLIENT_SECRET'],
            'redirectUri' => $_SERVER['BITBUCKET_REDIRECT_URI'],
        ]);
        $new_token = $provider->getAccessToken('refresh_token', [
            'refresh_token' => $_SERVER['BITBUCKET_REFRESH_TOKEN'],
        ]);
        $this->branchName = $this->createBranchName();
        try {
            $this->deleteBranch($this->branchName);
        } catch (\Throwable $e) {
        }
        $branch_name = $this->branchName;
        $client = new Client();
        $this->client = $client;
        $access_token = $new_token->getToken();
        $headers = [
            'Authorization' => "Bearer $access_token",
        ];
        $this->headers = $headers;
        try {
            $e = null;
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
                        ],
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
        } catch (\Throwable $e) {
        }
        $json = $this->getProcessAndRunWithoutError($new_token->getToken(), $url);
        $closed_with_success = self::hasPrClosedAndPrClosedSuccess($json);
        if ($retries < 20 && !$closed_with_success) {
            $retries++;
            return $this->testPrsClosedBitbucket($retries);
        }
        if ($e) {
            var_dump([$e->getMessage(), $e->getTraceAsString()]);
        }
        self::assertTrue($closed_with_success, 'PR was not both attempted and succeeded with being closed');
    }
}
