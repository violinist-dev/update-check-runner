<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Gitlab\Client as GitlabClient;

class CloseOnUpdateGitlabSelfHostedTest extends CloseOnUpdateGitlabTest
{
    protected $psrLogVersion = '100';

    public function setUp() : void
    {
        parent::setUp();
        $this->url = getenv('SELF_HOSTED_GITLAB_PRIVATE_REPO');
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
