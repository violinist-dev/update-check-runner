<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Violinist\ProjectData\ProjectData;

class CloseOnUpdateGithubPublicTest extends CloseOnUpdateGithubTest
{
    protected $psrLogVersion = '101';

    public function setUp() : void
    {
        parent::setUp();
        $this->token = $_SERVER['GITHUB_PRIVATE_USER_TOKEN'];
        $this->url = $_SERVER['GITHUB_PUBLIC_REPO'];
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
