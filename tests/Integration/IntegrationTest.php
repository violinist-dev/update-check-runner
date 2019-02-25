<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Violinist\Slug\Slug;

class IntegrationTest extends TestCase
{
    /**
     * This is so not the way phpunit is supposed to be used.
     */
    public function testOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('user_token'), getenv('project_url'));
        $this->assertProjectStarting(getenv('project_url'), $json);
        $this->assertRepoCloned($json);
        $this->assertComposerInstalled($json);
    }

    public function testGitlabOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('user_gitlab_token'), getenv('gitlab_project_url'));
        $this->assertProjectStarting(getenv('gitlab_project_url'), $json);
        $this->assertRepoCloned($json);
        $this->assertComposerInstalled($json);
    }

    public function testGitlabSelfhostedOutput()
    {
        $json = $this->getProcessAndRunWithoutError(getenv('user_token_self_hosted'), getenv('project_url_self_hosted'));
        $this->assertProjectStarting(getenv('project_url_self_hosted'), $json);
        $this->assertRepoCloned($json);
        $this->assertComposerInstalled($json);
    }

    protected function assertProjectStarting($url, $json)
    {
        $this->assertEquals(sprintf('Starting update check for %s', Slug::createFromUrl($url)->getSlug()), $json[0]->message);
    }

    protected function assertRepoCloned($json)
    {
        $this->assertEquals('Repository cloned', $json[3]->message);
    }

    protected function assertComposerInstalled($json)
    {
        $this->assertEquals('composer install completed successfully', $json[7]->message);
    }

    protected function getProcessAndRunWithoutError($token, $url)
    {
        $process = new Process(sprintf(
            'docker run -i --rm -e user_token=%s -e project_url=%s update-check-runner',
            $token,
            $url
        ), null, null, null, 600);
        $process->run();
        $this->assertEquals(0, $process->getExitCode());
        $json = @json_decode($process->getOutput());
        $this->assertFalse(empty($json));
        return $json;
    }
}
