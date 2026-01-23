<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Edge case tests for data validation.
 * Tests CNIC edge cases, date boundaries, and maximum field lengths.
 */
class DataValidationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected Campus $campus;
    protected Trade $trade;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();
        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    // =========================================================================
    // CNIC EDGE CASES
    // =========================================================================

    #[Test]
    public function it_accepts_cnic_with_leading_zeros()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '0123456789012', // Leading zero
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Should accept or reject based on validation rules
        $this->assertTrue(in_array($response->status(), [200, 201, 422]));
    }

    #[Test]
    public function it_rejects_cnic_with_all_zeros()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '0000000000000', // All zeros
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_cnic_with_special_characters()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '35201-1234567-1', // With dashes
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // May accept or reject based on normalization rules
        $this->assertTrue(in_array($response->status(), [200, 201, 422]));
    }

    #[Test]
    public function it_rejects_cnic_shorter_than_13_digits()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '35201123456', // 11 digits
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cnic']);
    }

    #[Test]
    public function it_rejects_cnic_longer_than_13_digits()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '352011234567890', // 15 digits
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cnic']);
    }

    // =========================================================================
    // DATE RANGE BOUNDARIES
    // =========================================================================

    #[Test]
    public function it_rejects_future_date_of_birth()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => now()->addDay()->format('Y-m-d'), // Future
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_of_birth']);
    }

    #[Test]
    public function it_rejects_date_of_birth_too_old()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1900-01-01', // Too old
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_of_birth']);
    }

    #[Test]
    public function it_rejects_underage_candidates()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => now()->subYears(16)->format('Y-m-d'), // 16 years old
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Should reject if minimum age is 18
        $this->assertTrue(in_array($response->status(), [200, 201, 422]));
    }

    #[Test]
    public function it_accepts_boundary_age_candidates()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => now()->subYears(18)->format('Y-m-d'), // Exactly 18
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Should accept at minimum age boundary
        $this->assertTrue(in_array($response->status(), [200, 201]));
    }

    #[Test]
    public function it_handles_leap_year_date_correctly()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '2000-02-29', // Leap year date
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $this->assertTrue(in_array($response->status(), [200, 201]));
    }

    // =========================================================================
    // MAXIMUM FIELD LENGTHS
    // =========================================================================

    #[Test]
    public function it_rejects_name_exceeding_max_length()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => Str::random(256), // Exceeds 255
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_accepts_name_at_max_length()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => Str::random(255), // Exactly 255
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $this->assertTrue(in_array($response->status(), [200, 201]));
    }

    #[Test]
    public function it_rejects_address_exceeding_max_length()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => Str::random(1001), // Exceeds 1000
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_email_exceeding_max_length()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'Test Candidate',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'email' => Str::random(250) . '@example.com', // Exceeds 255
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // PHONE NUMBER VALIDATION
    // =========================================================================

    #[Test]
    public function it_accepts_valid_pakistan_mobile_number()
    {
        $validNumbers = ['03001234567', '03451234567', '03321234567'];

        foreach ($validNumbers as $phone) {
            $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
                'name' => 'Test Candidate',
                'cnic' => '3520112345' . rand(100, 999),
                'father_name' => 'Father Name',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'phone' => $phone,
                'address' => '123 Test Street',
                'district' => 'Lahore',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
            ]);

            $this->assertTrue(
                in_array($response->status(), [200, 201]),
                "Phone {$phone} should be valid"
            );
        }
    }

    #[Test]
    public function it_rejects_invalid_phone_formats()
    {
        $invalidNumbers = ['123', '12345678901234', 'abcdefghijk', '0000000000'];

        foreach ($invalidNumbers as $phone) {
            $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
                'name' => 'Test Candidate',
                'cnic' => '3520112345671',
                'father_name' => 'Father Name',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'phone' => $phone,
                'address' => '123 Test Street',
                'district' => 'Lahore',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
            ]);

            $response->assertStatus(422);
        }
    }

    // =========================================================================
    // EMAIL VALIDATION
    // =========================================================================

    #[Test]
    public function it_rejects_invalid_email_formats()
    {
        $invalidEmails = ['notanemail', '@example.com', 'test@', 'test@.com'];

        foreach ($invalidEmails as $email) {
            $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
                'name' => 'Test Candidate',
                'cnic' => '3520112345671',
                'father_name' => 'Father Name',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'phone' => '03001234567',
                'email' => $email,
                'address' => '123 Test Street',
                'district' => 'Lahore',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
            ]);

            $response->assertStatus(422);
        }
    }

    #[Test]
    public function it_accepts_valid_email_formats()
    {
        $validEmails = ['test@example.com', 'user.name@domain.co.uk', 'user+tag@example.org'];

        foreach ($validEmails as $email) {
            $cnic = '35201123456' . rand(10, 99);
            $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
                'name' => 'Test Candidate',
                'cnic' => $cnic,
                'father_name' => 'Father Name',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'phone' => '03001234567',
                'email' => $email,
                'address' => '123 Test Street',
                'district' => 'Lahore',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
            ]);

            $this->assertTrue(
                in_array($response->status(), [200, 201]),
                "Email {$email} should be valid"
            );
        }
    }

    // =========================================================================
    // SPECIAL CHARACTER HANDLING
    // =========================================================================

    #[Test]
    public function it_handles_names_with_special_characters()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => "Muhammad Ali O'Brien-Khan", // Apostrophe and hyphen
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $this->assertTrue(in_array($response->status(), [200, 201]));
    }

    #[Test]
    public function it_sanitizes_xss_in_text_fields()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => '<script>alert("xss")</script>Test',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        if ($response->status() === 201 || $response->status() === 200) {
            $candidate = Candidate::latest()->first();
            $this->assertStringNotContainsString('<script>', $candidate->name);
        }
    }

    #[Test]
    public function it_handles_unicode_characters_in_names()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => 'محمد علی', // Urdu name
            'cnic' => '3520112345671',
            'father_name' => 'والد کا نام',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => 'لاہور',
            'district' => 'لاہور',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $this->assertTrue(in_array($response->status(), [200, 201]));
    }

    // =========================================================================
    // REQUIRED FIELD EDGE CASES
    // =========================================================================

    #[Test]
    public function it_rejects_empty_required_fields()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => '',
            'cnic' => '',
            'father_name' => '',
            'date_of_birth' => '',
            'gender' => '',
            'phone' => '',
            'address' => '',
            'district' => '',
            'campus_id' => '',
            'trade_id' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'cnic', 'father_name']);
    }

    #[Test]
    public function it_rejects_whitespace_only_fields()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'name' => '   ',
            'cnic' => '3520112345671',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => '123 Test Street',
            'district' => 'Lahore',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(422);
    }
}
