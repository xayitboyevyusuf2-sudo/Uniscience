# Marshrutlar (routes)

Quyidagi jadval `php artisan route:list --except-vendor` natijasidan olingan. JSON endpointlar: `/api/jurnallar/qidiruv` (jurnal qidiruvi, auth + throttle).

| Yo‘l | Metod | Nomi | Controller | Vazifa |
|------|-------|------|------------|--------|
| GET|HEAD / .. home ΓÇ║ PageController@home |
| GET|HEAD admin .. AdminController@index |
| GET|HEAD admin/audit .. admin.audit ΓÇ║ AdminController@audit |
| POST admin/foydalanuvchi/{user} .. AdminController@role |
| GET|HEAD admin/foydalanuvchi/{user}/tahrir .. ProfileController@editUser |
| POST admin/foydalanuvchi/{user}/tahrir .. ProfileController@updateUser |
| POST admin/foydalanuvchi/{user}/tasdiq admin.users.approval ΓÇ║ AdminCoΓÇª |
| GET|HEAD admin/foydalanuvchilar admin.users.index ΓÇ║ AdminUserController@iΓÇª |
| POST admin/foydalanuvchilar/xodim admin.users.staff ΓÇ║ AdminUserControΓÇª |
| POST admin/import .. AdminController@import |
| GET|HEAD admin/jurnal-arizalari admin.journal-requests.index ΓÇ║ AdminJournΓÇª |
| POST admin/jurnal-arizalari/{journalRequest}/hal admin.journal-requesΓÇª |
| POST admin/jurnal-arizalari/{journalRequest}/rad admin.journal-requesΓÇª |
| GET|HEAD admin/jurnallar admin.journals.index ΓÇ║ AdminJournalController@inΓÇª |
| POST admin/jurnallar admin.journals.store ΓÇ║ AdminJournalController@stΓÇª |
| POST admin/jurnallar/import-json admin.journals.import-json ΓÇ║ AdminJoΓÇª |
| GET|HEAD admin/jurnallar/yangi admin.journals.create ΓÇ║ AdminJournalControΓÇª |
| POST admin/jurnallar/{journal} admin.journals.update ΓÇ║ AdminJournalCoΓÇª |
| POST admin/jurnallar/{journal}/chiqarish admin.journals.delist ΓÇ║ AdmiΓÇª |
| GET|HEAD admin/jurnallar/{journal}/tahrir admin.journals.edit ΓÇ║ AdminJourΓÇª |
| GET|HEAD admin/ochirish-sorovlari admin.delete-requests ΓÇ║ PrivacyControllΓÇª |
| POST admin/ochirish-sorovlari/{deletionRequest} admin.delete-requestsΓÇª |
| POST admin/qaror/{article} .. AdminController@decide |
| POST admin/sozlamalar .. AdminController@settings |
| POST admin/sozlamalar/koeffitsientlar admin.scoring ΓÇ║ AdminControllerΓÇª |
| GET|HEAD admin/videolar admin.videos.index ΓÇ║ AdminContentController@videos |
| POST admin/videolar admin.videos.store ΓÇ║ AdminContentController@storeΓÇª |
| POST admin/videolar/{video} admin.videos.update ΓÇ║ AdminContentControlΓÇª |
| DELETE admin/videolar/{video} admin.videos.destroy ΓÇ║ AdminContentControΓÇª |
| GET|HEAD admin/yangiliklar .. admin.news.index ΓÇ║ AdminNewsController@index |
| POST admin/yangiliklar .. admin.news.store ΓÇ║ AdminNewsController@store |
| GET|HEAD admin/yangiliklar/yangi admin.news.create ΓÇ║ AdminNewsController@ΓÇª |
| POST admin/yangiliklar/{news} admin.news.update ΓÇ║ AdminNewsControllerΓÇª |
| DELETE admin/yangiliklar/{news} admin.news.destroy ΓÇ║ AdminNewsControlleΓÇª |
| GET|HEAD admin/yangiliklar/{news}/tahrir admin.news.edit ΓÇ║ AdminNewsContrΓÇª |
| GET|HEAD admin/yoriqnoma admin.guides.index ΓÇ║ AdminContentController@guidΓÇª |
| POST admin/yoriqnoma admin.guides.store ΓÇ║ AdminContentController@storΓÇª |
| POST admin/yoriqnoma/{guide} admin.guides.update ΓÇ║ AdminContentControΓÇª |
| DELETE admin/yoriqnoma/{guide} admin.guides.destroy ΓÇ║ AdminContentContrΓÇª |
| GET|HEAD api/jurnallar/qidiruv . journals.search ΓÇ║ JournalSearchController |
| GET|HEAD baza .. PageController@base |
| GET|HEAD bildirishnomalar notifications.index ΓÇ║ NotificationController@inΓÇª |
| POST bildirishnomalar/hammasi-oqildi notifications.read-all ΓÇ║ NotificΓÇª |
| POST bildirishnomalar/{id}/oqildi notifications.read ΓÇ║ NotificationCoΓÇª |
| POST chiqish .. AuthController@logout |
| GET|HEAD email/tasdiqlash verification.notice ΓÇ║ EmailVerificationControllΓÇª |
| POST email/tasdiqlash verification.send ΓÇ║ EmailVerificationControllerΓÇª |
| GET|HEAD email/tasdiqlash/{id}/{hash} verification.verify ΓÇ║ EmailVerificaΓÇª |
| GET|HEAD foto/{user} .. ProfileController@photo |
| GET|HEAD jurnallar .. journals.index ΓÇ║ PageController@journals |
| GET|HEAD kirish .. login ΓÇ║ AuthController@showLogin |
| POST kirish .. AuthController@login |
| POST malumotnoma .. PageController@certify |
| GET|HEAD malumotnoma/{token} .. PageController@verify |
| GET|HEAD maqola/{article} .. ArticleController@show |
| GET|HEAD maqola/{article}/pdf .. articles.pdf ΓÇ║ ArticleController@pdf |
| GET|HEAD matching .. matching.index ΓÇ║ MatchingController@index |
| POST matching/slot/{slot}/sorov matching.request ΓÇ║ MatchingControllerΓÇª |
| GET|HEAD matching/slotlarim .. matching.slots ΓÇ║ MatchingController@mySlots |
| POST matching/slotlarim matching.slots.store ΓÇ║ MatchingController@stoΓÇª |
| POST matching/slotlarim/{slot} matching.slots.update ΓÇ║ MatchingControΓÇª |
| DELETE matching/slotlarim/{slot} matching.slots.destroy ΓÇ║ MatchingContrΓÇª |
| POST matching/sorov/{mentorRequest}/bekor matching.cancel ΓÇ║ MatchingCΓÇª |
| GET|HEAD matching/sorovlar matching.requests ΓÇ║ MatchingController@requestΓÇª |
| POST matching/sorovlar/{mentorRequest}/javob matching.respond ΓÇ║ MatchΓÇª |
| GET|HEAD maxfiylik .. privacy ΓÇ║ PrivacyController@show |
| GET|HEAD moderator/maqola/{article} moderator.articles.show ΓÇ║ ModeratorCoΓÇª |
| POST moderator/maqola/{article}/sertifikat moderator.certificates.decΓÇª |
| GET|HEAD moderator/maqola/{article}/sertifikat/pdf moderator.certificatesΓÇª |
| GET|HEAD moderator/navbat .. moderator.queue ΓÇ║ ModeratorController@queue |
| GET|HEAD parolni-tiklash .. PasswordController@forgot |
| POST parolni-tiklash .. PasswordController@send |
| POST parolni-tiklash/yangi .. PasswordController@reset |
| GET|HEAD parolni-tiklash/{token} .. PasswordController@showReset |
| GET|HEAD portfel .. ArticleController@portfolio |
| GET|HEAD profil .. ProfileController@edit |
| POST profil .. ProfileController@update |
| POST profil/ochirish-sorovi privacy.request-deletion ΓÇ║ PrivacyControlΓÇª |
| GET|HEAD rahbariyat .. leadership.index ΓÇ║ LeadershipController@index |
| GET|HEAD rahbariyat/eksport.csv leadership.export.csv ΓÇ║ LeadershipControlΓÇª |
| GET|HEAD rahbariyat/eksport.pdf leadership.export.pdf ΓÇ║ LeadershipControlΓÇª |
| GET|HEAD reyting .. RatingController@index |
| GET|HEAD reyting/eksport.csv ratings.export.csv ΓÇ║ RatingController@exportΓÇª |
| GET|HEAD reyting/eksport.pdf ratings.export.pdf ΓÇ║ RatingController@exportΓÇª |
| GET|HEAD reyting/mening .. ratings.my ΓÇ║ RatingController@my |
| GET|HEAD royhat .. routes/web.php:29 |
| GET|HEAD royxat .. AuthController@showRegister |
| POST royxat .. AuthController@register |
| GET|HEAD talaba/{user} .. ProfileController@show |
| GET|HEAD tasdiq-kutilmoqda .. registration.pending |
| GET|HEAD vazirlik .. ministry.placeholder ΓÇ║ routes/web.php:127 |
| GET|HEAD yangiliklar .. news.index ΓÇ║ NewsController@index |
| GET|HEAD yangiliklar/{news}/ilova news.attachment ΓÇ║ NewsController@attachΓÇª |
| GET|HEAD yoriqnoma .. guides.index ΓÇ║ GuideController@index |
| GET|HEAD yoriqnoma/hujjat/{guide} guides.show ΓÇ║ GuideController@showGuide |
| GET|HEAD yoriqnoma/hujjat/{guide}/yuklab-olish guides.download ΓÇ║ GuideConΓÇª |
| GET|HEAD yoriqnoma/video/{video} . videos.show ΓÇ║ GuideController@showVideo |
| GET|HEAD yoriqnoma/video/{video}/oqim videos.stream ΓÇ║ GuideController@strΓÇª |
| GET|HEAD yoriqnoma/video/{video}/yuklab-olish videos.download ΓÇ║ GuideContΓÇª |
| GET|HEAD yuklash .. ArticleController@create |
| POST yuklash .. ArticleController@store |
| Showing [101] routes |
