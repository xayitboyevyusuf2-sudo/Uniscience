# Administrator qo‘llanmasi

## Jurnal import
- **CSV**: `/admin` → “OAK ro‘yxatini import” — ustunlar: `issn,name,field,tier,listed_from,listed_to,country,publisher`. Eski yozuvlar o‘chirilmaydi.
- **JSON**: `/admin/jurnallar` → “JSON import” — `[{issn,name,field,tier,listed_from,listed_to,country,publisher,source}]`. Hisobot: N ta yozuv, M ta o‘tkazib yuborildi (sabablari bilan).

## Tasdiqlash
- **Maqolalar**: `/admin` navbati yoki `/moderator/navbat` — OAK tekshiruvi, PDF sarlavha, muallif pozitsiyasi, konferensiya sertifikati, qaror + izoh.
- **Foydalanuvchilar**: `/admin/foydalanuvchilar` — rol berish, bloklash, tasdiqlash/rad, tahrir, xodim hisobi yaratish.

## Navbat
- `/moderator/navbat` — faqat o‘z fakultetiga biriktirilgan moderatorlar.

## Koeffitsientlar (FR-53)
- `/admin` → “Ball koeffitsientlari” — `tier.A…X`, `position.*`, `date.*`, `field.*`, `yearly_limit`, `diversity_factor`, `monthly_flag_threshold`. Bo‘sh qoldirilsa standart qiymat saqlanadi. Saqlangach barcha reytinglar qayta hisoblanadi. Har o‘zgarish `setting_changes` + `audit_logs` da yoziladi.

## Zaxira
- `php artisan backup:run` — DB + `storage/app` zip. `BACKUP_DISK` env orqali alohida disk.
- `php artisan backup:clean` — eski nusxalarni tozalaydi. `php artisan backup:monitor` — disk sog‘ligini tekshiradi.
- Tiklash: zip dan DB dump import + `storage/app` qaytarish.

## Boshqa
- **Jurnallar CRUD**: `/admin/jurnallar` — hech qachon o‘chirilmaydi; “ro‘yxatdan chiqarish” `listed_to = bugun` qo‘yadi.
- **Jurnal arizalari**: `/admin/jurnal-arizalari` — mavjud jurnalni biriktirish yoki yangi yaratish → maqola qayta tekshiriladi; yoki izoh bilan rad.
- **Kontent**: `/admin/yoriqnoma`, `/admin/videolar`, `/admin/yangiliklar`.
- **Audit**: `/admin/audit` — har admin POST amali yoziladi.
- **O‘chirish so‘rovlari**: `/admin/ochirish-sorovlari` — tasdiqlanganda foydalanuvchi anonimlashtiriladi.
