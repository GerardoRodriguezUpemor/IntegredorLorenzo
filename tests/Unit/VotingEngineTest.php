<?php

namespace Tests\Unit;

use App\Domain\Scheduling\VotingEngine;
use App\Models\ScheduleOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class VotingEngineTest extends TestCase
{
    private VotingEngine $votingEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->votingEngine = new VotingEngine();
    }

    public function test_validates_minimum_and_maximum_options()
    {
        // Should not throw exception
        $this->votingEngine->validateOptionCount(3);
        $this->votingEngine->validateOptionCount(4);
        $this->votingEngine->validateOptionCount(5);

        // Should assert
        $this->assertTrue(true);
    }

    public function test_throws_exception_for_less_than_minimum_options()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->votingEngine->validateOptionCount(2);
    }

    public function test_throws_exception_for_more_than_maximum_options()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->votingEngine->validateOptionCount(6);
    }
}
