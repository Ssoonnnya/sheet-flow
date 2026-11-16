<?php

namespace Tests\Unit;

use App\Services\Sheets\HeaderSlug;
use App\Services\Sheets\Identifier;
use Tests\TestCase;

class HeaderSlugTest extends TestCase
{
    public function test_headers_become_safe_identifiers(): void
    {
        $slug = new HeaderSlug(new Identifier);

        $this->assertSame('product_name', $slug->make('Product Name'));
        $this->assertSame('sales_sample_csv', $slug->make('sales-sample.csv'));
        $this->assertSame('product_name_2', $slug->make('Product Name', ['product_name']));
    }
}
