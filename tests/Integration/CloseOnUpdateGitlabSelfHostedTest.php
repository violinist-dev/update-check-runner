<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Bitbucket\Client;
use Bitbucket\HttpClient\Message\FileResource;
use Gitlab\Client as GitlabClient;
use Gitlab\ResultPager;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\Slug\Slug;

class CloseOnUpdateGitlabSelfHostedTest extends CloseOnUpdateGitlabTest
{
    public function setUp()
    {
        parent::setUp();
        $this->url = getenv('SELF_HOSTED_GITLAB_PRIVATE_REPO');
    }

    protected function createBranchName()
    {
        return 'psrlog100' . random_int(400, 999);
    }

    protected function handleAfterAuthenticate(GitlabClient $client)
    {
        $url = parse_url($this->url);
        if (empty($url['port'])) {
            $url['port'] = 80;
            if ($url['scheme'] === 'https') {
                $url['port'] = 443;
            }
        }
        $client->setUrl(sprintf('%s://%s:%d', $url['scheme'], $url['host'], $url['port']));
    }
}
