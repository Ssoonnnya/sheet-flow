<?php

namespace Tests\Unit;

use App\Exceptions\InvalidIdentifier;
use App\Exceptions\ProtectedDestinationTable;
use App\Services\Sheets\Identifier;
use InvalidArgumentException;
use Tests\TestCase;

class IdentifierTest extends TestCase
{
    private Identifier $identifiers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identifiers = new Identifier;
    }

    public function test_valid_identifiers_are_accepted(): void
    {
        $this->assertSame('sales', $this->identifiers->assertValid('sales'));
        $this->assertSame('product_price', $this->identifiers->assertValid('product_price'));
    }

    public function test_invalid_identifiers_are_rejected(): void
    {
        $this->expectException(InvalidIdentifier::class);

        $this->identifiers->assertValid('Product Price');
    }

    public function test_protected_tables_cannot_be_written(): void
    {
        $this->expectException(ProtectedDestinationTable::class);

        $this->identifiers->assertWritableTable('users');
    }

    public function test_unique_keys_require_at_least_one_column(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->identifiers->uniqueKeys([]);
    }
}
