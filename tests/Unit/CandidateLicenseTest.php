<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\CandidateLicense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CandidateLicenseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_candidate()
    {
        $candidate = Candidate::factory()->create();
        $license = CandidateLicense::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertInstanceOf(Candidate::class, $license->candidate);
        $this->assertEquals($candidate->id, $license->candidate->id);
    }

    /** @test */
    public function it_correctly_identifies_expired_license()
    {
        $license = CandidateLicense::factory()->create([
            'expiry_date' => Carbon::now()->subDays(1),
        ]);

        $this->assertTrue($license->isExpired());
    }

    /** @test */
    public function it_correctly_identifies_active_license()
    {
        $license = CandidateLicense::factory()->create([
            'expiry_date' => Carbon::now()->addYear(),
        ]);

        $this->assertFalse($license->isExpired());
    }

    /** @test */
    public function it_correctly_identifies_license_expiring_soon()
    {
        $license = CandidateLicense::factory()->create([
            'expiry_date' => Carbon::now()->addDays(45), // Within 90 days
        ]);

        $this->assertTrue($license->isExpiringSoon());
        $this->assertFalse($license->isExpired());
    }

    /** @test */
    public function it_correctly_identifies_license_not_expiring_soon()
    {
        $license = CandidateLicense::factory()->create([
            'expiry_date' => Carbon::now()->addDays(200), // More than 90 days
        ]);

        $this->assertFalse($license->isExpiringSoon());
        $this->assertFalse($license->isExpired());
    }

    /** @test */
    public function expired_scope_returns_only_expired_licenses()
    {
        // Create mix of expired and active licenses
        CandidateLicense::factory()->create(['expiry_date' => Carbon::now()->subDays(10)]);
        CandidateLicense::factory()->create(['expiry_date' => Carbon::now()->subDays(5)]);
        CandidateLicense::factory()->create(['expiry_date' => Carbon::now()->addYear()]);

        $expiredLicenses = CandidateLicense::expired()->get();

        $this->assertCount(2, $expiredLicenses);
    }

    /** @test */
    public function expiring_soon_scope_returns_licenses_expiring_within_90_days()
    {
        // Create various licenses
        CandidateLicense::factory()->create(['expiry_date' => Carbon::now()->addDays(30)]);
        CandidateLicense::factory()->create(['expiry_date' => Carbon::now()->addDays(60)]);
        CandidateLicense::factory()->create(['expiry_date' => Carbon::now()->addDays(100)]);
        CandidateLicense::factory()->create(['expiry_date' => Carbon::now()->subDays(10)]);

        $expiringSoonLicenses = CandidateLicense::expiringSoon()->get();

        $this->assertCount(2, $expiringSoonLicenses);
    }

    /** @test */
    public function license_with_no_expiry_date_is_not_expired()
    {
        $license = CandidateLicense::factory()->create(['expiry_date' => null]);

        $this->assertFalse($license->isExpired());
        $this->assertFalse($license->isExpiringSoon());
    }
}
