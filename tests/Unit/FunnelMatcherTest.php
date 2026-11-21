<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\DataExplorer\Query\FunnelMatcher;

/**
 * @group DataExplorer
 * @group FunnelMatcherTest
 * @group Plugins
 */
class FunnelMatcherTest extends TestCase
{
    public function test_aVisitThroughEveryStep_countsOnePassThatCompletes(): void
    {
        $matcher = new FunnelMatcher([[], [], []]);
        $matcher->addVisit([$this->hit(1, 0, [0]), $this->hit(2, 10, [1]), $this->hit(3, 30, [2])]);

        $totals = $matcher->totals()[''];
        $this->assertSame([1, 1, 1], $totals['entered']);
        $this->assertSame([1, 1, 0], $totals['continued']);
        $this->assertSame([1, 0, 0], $totals['started']);
        $this->assertSame([0, 10, 20], $totals['time']);
    }

    public function test_everyMatchOfTheFirstStep_startsANewPass(): void
    {
        $matcher = new FunnelMatcher([[], []]);
        $matcher->addVisit([
            $this->hit(1, 0, [0]),
            $this->hit(2, 10, [0]),
            $this->hit(3, 20, [1]),
        ]);

        $totals = $matcher->totals()[''];
        $this->assertSame([2, 0], $totals['started']);
        $this->assertSame([2, 1], $totals['entered'], 'the first pass ends at the second entry and abandons');
        $this->assertSame([1, 0], $totals['continued']);
    }

    public function test_aHitMatchingTheFirstStep_alsoCompletesTheRunningPass(): void
    {
        $matcher = new FunnelMatcher([[], []]);
        $matcher->addVisit([$this->hit(1, 0, [0]), $this->hit(2, 10, [0, 1])]);

        $totals = $matcher->totals()[''];
        $this->assertSame([2, 0], $totals['started']);
        $this->assertSame([2, 1], $totals['entered']);
    }

    public function test_aClosedFunnel_ignoresVisitsStartingLater(): void
    {
        $matcher = new FunnelMatcher([[], []]);
        $matcher->addVisit([$this->hit(1, 0, [1])]);

        $this->assertSame([], $matcher->totals());
    }

    public function test_anOpenFunnel_startsAPassAtAnyStep(): void
    {
        $matcher = new FunnelMatcher([[], [], []], true);
        $matcher->addVisit([$this->hit(1, 0, [1]), $this->hit(2, 5, [2])]);

        $totals = $matcher->totals()[''];
        $this->assertSame([0, 1, 1], $totals['entered']);
        $this->assertSame([0, 1, 0], $totals['started']);
    }

    public function test_aStepDirectlyFollowed_needsTheNextAction(): void
    {
        $matcher = new FunnelMatcher([[], ['direct' => true]]);
        $matcher->addVisit([$this->hit(1, 0, [0]), $this->hit(3, 10, [1], 2)]);
        $matcher->addVisit([$this->hit(5, 0, [0]), $this->hit(6, 10, [1], 5)]);

        $this->assertSame([2, 1], $matcher->totals()['']['entered']);
    }

    public function test_aConversionOnTheSameAction_followsDirectly(): void
    {
        $matcher = new FunnelMatcher([[], ['direct' => true]]);
        $matcher->addVisit([$this->hit(1, 0, [0]), $this->hit(1, 0, [1], null)]);

        $this->assertSame([1, 1], $matcher->totals()['']['entered']);
    }

    public function test_aTimeLimit_dropsStepsReachedTooLate(): void
    {
        $matcher = new FunnelMatcher([[], ['within' => 60]]);
        $matcher->addVisit([$this->hit(1, 0, [0]), $this->hit(2, 61, [1])]);
        $matcher->addVisit([$this->hit(3, 0, [0]), $this->hit(4, 60, [1])]);

        $this->assertSame([2, 1], $matcher->totals()['']['entered']);
    }

    public function test_anOptionalStep_isCountedWhenReachedBeforeTheNextRequiredOne(): void
    {
        $matcher = new FunnelMatcher([[], ['optional' => true], []]);
        $matcher->addVisit([$this->hit(1, 0, [0]), $this->hit(2, 5, [1]), $this->hit(3, 9, [2])]);
        $matcher->addVisit([$this->hit(4, 0, [0]), $this->hit(5, 5, [2]), $this->hit(6, 9, [1])]);

        $totals = $matcher->totals()[''];
        $this->assertSame([2, 1, 2], $totals['entered']);
        $this->assertSame([2, 1, 0], $totals['continued'], 'step 1 goes on to step 3, the next required one');
    }

    public function test_totals_areSplitByTheBreakdownValueOfTheEntry(): void
    {
        $matcher = new FunnelMatcher([[], []]);
        $matcher->addVisit([$this->hit(1, 0, [0], null, 'Desktop'), $this->hit(2, 5, [1], null, 'Smartphone')]);
        $matcher->addVisit([$this->hit(3, 0, [0], null, 'Smartphone')]);

        $totals = $matcher->totals();
        $this->assertSame([1, 1], $totals['Desktop']['entered']);
        $this->assertSame([1, 0], $totals['Smartphone']['entered']);
    }

    private function hit(int $pos, int $time, array $steps, ?int $prev = -1, ?string $key = null): array
    {
        return [
            'pos' => $pos,
            'prev' => $prev === -1 ? $pos - 1 : $prev,
            'time' => $time,
            'steps' => $steps,
            'key' => $key,
        ];
    }
}
