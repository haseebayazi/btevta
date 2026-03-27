<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Enums\ContractStatus;
use App\Enums\DepartureStatus;
use App\Enums\EmploymentStatus;
use App\Enums\IqamaStatus;
use App\Enums\SwitchStatus;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\CompanySwitchLog;
use App\Models\Departure;
use App\Models\EmploymentHistory;
use App\Models\PostDepartureDetail;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostDepartureControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Candidate $candidate;
    protected Departure $departure;
    protected PostDepartureDetail $detail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        $this->candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'status' => CandidateStatus::DEPARTED->value,
        ]);

        $this->departure = Departure::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_status' => DepartureStatus::DEPARTED->value,
        ]);

        $this->detail = PostDepartureDetail::create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------------

    #[Test]
    public function dashboard_loads_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('post-departure.dashboard'));

        $response->assertOk()
            ->assertViewIs('post-departure.dashboard')
            ->assertViewHas('dashboard');
    }

    // -----------------------------------------------------------------------
    // Show
    // -----------------------------------------------------------------------

    #[Test]
    public function show_displays_candidate_post_departure_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('post-departure.show', $this->candidate));

        $response->assertOk()
            ->assertViewIs('post-departure.show')
            ->assertViewHas('detail')
            ->assertViewHas('checklist')
            ->assertViewHas('employmentHistory');
    }

    // -----------------------------------------------------------------------
    // Iqama
    // -----------------------------------------------------------------------

    #[Test]
    public function can_update_iqama_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.update-iqama', $this->detail), [
                'iqama_number' => '1234567890',
                'iqama_issue_date' => '2026-01-01',
                'iqama_expiry_date' => '2027-01-01',
                'iqama_status' => 'issued',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->detail->refresh();
        $this->assertEquals('1234567890', $this->detail->iqama_number);
        $this->assertEquals(IqamaStatus::ISSUED, $this->detail->iqama_status);
    }

    #[Test]
    public function iqama_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.update-iqama', $this->detail), []);

        $response->assertSessionHasErrors(['iqama_number', 'iqama_issue_date', 'iqama_expiry_date', 'iqama_status']);
    }

    // -----------------------------------------------------------------------
    // Foreign Contact
    // -----------------------------------------------------------------------

    #[Test]
    public function can_update_foreign_contact(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.update-contact', $this->detail), [
                'mobile_number' => '+966501234567',
                'carrier' => 'STC',
                'address' => 'Riyadh, Saudi Arabia',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->detail->refresh();
        $this->assertEquals('+966501234567', $this->detail->foreign_mobile_number);
        $this->assertEquals('STC', $this->detail->foreign_mobile_carrier);
    }

    // -----------------------------------------------------------------------
    // Foreign Bank
    // -----------------------------------------------------------------------

    #[Test]
    public function can_update_foreign_bank_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.update-bank', $this->detail), [
                'bank_name' => 'Al Rajhi Bank',
                'account_number' => '12345678',
                'iban' => 'SA0380000000608010167519',
                'swift' => 'RJHISARI',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->detail->refresh();
        $this->assertEquals('Al Rajhi Bank', $this->detail->foreign_bank_name);
        $this->assertEquals('12345678', $this->detail->foreign_bank_account);
    }

    // -----------------------------------------------------------------------
    // Tracking App
    // -----------------------------------------------------------------------

    #[Test]
    public function can_register_tracking_app(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.register-tracking', $this->detail), [
                'app_name' => 'Absher',
                'app_id' => 'ABS123456',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->detail->refresh();
        $this->assertEquals('Absher', $this->detail->tracking_app_name);
        $this->assertTrue($this->detail->tracking_app_registered);
    }

    // -----------------------------------------------------------------------
    // WPS
    // -----------------------------------------------------------------------

    #[Test]
    public function can_register_wps(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.register-wps', $this->detail), []);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->detail->refresh();
        $this->assertTrue($this->detail->wps_registered);
        $this->assertNotNull($this->detail->wps_registration_date);
    }

    // -----------------------------------------------------------------------
    // Employment
    // -----------------------------------------------------------------------

    #[Test]
    public function can_add_initial_employment(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.add-employment', $this->detail), [
                'company_name' => 'Saudi Aramco',
                'position_title' => 'Technician',
                'work_location' => 'Dammam',
                'base_salary' => 3000.00,
                'currency' => 'SAR',
                'commencement_date' => '2026-02-01',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employment_histories', [
            'candidate_id' => $this->candidate->id,
            'company_name' => 'Saudi Aramco',
            'sequence' => 1,
            'status' => 'current',
        ]);
    }

    // -----------------------------------------------------------------------
    // Contract
    // -----------------------------------------------------------------------

    #[Test]
    public function can_update_contract(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.update-contract', $this->detail), [
                'contract_number' => 'QWA-2026-001',
                'start_date' => '2026-02-01',
                'end_date' => '2028-02-01',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->detail->refresh();
        $this->assertEquals('QWA-2026-001', $this->detail->contract_number);
        $this->assertEquals(ContractStatus::ACTIVE, $this->detail->contract_status);
    }

    // -----------------------------------------------------------------------
    // Company Switch
    // -----------------------------------------------------------------------

    #[Test]
    public function can_initiate_company_switch_with_current_employment(): void
    {
        // Create initial employment
        $employment = EmploymentHistory::create([
            'candidate_id' => $this->candidate->id,
            'post_departure_detail_id' => $this->detail->id,
            'departure_id' => $this->departure->id,
            'company_name' => 'Original Company',
            'commencement_date' => '2026-01-01',
            'base_salary' => 2500,
            'salary_currency' => 'SAR',
            'status' => EmploymentStatus::CURRENT,
            'sequence' => 1,
            'switch_number' => 0,
            'switch_date' => '2026-01-01',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.initiate-switch', $this->detail), [
                'company_name' => 'New Company LLC',
                'reason' => 'Better opportunity',
                'base_salary' => 3500,
                'commencement_date' => '2026-06-01',
                'release_letter' => \Illuminate\Http\UploadedFile::fake()->create('release.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_switch_logs', [
            'candidate_id' => $this->candidate->id,
            'switch_number' => 1,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function cannot_initiate_switch_without_current_employment(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('post-departure.initiate-switch', $this->detail), [
                'company_name' => 'New Company LLC',
                'reason' => 'Better opportunity',
                'base_salary' => 3500,
                'commencement_date' => '2026-06-01',
                'release_letter' => \Illuminate\Http\UploadedFile::fake()->create('release.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // -----------------------------------------------------------------------
    // Compliance
    // -----------------------------------------------------------------------

    #[Test]
    public function compliance_checklist_returns_correct_structure(): void
    {
        $checklist = $this->detail->getComplianceChecklist();

        $this->assertArrayHasKey('iqama', $checklist);
        $this->assertArrayHasKey('tracking_app', $checklist);
        $this->assertArrayHasKey('wps', $checklist);
        $this->assertArrayHasKey('contract', $checklist);
        $this->assertArrayHasKey('bank', $checklist);
        $this->assertArrayHasKey('contact', $checklist);

        foreach ($checklist as $item) {
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('complete', $item);
        }
    }

    #[Test]
    public function compliance_checklist_shows_items_as_incomplete_initially(): void
    {
        $checklist = $this->detail->getComplianceChecklist();
        $completed = collect($checklist)->filter(fn($item) => $item['complete'])->count();

        $this->assertEquals(0, $completed);
    }
}
