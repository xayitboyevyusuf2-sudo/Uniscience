# Yakuniy qabul testlari (TS-01 … TS-22)

Oxirgi `php artisan test` natijasi: **164 ta test, 892 ta assertion, hammasi Passed** (2026-10-06).

| TS | Test klassi::metod | Natija |
|----|--------------------|--------|
| TS-01 | AcceptanceTest::test_ts01_registration_consent_and_email_verification_link | Passed |
| TS-02 | VerificationTest::test_ts02_listed_in_range_is_approved | Passed |
| TS-03 | AcceptanceTest::test_ts03_article_upload_form_has_multilingual_sections_and_pdf | Passed |
| TS-04 | VerificationTest::test_ts04_delisted_later_still_approved | Passed |
| TS-05 | VerificationTest::test_ts05_listed_after_publication_is_rejected | Passed |
| TS-06 | VerificationTest::test_ts06_name_mismatch_goes_to_manual + ArticleSubmissionTest::test_ts06_multilingual_submission_creates_author_rows_and_pending_database_notification | Passed |
| TS-07 | AcceptanceTest::test_ts07_public_base_lists_approved_articles_with_filters | Passed |
| TS-08 | VerificationTest::test_ts08_score_is_three | Passed |
| TS-09 | AcceptanceTest::test_ts09_moderator_decision_updates_status_and_notifies | Passed |
| TS-10 | AcceptanceTest::test_ts10_certificate_token_verification_page | Passed |
| TS-11 | AcceptanceTest::test_ts11_profile_shows_score_articles_and_ranks | Passed |
| TS-12 | AcceptanceTest::test_ts12_rating_tabs_scopes_and_neighborhood | Passed |
| TS-13 | RatingsTest::test_ts13_score_and_rating_item_are_cached_from_scorer | Passed |
| TS-14 | RatingsTest::test_ts14_seventh_article_is_not_counted_and_reason_shown | Passed |
| TS-15 | RatingsTest::test_ts15_ranks_by_scope_category_separation_and_ties | Passed |
| TS-16 | GuidesVideosTest::test_ts16_student_reads_and_downloads_but_cannot_manage | Passed |
| TS-17 | NewsTest::test_ts17_type_filter_active_scope_and_archive_command | Passed |
| TS-18 | MentoringTest::test_ts18_slot_on_mentor_profile_and_student_sends_request | Passed |
| TS-19 | MentoringTest::test_ts19_accept_marks_scheduled_and_notifies_student | Passed |
| TS-20 | LeadershipDashboardTest::test_ts20_dashboard_counts_match_database | Passed |
| TS-21 | AcceptanceTest::test_ts21_admin_panel_sidebar_sections | Passed |
| TS-22 | AcceptanceTest::test_ts22_full_manual_flow | Passed |

## Qo‘lda ssenariy
1. **Ro‘yxat** — `/royxat` (consent belgisi, `/maxfiylik` havolasi)
2. **Email tasdiq** — link → profil ochiladi
3. **Maqola yuklash** — `/yuklash` (ko‘p tilli, PDF, mualliflar)
4. **Moderator** — `/moderator/navbat` yoki `/admin` → qaror
5. **Reyting** — `/reyting`, `/reyting/mening`
6. **Ma’lumotnoma (QR)** — `/malumotnoma` → `/malumotnoma/{token}`
7. **Rahbariyat eksporti** — `/rahbariyat/eksport.csv` / `.pdf`
