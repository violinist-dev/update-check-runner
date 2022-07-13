<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\ProjectData\ProjectData;
use Violinist\Slug\Slug;

class CloseOnUpdateGithubPublicTest extends CloseOnUpdateGithubTest
{
    protected $psrLogVersion = '101';

    public function setUp()
    {
        parent::setUp();
        $this->token = getenv('GITHUB_PRIVATE_USER_TOKEN');
        $this->url = getenv('GITHUB_PUBLIC_REPO');
    }

    protected function getExtraParams()
    {
        $extra_params = parent::getExtraParams();
        $project = new ProjectData();
        $project->setNid(getenv('GITHUB_PUBLIC_PROJECT_NID'));
        $extra_params = $extra_params + [
            'project' => sprintf("'%s'", json_encode(serialize($project))),
            'fork_to' => getenv('GITHUB_FORK_TO'),
            'token_url' => getenv('TOKEN_URL'),
        ];
        return $extra_params;
    }
}
