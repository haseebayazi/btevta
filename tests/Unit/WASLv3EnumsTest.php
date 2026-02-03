<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Enums\PlacementInterest;
use App\Enums\TrainingType;
use App\Enums\TrainingProgress;
use App\Enums\AssessmentType;
use App\Enums\PTNStatus;
use App\Enums\ProtectorStatus;
use App\Enums\FlightType;
use App\Enums\DepartureStatus;
use App\Enums\VisaApplicationStatus;
use App\Enums\VisaIssuedStatus;
use App\Enums\VisaStageResult;
use App\Enums\EvidenceType;
use App\Enums\ScreeningStatus;
use App\Enums\CandidateStatus;

class WASLv3EnumsTest extends TestCase
{
    #[Test]
    public function placement_interest_enum_has_correct_values()
    {
        $cases = PlacementInterest::cases();

        $this->assertCount(2, $cases);
        $this->assertEquals('local', PlacementInterest::LOCAL->value);
        $this->assertEquals('international', PlacementInterest::INTERNATIONAL->value);
    }

    #[Test]
    public function training_type_enum_has_correct_values()
    {
        $cases = TrainingType::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('technical', TrainingType::TECHNICAL->value);
        $this->assertEquals('soft_skills', TrainingType::SOFT_SKILLS->value);
        $this->assertEquals('both', TrainingType::BOTH->value);
    }

    #[Test]
    public function training_progress_enum_has_correct_values()
    {
        $cases = TrainingProgress::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('not_started', TrainingProgress::NOT_STARTED->value);
        $this->assertEquals('in_progress', TrainingProgress::IN_PROGRESS->value);
        $this->assertEquals('completed', TrainingProgress::COMPLETED->value);
    }

    #[Test]
    public function assessment_type_enum_has_correct_values()
    {
        $cases = AssessmentType::cases();

        $this->assertCount(2, $cases);
        $this->assertEquals('interim', AssessmentType::INTERIM->value);
        $this->assertEquals('final', AssessmentType::FINAL->value);
    }

    #[Test]
    public function ptn_status_enum_has_correct_values()
    {
        $cases = PTNStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertEquals('not_applied', PTNStatus::NOT_APPLIED->value);
        $this->assertEquals('issued', PTNStatus::ISSUED->value);
        $this->assertEquals('done', PTNStatus::DONE->value);
        $this->assertEquals('pending', PTNStatus::PENDING->value);
        $this->assertEquals('not_issued', PTNStatus::NOT_ISSUED->value);
        $this->assertEquals('refused', PTNStatus::REFUSED->value);
    }

    #[Test]
    public function protector_status_enum_has_correct_values()
    {
        $cases = ProtectorStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertEquals('not_applied', ProtectorStatus::NOT_APPLIED->value);
        $this->assertEquals('applied', ProtectorStatus::APPLIED->value);
        $this->assertEquals('done', ProtectorStatus::DONE->value);
        $this->assertEquals('pending', ProtectorStatus::PENDING->value);
        $this->assertEquals('not_issued', ProtectorStatus::NOT_ISSUED->value);
        $this->assertEquals('refused', ProtectorStatus::REFUSED->value);
    }

    #[Test]
    public function flight_type_enum_has_correct_values()
    {
        $cases = FlightType::cases();

        $this->assertCount(2, $cases);
        $this->assertEquals('direct', FlightType::DIRECT->value);
        $this->assertEquals('connected', FlightType::CONNECTED->value);
    }

    #[Test]
    public function departure_status_enum_has_correct_values()
    {
        $cases = DepartureStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('processing', DepartureStatus::PROCESSING->value);
        $this->assertEquals('ready_to_depart', DepartureStatus::READY_TO_DEPART->value);
        $this->assertEquals('departed', DepartureStatus::DEPARTED->value);
    }

    #[Test]
    public function visa_application_status_enum_has_correct_values()
    {
        $cases = VisaApplicationStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('not_applied', VisaApplicationStatus::NOT_APPLIED->value);
        $this->assertEquals('applied', VisaApplicationStatus::APPLIED->value);
        $this->assertEquals('refused', VisaApplicationStatus::REFUSED->value);
    }

    #[Test]
    public function visa_issued_status_enum_has_correct_values()
    {
        $cases = VisaIssuedStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('pending', VisaIssuedStatus::PENDING->value);
        $this->assertEquals('confirmed', VisaIssuedStatus::CONFIRMED->value);
        $this->assertEquals('refused', VisaIssuedStatus::REFUSED->value);
    }

    #[Test]
    public function visa_stage_result_enum_has_correct_values()
    {
        $cases = VisaStageResult::cases();

        $this->assertCount(4, $cases);
        $this->assertEquals('pending', VisaStageResult::PENDING->value);
        $this->assertEquals('pass', VisaStageResult::PASS->value);
        $this->assertEquals('fail', VisaStageResult::FAIL->value);
        $this->assertEquals('refused', VisaStageResult::REFUSED->value);
    }

    #[Test]
    public function evidence_type_enum_has_correct_values()
    {
        $cases = EvidenceType::cases();

        $this->assertCount(6, $cases);
        $this->assertEquals('audio', EvidenceType::AUDIO->value);
        $this->assertEquals('video', EvidenceType::VIDEO->value);
        $this->assertEquals('written', EvidenceType::WRITTEN->value);
        $this->assertEquals('screenshot', EvidenceType::SCREENSHOT->value);
        $this->assertEquals('document', EvidenceType::DOCUMENT->value);
        $this->assertEquals('other', EvidenceType::OTHER->value);
    }

    #[Test]
    public function updated_screening_status_enum_has_correct_values()
    {
        $cases = ScreeningStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('pending', ScreeningStatus::PENDING->value);
        $this->assertEquals('screened', ScreeningStatus::SCREENED->value);
        $this->assertEquals('deferred', ScreeningStatus::DEFERRED->value);
    }

    #[Test]
    public function candidate_status_enum_has_17_statuses()
    {
        $cases = CandidateStatus::cases();

        // 15 active + 3 terminal = 18 total (includes NEW and COMPLETED)
        $this->assertCount(18, $cases);
    }

    #[Test]
    public function candidate_status_has_active_statuses()
    {
        $activeStatuses = [
            'listed',
            'pre_departure_docs',
            'screening',
            'screened',
            'registered',
            'training',
            'training_completed',
            'visa_process',
            'visa_approved',
            'departure_processing',
            'ready_to_depart',
            'departed',
            'post_departure',
        ];

        foreach ($activeStatuses as $status) {
            $enum = CandidateStatus::tryFrom($status);
            $this->assertNotNull($enum, "Active status '$status' should exist");
        }
    }

    #[Test]
    public function candidate_status_has_terminal_statuses()
    {
        $terminalStatuses = [
            'deferred',
            'rejected',
            'withdrawn',
        ];

        foreach ($terminalStatuses as $status) {
            $enum = CandidateStatus::tryFrom($status);
            $this->assertNotNull($enum, "Terminal status '$status' should exist");
        }
    }

    #[Test]
    public function enums_can_be_used_in_match_expressions()
    {
        $status = PTNStatus::ISSUED;

        $result = match($status) {
            PTNStatus::NOT_APPLIED => 'Not Applied',
            PTNStatus::ISSUED => 'Issued',
            PTNStatus::DONE => 'Done',
            PTNStatus::PENDING => 'Pending',
            PTNStatus::NOT_ISSUED => 'Not Issued',
            PTNStatus::REFUSED => 'Refused',
        };

        $this->assertEquals('Issued', $result);
    }

    #[Test]
    public function enums_can_be_compared_directly()
    {
        $status1 = TrainingProgress::IN_PROGRESS;
        $status2 = TrainingProgress::IN_PROGRESS;
        $status3 = TrainingProgress::COMPLETED;

        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    #[Test]
    public function enums_provide_all_cases_method()
    {
        $assessmentTypes = AssessmentType::cases();

        $this->assertIsArray($assessmentTypes);
        $this->assertContainsOnlyInstancesOf(AssessmentType::class, $assessmentTypes);
    }

    #[Test]
    public function enums_can_be_serialized_to_value()
    {
        $flightType = FlightType::DIRECT;

        $this->assertEquals('direct', $flightType->value);
        $this->assertIsString($flightType->value);
    }

    #[Test]
    public function enums_can_be_created_from_value()
    {
        $status = DepartureStatus::from('ready_to_depart');

        $this->assertInstanceOf(DepartureStatus::class, $status);
        $this->assertEquals(DepartureStatus::READY_TO_DEPART, $status);
    }

    #[Test]
    public function enums_try_from_returns_null_for_invalid_value()
    {
        $status = EvidenceType::tryFrom('invalid_type');

        $this->assertNull($status);
    }

    #[Test]
    public function all_enums_are_backed_by_strings()
    {
        $enums = [
            PlacementInterest::class,
            TrainingType::class,
            TrainingProgress::class,
            AssessmentType::class,
            PTNStatus::class,
            ProtectorStatus::class,
            FlightType::class,
            DepartureStatus::class,
            VisaApplicationStatus::class,
            VisaIssuedStatus::class,
            VisaStageResult::class,
            EvidenceType::class,
            ScreeningStatus::class,
            CandidateStatus::class,
        ];

        foreach ($enums as $enumClass) {
            $firstCase = $enumClass::cases()[0];
            $this->assertIsString($firstCase->value, "$enumClass should be backed by strings");
        }
    }
}
