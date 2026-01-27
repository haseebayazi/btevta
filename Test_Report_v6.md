
# WASL Test Report v6 — Problematic Tests Only

This markdown lists only failed, warning, risky, or no-assertion tests (with details) from Test_Report_v6.txt, grouped by test class.

---

## Legend
- ⨯ = Failed
- ! = Warning/No assertion

---

## Problematic Tests (Excerpt)

### AllocationServiceTest
- ⨯ it logs allocation activity
- ⨯ it returns allocation summary
- ⨯ it can bulk allocate multiple candidates
- ⨯ it handles bulk allocation failures gracefully

### AuthorizationEdgeCasesTest
- ⨯ campus admin cannot access other campus batches
- ⨯ campus admin cannot view other campus complaints
- ⨯ instructor cannot access administrative functions
- ⨯ oep cannot access other oep remittances
- ⨯ user cannot delete other users tokens
- ⨯ super admin can access all campuses
- ⨯ only super admin can access system settings

### AutoBatchServiceTest
- ⨯ it creates new batch when none exists
- ⨯ it assigns to existing batch when available
- ⨯ it creates new batch when existing is full
- ⨯ it respects configured batch size
- ⨯ it updates candidate with batch and allocated number
- ⨯ it handles multiple candidates in sequence
- ⨯ it generates unique allocated numbers within batch
- ⨯ it groups by campus program trade combination

---

*Note: Only the first three test classes are shown here for brevity. The full report includes all problematic tests as found in Test_Report_v6.txt.*

---

*Generated on 2026-01-26 from Test_Report_v6.txt*
