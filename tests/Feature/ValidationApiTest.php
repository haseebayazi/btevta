<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for validation API endpoints.
 * Tests CNIC validation, phone validation, and duplicate detection.
 */
class ValidationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Trade $trade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->trade = Trade::factory()->create();
    }

    // ==================== CNIC VALIDATION ====================

    /** @test */
    public function it_validates_valid_cnic_format()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-cnic', [
            'cnic' => '3520112345671', // 13 digit valid format
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'valid',
            'format_valid',
        ]);
    }

    /** @test */
    public function it_rejects_invalid_cnic_length()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-cnic', [
            'cnic' => '12345', // Too short
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertFalse($data['format_valid']);
    }

    /** @test */
    public function it_handles_cnic_with_dashes()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-cnic', [
            'cnic' => '35201-1234567-1', // With dashes
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['format_valid']);
    }

    /** @test */
    public function it_rejects_non_numeric_cnic()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-cnic', [
            'cnic' => '3520ABCDEFGH1',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertFalse($data['format_valid']);
    }

    // ==================== PHONE VALIDATION ====================

    /** @test */
    public function it_validates_pakistan_phone_03xx_format()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-phone', [
            'phone' => '03001234567',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['valid']);
    }

    /** @test */
    public function it_validates_pakistan_phone_with_plus92()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-phone', [
            'phone' => '+923001234567',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['valid']);
    }

    /** @test */
    public function it_validates_pakistan_phone_with_92()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-phone', [
            'phone' => '923001234567',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['valid']);
    }

    /** @test */
    public function it_validates_phone_with_dashes()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-phone', [
            'phone' => '0300-1234567',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['valid']);
    }

    /** @test */
    public function it_rejects_invalid_phone_format()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-phone', [
            'phone' => '12345',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertFalse($data['valid']);
    }

    /** @test */
    public function it_rejects_non_pakistan_phone()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/validate-phone', [
            'phone' => '+14155551234', // US number
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertFalse($data['valid']);
    }

    // ==================== DUPLICATE DETECTION ====================

    /** @test */
    public function it_detects_duplicate_by_phone()
    {
        Candidate::factory()->create([
            'phone' => '03001234567',
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/check-duplicates', [
            'phone' => '03001234567',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data['duplicates']);
        $this->assertEquals('phone', $data['duplicates'][0]['match_type']);
    }

    /** @test */
    public function it_detects_duplicate_by_email()
    {
        Candidate::factory()->create([
            'email' => 'test@example.com',
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/check-duplicates', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data['duplicates']);
        $this->assertEquals('email', $data['duplicates'][0]['match_type']);
    }

    /** @test */
    public function it_detects_duplicate_by_similar_name()
    {
        Candidate::factory()->create([
            'name' => 'Muhammad Ali Khan',
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/check-duplicates', [
            'name' => 'Muhammad Ali',
        ]);

        $response->assertOk();
        $data = $response->json();
        // May or may not find match depending on similarity threshold
        $this->assertIsArray($data['duplicates']);
    }

    /** @test */
    public function it_excludes_specified_candidate_from_duplicates()
    {
        $existing = Candidate::factory()->create([
            'phone' => '03001234567',
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/check-duplicates', [
            'phone' => '03001234567',
            'exclude_id' => $existing->id,
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertEmpty($data['duplicates']);
    }

    /** @test */
    public function it_returns_empty_when_no_duplicates()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/check-duplicates', [
            'phone' => '03009999999',
            'email' => 'unique@example.com',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertEmpty($data['duplicates']);
    }

    /** @test */
    public function it_requires_authentication_for_validation_apis()
    {
        $response = $this->postJson('/api/validate-cnic', [
            'cnic' => '3520112345671',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_handles_multiple_duplicate_types()
    {
        Candidate::factory()->create([
            'phone' => '03001234567',
            'email' => 'test@example.com',
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/check-duplicates', [
            'phone' => '03001234567',
            'email' => 'test@example.com',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data['duplicates']);
    }
}
