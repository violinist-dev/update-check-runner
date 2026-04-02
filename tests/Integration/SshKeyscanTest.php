<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Symfony\Component\Process\Process;
use Violinist\Slug\Slug;

class SshKeyscanTest extends IntegrationBase
{

    public function testSshKeyscanRunsForGithub()
    {
        $url = $_SERVER['GITHUB_PRIVATE_REPO'];
        $token = $_SERVER['GITHUB_PRIVATE_USER_TOKEN'];
        $hostname = Slug::createFromUrl($url)->getProvider();

        // Create a temporary directory to mount as /root/.ssh inside the
        // container so we can inspect known_hosts after the run.
        $tmpDir = sys_get_temp_dir() . '/violinist-ssh-test-' . uniqid();
        mkdir($tmpDir, 0700, true);
        file_put_contents($tmpDir . '/known_hosts', '');

        $other_env = [
            'LICENCE_KEY' => getenv('VALID_CI_LICENCE'),
        ];
        $command = [
            'docker',
            'run',
            '-i',
            '--rm',
            '-e',
            'user_token=' . $token,
            '-e',
            'project_url=' . $url,
        ];
        foreach ($other_env as $var => $value) {
            $command[] = '-e';
            $command[] = sprintf('%s=%s', $var, $value);
        }
        $command[] = '-v';
        $command[] = $tmpDir . ':/root/.ssh';
        $command[] = 'update-check-runner';

        $process = new Process($command, null, null, null, 600);
        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'Docker did not exit with exit code 0');
        $json = @json_decode($process->getOutput());
        $this->assertNotEmpty($json);

        $this->assertStandardOutput($url, $json);

        // Verify the ssh-keyscan command was executed.
        $this->findMessage(sprintf('Creating command ssh-keyscan -t rsa %s >> ~/.ssh/known_hosts', $hostname), $json);

        // Verify that the keyscan output was captured in the Docker stderr,
        // which confirms ssh-keyscan actually ran and contacted the host.
        $this->assertStringContainsString($hostname, $process->getErrorOutput(), 'ssh-keyscan stderr should reference the scanned hostname');

        // Clean up.
        @unlink($tmpDir . '/known_hosts');
        @rmdir($tmpDir);
    }
}
