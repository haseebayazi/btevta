<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Program;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\ImplementingPartner;
use App\Models\Course;
use App\Models\PaymentMethod;
use App\Models\NextOfKin;
use App\Models\Batch;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Tests\TestCase;

class RegistrationAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Candidate $screenedCandidate;
    protected Campus $campus;
    protected Program $program;
    protected Trade $trade;
    protected Oep $oep;
    protected ImplementingPartner $partner;
    protected Course $course;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create required dependencies
        $this->campus = Campus::factory()->create(['is_active' => true]);
        $this->program = Program::factory()->create(['is_active' => true]);
        $this->trade = Trade::factory()->create();
        $this->oep = Oep::factory()->create(['is_active' => true]);
        $this->partner = ImplementingPartner::factory()->create(['is_active' => true]);
        $this->course = Course::factory()->create([
            'is_active' => true,
            'duration_days' => 30,
        ]);
        $this->paymentMethod = PaymentMethod::factory()->create([
            'is_active' => true,
            'requires_bank_name' => false,
        ]);

        // Create a screened candidate
        $this->screenedCandidate = Candidate::factory()->create([
            'status' => CandidateStatus::SCREENED->value,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);
    }

    public function test_allocation_page_requires_screened_status(): void
    {
        // Create a candidate that is NOT screened
        $newCandidate = Candidate::factory()->create([
            'status' => CandidateStatus::LISTED->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('registration.allocation', $newCandidate));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_allocation_page_accessible_for_screened_candidate(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('registration.allocation', $this->screenedCandidate));

        $response->assertStatus(200);
        $response->assertViewIs('registration.allocation');
        $response->assertViewHas('candidate');
        $response->assertViewHas('campuses');
        $response->assertViewHas('programs');
        $response->assertViewHas('courses');
        $response->assertViewHas('paymentMethods');
    }

    public function test_cannot_register_unscreened_candidate(): void
    {
        $newCandidate = Candidate::factory()->create([
            'status' => CandidateStatus::LISTED->value,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('registration.store-allocation', $newCandidate), [
                'campus_id' => $this->campus->id,
                'program_id' => $this->program->id,
                'trade_id' => $this->trade->id,
                'course_id' => $this->course->id,
                'course_start_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'course_end_date' => Carbon::now()->addDays(37)->format('Y-m-d'),
                'nok_name' => 'Test NOK',
                'nok_relationship' => 'father',
                'nok_cnic' => '3520112345678',
                'nok_phone' => '03001234567',
                'nok_payment_method_id' => $this->paymentMethod->id,
                'nok_account_number' => '03001234567',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $newCandidate->refresh();
        $this->assertEquals(CandidateStatus::LISTED->value, $newCandidate->status);
    }

    public function test_validation_requires_mandatory_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('registration.store-allocation', $this->screenedCandidate), []);

        $response->assertSessionHasErrors([
            'campus_id',
            'program_id',
            'trade_id',
            'course_id',
            'course_start_date',
            'course_end_date',
            'nok_name',
            'nok_relationship',
            'nok_cnic',
            'nok_phone',
            'nok_payment_method_id',
            'nok_account_number',
        ]);
    }

    public function test_can_complete_registration_with_allocation(): void
    {
        $startDate = Carbon::now()->addDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(37)->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->post(route('registration.store-allocation', $this->screenedCandidate), [
                'campus_id' => $this->campus->id,
                'program_id' => $this->program->id,
                'trade_id' => $this->trade->id,
                'oep_id' => $this->oep->id,
                'implementing_partner_id' => $this->partner->id,
                'course_id' => $this->course->id,
                'course_start_date' => $startDate,
                'course_end_date' => $endDate,
                'nok_name' => 'Test NOK',
                'nok_relationship' => 'father',
                'nok_cnic' => '3520112345678',
                'nok_phone' => '03001234567',
                'nok_address' => '123 Test Street, Test City',
                'nok_payment_method_id' => $this->paymentMethod->id,
                'nok_account_number' => '03001234567',
            ]);

        // Check for errors in session
        if ($response->getSession()->has('error')) {
            $this->fail('Error in session: ' . $response->getSession()->get('error'));
        }

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->screenedCandidate->refresh();
        $this->assertEquals(CandidateStatus::REGISTERED->value, $this->screenedCandidate->status);
        $this->assertNotNull($this->screenedCandidate->batch_id);
        $this->assertNotNull($this->screenedCandidate->allocated_number);
        $this->assertEquals($this->program->id, $this->screenedCandidate->program_id);
    }

    public function test_auto_batch_assignment_works(): void
    {
        $startDate = Carbon::now()->addDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(37)->format('Y-m-d');

        // Ensure no batch exists initially
        $this->assertNull($this->screenedCandidate->batch_id);

        $this->actingAs($this->admin)
            ->post(route('registration.store-allocation', $this->screenedCandidate), [
                'campus_id' => $this->campus->id,
                'program_id' => $this->program->id,
                'trade_id' => $this->trade->id,
                'course_id' => $this->course->id,
                'course_start_date' => $startDate,
                'course_end_date' => $endDate,
                'nok_name' => 'Test NOK',
                'nok_relationship' => 'father',
                'nok_cnic' => '3520112345678',
                'nok_phone' => '03001234567',
                'nok_payment_method_id' => $this->paymentMethod->id,
                'nok_account_number' => '03001234567',
            ]);

        $this->screenedCandidate->refresh();

        // Batch should be assigned
        $this->assertNotNull($this->screenedCandidate->batch_id);

        // Verify batch has correct attributes
        $batch = Batch::find($this->screenedCandidate->batch_id);
        $this->assertEquals($this->campus->id, $batch->campus_id);
        $this->assertEquals($this->program->id, $batch->program_id);
        $this->assertEquals($this->trade->id, $batch->trade_id);
    }

    public function test_next_of_kin_with_financial_details_saved(): void
    {
        $startDate = Carbon::now()->addDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(37)->format('Y-m-d');

        $this->actingAs($this->admin)
            ->post(route('registration.store-allocation', $this->screenedCandidate), [
                'campus_id' => $this->campus->id,
                'program_id' => $this->program->id,
                'trade_id' => $this->trade->id,
                'course_id' => $this->course->id,
                'course_start_date' => $startDate,
                'course_end_date' => $endDate,
                'nok_name' => 'Muhammad Ali',
                'nok_relationship' => 'father',
                'nok_cnic' => '3520112345678',
                'nok_phone' => '03001234567',
                'nok_address' => '123 Test Street, Lahore',
                'nok_payment_method_id' => $this->paymentMethod->id,
                'nok_account_number' => '03009876543',
            ]);

        $nok = NextOfKin::where('candidate_id', $this->screenedCandidate->id)->first();

        $this->assertNotNull($nok);
        $this->assertEquals('Muhammad Ali', $nok->name);
        $this->assertEquals('father', $nok->relationship);
        $this->assertEquals('3520112345678', $nok->cnic);
        $this->assertEquals($this->paymentMethod->id, $nok->payment_method_id);
        $this->assertEquals('03009876543', $nok->account_number);
    }

    public function test_course_assignment_saved(): void
    {
        $startDate = Carbon::now()->addDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(37)->format('Y-m-d');

        $this->actingAs($this->admin)
            ->post(route('registration.store-allocation', $this->screenedCandidate), [
                'campus_id' => $this->campus->id,
                'program_id' => $this->program->id,
                'trade_id' => $this->trade->id,
                'course_id' => $this->course->id,
                'course_start_date' => $startDate,
                'course_end_date' => $endDate,
                'nok_name' => 'Test NOK',
                'nok_relationship' => 'father',
                'nok_cnic' => '3520112345678',
                'nok_phone' => '03001234567',
                'nok_payment_method_id' => $this->paymentMethod->id,
                'nok_account_number' => '03001234567',
            ]);

        $this->screenedCandidate->refresh();

        // Verify course is attached
        $this->assertTrue($this->screenedCandidate->courses->contains($this->course));

        // Verify pivot data
        $pivot = $this->screenedCandidate->courses->first()->pivot;
        $this->assertEquals('assigned', $pivot->status);
        // Handle both Carbon instance and string dates
        $pivotStartDate = is_string($pivot->start_date) ? $pivot->start_date : $pivot->start_date->format('Y-m-d');
        $pivotEndDate = is_string($pivot->end_date) ? $pivot->end_date : $pivot->end_date->format('Y-m-d');
        $this->assertEquals($startDate, $pivotStartDate);
        $this->assertEquals($endDate, $pivotEndDate);
    }
}
