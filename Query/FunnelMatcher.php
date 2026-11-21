<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

/**
 * Walks the actions and conversions of one visit that match a funnel step, and counts its passes through the
 * funnel. Every match of the first step starts a new pass, so a visit can go through the funnel several times; in
 * an open funnel a pass can also start at a later step while none is running.
 *
 * A candidate is an action or a conversion of the visit matching at least one step:
 * - pos: id of its action (the action a conversion was recorded on)
 * - prev: id of the action before that one in the visit, null for the first one
 * - time: timestamp
 * - steps: indexes of the steps it matches
 * - key: breakdown value, read on the candidate a pass starts on
 *
 * Candidates come in visit order: by time, then by action, a conversion after the action it was recorded on.
 */
class FunnelMatcher
{
    /** @var array<int, array{optional: bool, direct: bool, within: ?int}> */
    private $steps;
    /** @var bool */
    private $open;
    /** @var array<string, array{started: int[], entered: int[], continued: int[], time: int[], timed: int[]}> */
    private $totals = [];

    /**
     * @param array<int, array{optional?: bool, direct?: bool, within?: ?int}> $steps within in seconds
     */
    public function __construct(array $steps, bool $open = false)
    {
        $this->steps = [];
        foreach (array_values($steps) as $index => $step) {
            $this->steps[] = [
                'optional' => $index > 0 && !empty($step['optional']),
                'direct' => $index > 0 && !empty($step['direct']),
                'within' => $index > 0 && isset($step['within']) && $step['within'] > 0 ? (int) $step['within'] : null,
            ];
        }
        $this->open = $open;
    }

    /**
     * Adds the passes of one visit to the totals.
     *
     * @param array<int, array{pos: int, prev: ?int, time: int, steps: int[], key?: string|null}> $candidates
     */
    public function addVisit(array $candidates): void
    {
        foreach ($this->passes(array_values($candidates)) as $pass) {
            $this->count($pass);
        }
    }

    /**
     * Totals per breakdown value ('' without a breakdown), per step: the passes that started at it, that reached
     * it, that went on to the next required step, the seconds since the step reached before it and how many passes
     * those seconds sum.
     *
     * @return array<string, array{started: int[], entered: int[], continued: int[], time: int[], timed: int[]}>
     */
    public function totals(): array
    {
        return $this->totals;
    }

    /**
     * @return array<int, array{key: string, entry: int, reached: array<int, int>, seconds: array<int, int>}>
     */
    public function passes(array $candidates): array
    {
        $passes = [];
        $current = null;
        foreach ($candidates as $index => $candidate) {
            $steps = array_map('intval', $candidate['steps']);
            $entry = null;
            if (in_array(0, $steps, true)) {
                $entry = 0;
            } elseif ($this->open && $current === null && $steps) {
                $entry = min($steps);
            }
            if ($entry === null) {
                if ($current !== null) {
                    $current['candidates'][] = $candidate;
                }
                continue;
            }
            if ($current !== null) {
                // the hit that starts a new pass can still be the next step of the running one
                $current['candidates'][] = $candidate;
                $passes[] = $current;
            }
            $current = ['entry' => $entry, 'candidates' => [$candidate]];
        }
        if ($current !== null) {
            $passes[] = $current;
        }

        return array_map(fn (array $pass) => $this->walk($pass['entry'], $pass['candidates']), $passes);
    }

    /**
     * @return array{key: string, entry: int, reached: array<int, int>, seconds: array<int, int>}
     */
    private function walk(int $entry, array $candidates): array
    {
        $reached = [$entry => $candidates[0]['time']];
        $seconds = [];
        $last = 0;
        $count = count($this->steps);

        for ($step = $entry + 1; $step < $count; $step++) {
            if ($this->steps[$step]['optional']) {
                $required = $this->nextRequired($step);
                $limit = $required === null ? count($candidates) : ($this->find($candidates, $last, $required) ?? count($candidates));
                $found = $this->find($candidates, $last, $step);
                if ($found === null || $found >= $limit) {
                    continue;
                }
            } else {
                $found = $this->find($candidates, $last, $step);
                if ($found === null) {
                    break;
                }
            }
            $seconds[$step] = $candidates[$found]['time'] - $candidates[$last]['time'];
            $reached[$step] = $candidates[$found]['time'];
            $last = $found;
        }

        return [
            'key' => (string) ($candidates[0]['key'] ?? ''),
            'entry' => $entry,
            'reached' => $reached,
            'seconds' => $seconds,
        ];
    }

    /**
     * The first candidate after $from that matches $step and follows the candidate $from as the step requires.
     */
    private function find(array $candidates, int $from, int $step): ?int
    {
        $previous = $candidates[$from];
        $rule = $this->steps[$step];
        for ($i = $from + 1, $n = count($candidates); $i < $n; $i++) {
            $candidate = $candidates[$i];
            if ($rule['within'] !== null && $candidate['time'] - $previous['time'] > $rule['within']) {
                return null;
            }
            if (!in_array($step, array_map('intval', $candidate['steps']), true)) {
                continue;
            }
            if ($rule['direct'] && !self::followsDirectly($previous, $candidate)) {
                continue;
            }

            return $i;
        }

        return null;
    }

    /**
     * The next action of the visit, or a conversion recorded on the same action or on the next one.
     */
    private static function followsDirectly(array $previous, array $candidate): bool
    {
        return $candidate['pos'] === $previous['pos'] || ($candidate['prev'] !== null && $candidate['prev'] === $previous['pos']);
    }

    private function nextRequired(int $after): ?int
    {
        for ($step = $after + 1, $count = count($this->steps); $step < $count; $step++) {
            if (!$this->steps[$step]['optional']) {
                return $step;
            }
        }

        return null;
    }

    private function count(array $pass): void
    {
        $key = $pass['key'];
        $count = count($this->steps);
        if (!isset($this->totals[$key])) {
            $zeros = array_fill(0, $count, 0);
            $this->totals[$key] = [
                'started' => $zeros,
                'entered' => $zeros,
                'continued' => $zeros,
                'time' => $zeros,
                'timed' => $zeros,
            ];
        }
        $totals = &$this->totals[$key];

        $totals['started'][$pass['entry']]++;
        foreach (array_keys($pass['reached']) as $step) {
            $totals['entered'][$step]++;
            $next = $this->nextRequired($step);
            if ($next !== null && isset($pass['reached'][$next])) {
                $totals['continued'][$step]++;
            }
            if (isset($pass['seconds'][$step])) {
                $totals['time'][$step] += $pass['seconds'][$step];
                $totals['timed'][$step]++;
            }
        }
    }
}
