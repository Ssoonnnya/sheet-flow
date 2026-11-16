<?php

namespace Tests\Feature\Api;

use App\Contracts\SheetReader;
use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use App\Models\ImportRun;
use App\Models\SheetSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSheetReader;
use Tests\TestCase;

class SourceCrudPaginationTest extends TestCase
{
    use RefreshDatabase;

    private FakeSheetReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = new FakeSheetReader;
        $this->app->instance(SheetReader::class, $this->reader);
    }

    public function test_sources_are_paginated(): void
    {
        $user = User::factory()->create();

        SheetSource::factory()->count(16)->create();

        $this->actingAs($user)
            ->getJson('/api/sources?per_page=15')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_runs_are_paginated_with_allowed_page_sizes(): void
    {
        $user = User::factory()->create();

        ImportRun::factory()->count(16)->create([
            'status' => ImportRunStatus::Completed,
            'trigger' => ImportTrigger::Manual,
        ]);

        $this->actingAs($user)
            ->getJson('/api/import-runs?per_page=15')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16);

        $this->actingAs($user)
            ->getJson('/api/import-runs?per_page=99')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15);
    }

    public function test_source_name_and_active_can_be_updated(): void
    {
        $user = User::factory()->create();
        $source = SheetSource::factory()->create([
            'name' => 'Old name',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->putJson("/api/sources/{$source->id}", [
                'name' => 'New name',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('sheet_sources', [
            'id' => $source->id,
            'name' => 'New name',
            'is_active' => false,
        ]);
    }

    public function test_source_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $source = SheetSource::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/api/sources/{$source->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('sheet_sources', [
            'id' => $source->id,
        ]);
    }

    public function test_changing_spreadsheet_id_reprovisions_tabs(): void
    {
        $user = User::factory()->create();

        $this->reader->setTitle('old-sheet-id', 'Old Sheet');
        $this->reader->put('old-sheet-id', 'OldTab', [
            ['date' => '2026-01-01'],
        ]);

        $source = $this->actingAs($user)
            ->postJson('/api/sources', ['spreadsheet_id' => 'old-sheet-id'])
            ->assertCreated()
            ->json('data');

        $this->reader->setTitle('new-sheet-id', 'New Sheet');
        $this->reader->put('new-sheet-id', 'NewTab', [
            ['amount' => '3'],
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/sources/'.$source['id'], [
                'spreadsheet_id' => 'new-sheet-id',
            ])
            ->assertOk();

        $this->assertSame($source['id'], $response->json('data.id'));
        $this->assertSame('new-sheet-id', $response->json('data.spreadsheet_id'));
        $this->assertSame('New Sheet', $response->json('data.name'));
        $this->assertTrue(collect($response->json('data.tabs'))->contains(
            fn (array $tab): bool => $tab['name'] === 'NewTab',
        ));
        $this->assertDatabaseCount('sheet_sources', 1);
    }
}
