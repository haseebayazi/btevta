<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Employer;
use App\Models\Country;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployerModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_employer()
    {
        $country = Country::factory()->create();

        $employer = Employer::create([
            'permission_number' => 'PERM-2026-001',
            'visa_issuing_company' => 'ARAMCO',
            'country_id' => $country->id,
            'sector' => 'Oil & Gas',
            'trade' => 'Welder',
            'basic_salary' => 2500.00,
            'salary_currency' => 'SAR',
            'food_by_company' => true,
            'accommodation_by_company' => true,
            'transport_by_company' => false,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('employers', [
            'permission_number' => 'PERM-2026-001',
            'visa_issuing_company' => 'ARAMCO',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_country()
    {
        $country = Country::factory()->create(['name' => 'Saudi Arabia']);
        $employer = Employer::factory()->create(['country_id' => $country->id]);

        $this->assertInstanceOf(Country::class, $employer->country);
        $this->assertEquals('Saudi Arabia', $employer->country->name);
    }

    /** @test */
    public function it_can_have_many_candidates()
    {
        $employer = Employer::factory()->create();
        $candidate1 = Candidate::factory()->create();
        $candidate2 = Candidate::factory()->create();

        $employer->candidates()->attach($candidate1->id, [
            'is_current' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $employer->candidates()->attach($candidate2->id, [
            'is_current' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $this->assertCount(2, $employer->candidates);
    }

    /** @test */
    public function it_can_get_current_candidates_only()
    {
        $employer = Employer::factory()->create();
        $candidate1 = Candidate::factory()->create();
        $candidate2 = Candidate::factory()->create();
        $candidate3 = Candidate::factory()->create();

        $employer->candidates()->attach($candidate1->id, ['is_current' => true, 'assigned_at' => now(), 'assigned_by' => 1]);
        $employer->candidates()->attach($candidate2->id, ['is_current' => true, 'assigned_at' => now(), 'assigned_by' => 1]);
        $employer->candidates()->attach($candidate3->id, ['is_current' => false, 'assigned_at' => now()->subMonths(6), 'assigned_by' => 1]);

        $currentCandidates = $employer->currentCandidates;

        $this->assertCount(2, $currentCandidates);
    }

    /** @test */
    public function it_belongs_to_a_creator()
    {
        $user = User::factory()->create();
        $employer = Employer::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $employer->creator);
        $this->assertEquals($user->id, $employer->creator->id);
    }

    /** @test */
    public function it_can_scope_active_employers()
    {
        Employer::factory()->create(['is_active' => true]);
        Employer::factory()->create(['is_active' => true]);
        Employer::factory()->create(['is_active' => false]);

        $activeEmployers = Employer::active()->get();

        $this->assertCount(2, $activeEmployers);
    }

    /** @test */
    public function it_casts_salary_to_decimal()
    {
        $employer = Employer::factory()->create(['basic_salary' => 2500.50]);

        $this->assertIsFloat($employer->basic_salary);
        $this->assertEquals(2500.50, $employer->basic_salary);
    }

    /** @test */
    public function it_casts_benefits_to_boolean()
    {
        $employer = Employer::factory()->create([
            'food_by_company' => 1,
            'accommodation_by_company' => 0,
            'transport_by_company' => 1,
        ]);

        $this->assertTrue($employer->food_by_company);
        $this->assertFalse($employer->accommodation_by_company);
        $this->assertTrue($employer->transport_by_company);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'permission_number',
            'visa_issuing_company',
            'country_id',
            'sector',
            'trade',
            'basic_salary',
            'salary_currency',
            'food_by_company',
            'transport_by_company',
            'accommodation_by_company',
            'other_conditions',
            'evidence_path',
            'is_active',
            'created_by',
        ];

        $employer = new Employer();

        $this->assertEquals($fillable, $employer->getFillable());
    }

    /** @test */
    public function it_soft_deletes()
    {
        $employer = Employer::factory()->create();
        $employerId = $employer->id;

        $employer->delete();

        $this->assertSoftDeleted('employers', ['id' => $employerId]);
        $this->assertNotNull(Employer::withTrashed()->find($employerId)->deleted_at);
    }

    /** @test */
    public function it_can_store_evidence_path()
    {
        $employer = Employer::factory()->create([
            'evidence_path' => 'employers/evidence/demand_letter.pdf',
        ]);

        $this->assertEquals('employers/evidence/demand_letter.pdf', $employer->evidence_path);
    }

    /** @test */
    public function it_can_store_other_conditions()
    {
        $conditions = "Overtime: 1.5x base rate\nLeave: 30 days annual\nWorking hours: 8 hours/day";

        $employer = Employer::factory()->create([
            'other_conditions' => $conditions,
        ]);

        $this->assertEquals($conditions, $employer->other_conditions);
    }
}
