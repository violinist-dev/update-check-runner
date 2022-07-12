<?php

namespace Violinist\UpdateCheckRunner\Tests\Integration;

use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Violinist\Slug\Slug;

abstract class CloseOnUpdateBase extends IntegrationBase
{
    protected $branchName;
    protected $psrLogVersion = '100';

    public function tearDown()
    {
        try {
            $this->deleteBranch($this->branchName);
        } catch (\Throwable $e) {
            // That's OK.
        }
    }

    public function setUp()
    {
        parent::setUp();
        $this->branchName = $this->createBranchName();
    }

    protected function createBranchName()
    {
        $length = 13;
        $bytes = random_bytes(ceil($length / 2));
        $id = substr(bin2hex($bytes), 0, $length);
        return sprintf('psrlog%s%s%s', $this->psrLogVersion, random_int(500, 1500), $id);
    }

    protected function deleteBranch($branch_name)
    {
        // Empty default implementation.
    }

    public static function hasPrClosedAndPrClosedSuccess($json)
    {
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
        return $pr_closed_found && $pr_closed_success_found;
    }
}
