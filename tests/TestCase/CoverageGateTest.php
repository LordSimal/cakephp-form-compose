<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class CoverageGateTest extends TestCase
{
    /**
     * Execute the actual CLI gate against a temporary report.
     */
    private function gate(string $xml): int
    {
        $path = tempnam(sys_get_temp_dir(), 'form-compose-coverage-');
        if ($path === false) {
            throw new RuntimeException('Cannot create a temporary coverage report.');
        }
        file_put_contents($path, $xml);
        try {
            $pipes = [];
            $process = proc_open(
                [PHP_BINARY, dirname(__DIR__, 2) . '/scripts/check-coverage.php', $path],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            if (!is_resource($process)) {
                throw new RuntimeException('Cannot execute the coverage gate.');
            }
            foreach ($pipes as $pipe) {
                stream_get_contents($pipe);
                fclose($pipe);
            }

            return proc_close($process);
        } finally {
            unlink($path);
        }
    }

    public function testStrictBoundaryIsEnforcedWithoutRounding(): void
    {
        static::assertSame(
            1,
            $this->gate('<coverage><project><metrics statements="100" coveredstatements="90"/></project></coverage>'),
        );
        static::assertSame(
            0,
            $this->gate(
                '<coverage><project><metrics statements="100000" coveredstatements="90001"/></project></coverage>',
            ),
        );
    }

    public function testEmptyIncompleteAndInvalidReportsFail(): void
    {
        static::assertSame(
            1,
            $this->gate('<coverage><project><metrics statements="0" coveredstatements="0"/></project></coverage>'),
        );
        static::assertSame(1, $this->gate('<coverage><project/></coverage>'));
        static::assertSame(1, $this->gate('invalid XML'));
        static::assertSame(
            1,
            $this->gate('<coverage><project><metrics statements="100" coveredstatements="101"/></project></coverage>'),
        );
    }
}
