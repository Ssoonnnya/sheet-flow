<?php

namespace Tests\Unit;

use App\Enums\ColumnType;
use App\Services\Sheets\InferColumnType;
use PHPUnit\Framework\TestCase;

class InferColumnTypeTest extends TestCase
{
    public function test_samples_are_classified(): void
    {
        $infer = new InferColumnType;

        $this->assertSame(ColumnType::Integer, $infer->infer(['1', '2', '3']));
        $this->assertSame(ColumnType::Decimal, $infer->infer(['1.5', '2.0']));
        $this->assertSame(ColumnType::Boolean, $infer->infer(['true', 'false']));
        $this->assertSame(ColumnType::Date, $infer->infer(['2026-01-02', '2026-01-03']));
        $this->assertSame(ColumnType::String, $infer->infer(['Pencil', 'Notebook']));
    }
}
