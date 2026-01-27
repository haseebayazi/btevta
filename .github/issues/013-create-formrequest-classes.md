Title: Create FormRequest classes for flagged forms
Labels: chore, P2, backend, estimate:3-6h
Assignees: @(unassigned)

Description:
Replace inline controller validations with FormRequest classes for forms flagged as critical in the audit and improve validation reuse and testing.

Checklist:
- [ ] Identify controllers with large/complex inline validations (based on audit)
- [ ] Create corresponding `FormRequest` classes with clear rules and messages
- [ ] Replace controller validation calls with `FormRequest` type-hinting
- [ ] Add validation unit tests for edge cases

Acceptance Criteria:
- No controller uses the old inline validation for the converted forms
- Tests validate both positive and negative cases

Files: `app/Http/Requests/*`, updated controllers