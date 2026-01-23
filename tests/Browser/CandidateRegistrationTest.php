<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Test;

use App\Models\User;
use App\Models\Trade;
use App\Models\Campus;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for candidate registration flow.
 */
class CandidateRegistrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected Trade $trade;
    protected Campus $campus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@theleap.org',
            'password' => bcrypt('password'),
        ]);
        $this->trade = Trade::factory()->create();
        $this->campus = Campus::factory()->create();
    }

    #[Test]
    public function it_can_login_as_admin()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'admin@theleap.org')
                    ->type('password', 'password')
                    ->press('Login')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard');
        });
    }

    #[Test]
    public function it_can_navigate_to_candidates_list()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/dashboard')
                    ->clickLink('Candidates')
                    ->assertPathIs('/candidates')
                    ->assertSee('Candidates');
        });
    }

    #[Test]
    public function it_can_create_new_candidate()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/candidates')
                    ->click('@create-candidate')
                    ->assertPathIs('/candidates/create')
                    ->type('name', 'Muhammad Ali Khan')
                    ->type('cnic', '3520112345671')
                    ->type('father_name', 'Ahmed Khan')
                    ->type('date_of_birth', '1995-05-15')
                    ->select('gender', 'male')
                    ->type('phone', '03001234567')
                    ->type('email', 'ali.khan@example.com')
                    ->type('address', '123 Main Street, Rawalpindi')
                    ->type('district', 'Rawalpindi')
                    ->select('trade_id', $this->trade->id)
                    ->select('campus_id', $this->campus->id)
                    ->press('Create Candidate')
                    ->assertSee('Candidate created successfully')
                    ->assertPathIs('/candidates');
        });
    }

    #[Test]
    public function it_shows_validation_errors_for_invalid_cnic()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/candidates/create')
                    ->type('name', 'Test Candidate')
                    ->type('cnic', '123') // Invalid CNIC
                    ->type('father_name', 'Father Name')
                    ->type('date_of_birth', '1995-05-15')
                    ->select('gender', 'male')
                    ->type('phone', '03001234567')
                    ->type('address', 'Test Address')
                    ->type('district', 'Rawalpindi')
                    ->select('trade_id', $this->trade->id)
                    ->press('Create Candidate')
                    ->assertSee('CNIC must be 13 digits');
        });
    }

    #[Test]
    public function it_can_search_candidates()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/candidates')
                    ->type('@search-input', 'Muhammad')
                    ->press('@search-button')
                    ->waitForText('Search results')
                    ->assertSee('Muhammad');
        });
    }

    #[Test]
    public function it_can_view_candidate_details()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/candidates')
                    ->click('@view-candidate-1')
                    ->assertPathBeginsWith('/candidates/')
                    ->assertSee('Candidate Details')
                    ->assertSee('Status')
                    ->assertSee('CNIC');
        });
    }

    #[Test]
    public function it_can_edit_candidate()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/candidates/1/edit')
                    ->clear('name')
                    ->type('name', 'Updated Name')
                    ->press('Update Candidate')
                    ->assertSee('Candidate updated successfully')
                    ->assertSee('Updated Name');
        });
    }

    #[Test]
    public function it_shows_duplicate_warning_for_existing_phone()
    {
        $this->browse(function (Browser $browser) {
            // First create a candidate
            $browser->loginAs($this->admin)
                    ->visit('/candidates/create')
                    ->type('phone', '03001234567')
                    ->pause(500) // Wait for AJAX check
                    ->assertSee('potential duplicate');
        });
    }
}
