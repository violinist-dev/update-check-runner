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
        $process = new Process(sprintf(
            'docker run -i --rm -e user_token=%s -e project_url=%s update-check-runner',
            getenv('user_token'),
            getenv('project_url')
        ), null, null, null, 600);
        $process->run();
        $this->assertEquals(0, $process->getExitCode());
        $json = @json_decode($process->getOutput());
        $this->assertEquals(sprintf('Starting update check for %s', Slug::createFromUrl(getenv('project_url'))->getSlug()), $json[0]->message);
    }

    public function testGitlabOutput()
    {
        $process = new Process(sprintf(
            'docker run -i --rm -e user_token=%s -e project_url=%s update-check-runner',
            getenv('user_gitlab_token'),
            getenv('gitlab_project_url')
        ), null, null, null, 600);
        $process->run();
        $this->assertEquals(0, $process->getExitCode());
        $json = @json_decode($process->getOutput());
        $this->assertEquals(sprintf('Starting update check for %s', Slug::createFromUrl(getenv('gitlab_project_url'))->getSlug()), $json[0]->message);
    }
}
