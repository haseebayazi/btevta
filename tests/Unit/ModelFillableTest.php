<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Campus;
use App\Models\Trade;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelFillableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Campus model fillable does not include non-existent fields
     */
    public function test_campus_fillable_only_contains_existing_fields(): void
    {
        $campus = new Campus();
        $fillable = $campus->getFillable();

        // These fields should NOT be in fillable (they don't exist in DB)
        $this->assertNotContains('location', $fillable);
        $this->assertNotContains('province', $fillable);
        $this->assertNotContains('district', $fillable);

        // These fields SHOULD be in fillable
        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('address', $fillable);
        $this->assertContains('city', $fillable);
        $this->assertContains('email', $fillable);
    }

    /**
     * Test Trade model fillable does not include non-existent fields
     */
    public function test_trade_fillable_only_contains_existing_fields(): void
    {
        $trade = new Trade();
        $fillable = $trade->getFillable();

        // duration_weeks should NOT be in fillable (doesn't exist in DB)
        $this->assertNotContains('duration_weeks', $fillable);

        // These fields SHOULD be in fillable
        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('duration_months', $fillable);
        $this->assertContains('description', $fillable);
    }

    /**
     * Test Campus can be created with valid fillable fields
     */
    public function test_campus_can_be_created_with_valid_fields(): void
    {
        $campus = Campus::create([
            'name' => 'Test Campus',
            'code' => 'TC001',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'email' => 'test@campus.com',
            'phone' => '123-456-7890',
            'contact_person' => 'John Doe',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('campuses', [
            'name' => 'Test Campus',
            'code' => 'TC001',
        ]);
    }

    /**
     * Test Trade can be created with valid fillable fields
     */
    public function test_trade_can_be_created_with_valid_fields(): void
    {
        $trade = Trade::create([
            'name' => 'Test Trade',
            'code' => 'TT001',
            'category' => 'Technical',
            'description' => 'A test trade',
            'duration_months' => 6,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('trades', [
            'name' => 'Test Trade',
            'code' => 'TT001',
        ]);
    }
}
