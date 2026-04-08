<?php

namespace Database\Seeders;

use App\Models\ComplaintTemplate;
use Illuminate\Database\Seeder;

class ComplaintTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'                   => 'Salary Dispute',
                'category'               => 'salary',
                'description_template'   => "I am reporting a salary dispute.\n\nExpected salary: [amount]\nReceived salary: [amount]\nMonth(s) affected: [month/year]\n\nDetails: [describe the issue in detail]",
                'required_evidence_types' => ['contract', 'payslip', 'bank_statement'],
                'suggested_actions'      => ['Contact employer', 'Review contract', 'Escalate to OEP'],
                'default_priority'       => 'high',
                'suggested_sla_hours'    => 48,
            ],
            [
                'name'                   => 'Workplace Safety Issue',
                'category'               => 'facility',
                'description_template'   => "I am reporting a workplace safety concern.\n\nLocation: [location]\nDate observed: [date]\nNature of hazard: [describe]\n\nPeople at risk: [who is affected]\n\nDescription: [describe the issue in detail]",
                'required_evidence_types' => ['photo', 'witness_statement'],
                'suggested_actions'      => ['Notify safety officer', 'Document incident', 'File formal report'],
                'default_priority'       => 'urgent',
                'suggested_sla_hours'    => 24,
            ],
            [
                'name'                   => 'Document Issue',
                'category'               => 'document',
                'description_template'   => "I am experiencing an issue with my documents.\n\nDocument type: [type of document]\nIssue: [describe the problem]\nDocument reference: [number/ID]\n\nDetails: [explain what happened and what you need]",
                'required_evidence_types' => ['document_copy'],
                'suggested_actions'      => ['Gather copies', 'Contact issuing authority', 'File request'],
                'default_priority'       => 'normal',
                'suggested_sla_hours'    => 72,
            ],
            [
                'name'                   => 'Harassment Report',
                'category'               => 'conduct',
                'description_template'   => "I am reporting an incident of harassment.\n\nType of harassment: [verbal / physical / sexual / other]\nPerpetrator: [name/position if known]\nDate(s): [when it occurred]\n\nDescription: [describe what happened]\n\nWitnesses: [names of any witnesses]\n\nNote: This is a confidential report.",
                'required_evidence_types' => ['witness_statement', 'communication_record'],
                'suggested_actions'      => ['Ensure safety', 'Collect evidence', 'Notify HR', 'Consider legal action'],
                'default_priority'       => 'urgent',
                'suggested_sla_hours'    => 24,
            ],
            [
                'name'                   => 'Accommodation Complaint',
                'category'               => 'accommodation',
                'description_template'   => "I am filing a complaint about my accommodation.\n\nAddress: [accommodation address]\nIssue type: [overcrowding / poor sanitation / lack of utilities / other]\nSince when: [date]\n\nDescription: [describe the living conditions and issues]",
                'required_evidence_types' => ['photo', 'supporting_document'],
                'suggested_actions'      => ['Document conditions', 'Notify supervisor', 'Request relocation'],
                'default_priority'       => 'high',
                'suggested_sla_hours'    => 48,
            ],
            [
                'name'                   => 'Training Issue',
                'category'               => 'training',
                'description_template'   => "I am reporting an issue with my training programme.\n\nTraining centre: [name]\nCourse/Trade: [course name]\nIssue: [inadequate training / wrong course / missing certificate / other]\n\nDescription: [describe the problem and its impact]",
                'required_evidence_types' => ['supporting_document'],
                'suggested_actions'      => ['Review training records', 'Contact campus admin', 'Arrange assessment'],
                'default_priority'       => 'normal',
                'suggested_sla_hours'    => 72,
            ],
        ];

        foreach ($templates as $template) {
            ComplaintTemplate::updateOrCreate(
                ['name' => $template['name']],
                array_merge($template, ['is_active' => true])
            );
        }

        $this->command->info('Complaint templates seeded: '.count($templates).' templates');
    }
}
