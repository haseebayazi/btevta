# WASL Complete Implementation - Master Prompt

**Project:** BTEVTA WASL
**Task:** Implement Modules 2-10 Sequentially
**Date:** February 2026

---

## Instructions

You are implementing the complete WASL system enhancements. Execute each module **IN ORDER** without skipping any part. Each module has a detailed prompt in the `docs/` folder.

### Execution Order

```
Module 2 → Module 3 → Module 4 → Module 5 → Module 6 → Module 7 → Module 8 → Module 9 → Module 10
```

### For Each Module

1. **Read** the full prompt: `docs/MODULE_X_IMPLEMENTATION_PROMPT.md`
2. **Analyze** existing code as instructed in "Pre-Implementation Analysis"
3. **Implement** all phases in order (Database → Enums → Models → Services → Controllers → Routes → Views)
4. **Test** using the test specifications provided
5. **Verify** using the "Validation Checklist"
6. **Commit** with descriptive message before moving to next module

### Module Sequence

| Order | File | Summary |
|-------|------|---------|
| 1 | `MODULE_2_IMPLEMENTATION_PROMPT.md` | Initial Screening - consent, placement interest, country |
| 2 | `MODULE_3_IMPLEMENTATION_PROMPT.md` | Registration - auto-batch, course assignment, NOK financial |
| 3 | `MODULE_4_IMPLEMENTATION_PROMPT.md` | Training - dual status (technical/soft skills) |
| 4 | `MODULE_5_IMPLEMENTATION_PROMPT.md` | Visa - stage details, hierarchical dashboard |
| 5 | `MODULE_6_IMPLEMENTATION_PROMPT.md` | Departure - PTN, protector, briefing with video |
| 6 | `MODULE_7_IMPLEMENTATION_PROMPT.md` | Post-Departure - iqama, employment, company switches |
| 7 | `MODULE_8_IMPLEMENTATION_PROMPT.md` | Employer - permission numbers, packages |
| 8 | `MODULE_9_IMPLEMENTATION_PROMPT.md` | Success Stories & Complaints enhancements |
| 9 | `MODULE_10_INTEGRATION_PROMPT.md` | Journey dashboard, pipeline, audit, notifications |

### Critical Rules

1. **DO NOT** skip any module or phase within a module
2. **DO NOT** break existing functionality - all changes are additive/modifications
3. **DO** run `php artisan test` after each module to ensure no regressions
4. **DO** commit after completing each module
5. **DO** match UI styling to Module 1 (Tailwind CSS with gradient headers)
6. **DO** follow existing patterns (Services, Policies, FormRequests)

### After All Modules Complete

1. Run full test suite: `php artisan test`
2. Update `CLAUDE.md` version to 2.0.0
3. Update `README.md` with all new features
4. Create final commit: "Complete WASL v2.0 implementation (Modules 2-10)"

### Start Command

Begin with Module 2:
```
Read docs/MODULE_2_IMPLEMENTATION_PROMPT.md and implement Initial Screening
```

---

**Remember:** Quality over speed. Each module must be fully functional before proceeding.
