<?php

namespace App\Http\Requests;

use App\Models\Article;
use App\Models\User;
use App\Services\Verifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $family = config('uniscience.article_types.'.$this->input('type').'.family');
        $fieldRequired = in_array($family, ['scopus', 'conference'], true);

        return [
            'title' => ['required', 'string', 'max:500'],
            'annotation_uz' => ['required', 'string', 'min:50'],
            'annotation_ru' => ['required', 'string', 'min:50'],
            'annotation_en' => ['required', 'string', 'min:50'],
            'keywords_uz' => ['required', 'string', 'max:500'],
            'keywords_ru' => ['required', 'string', 'max:500'],
            'keywords_en' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::in(array_keys(config('uniscience.article_types', [])))],
            'field' => [Rule::excludeIf(! $fieldRequired), Rule::requiredIf($fieldRequired), 'string', 'max:500'],
            'published_at' => ['required', 'date', 'before_or_equal:today'],
            'journal_name' => ['required', 'string', 'max:255'],
            'journal_id' => [Rule::excludeIf($family !== 'journal'), 'nullable', 'integer', Rule::exists('journals', 'id')],
            'journal_not_listed' => [Rule::excludeIf($family !== 'journal'), 'sometimes', 'boolean'],
            'issn' => ['nullable', 'regex:/^\d{4}-\d{3}[\dXx]$/'],
            'url' => ['required', 'url', 'max:500'],
            'doi' => ['nullable', 'string', 'max:190'],
            'authors' => ['required', 'array', 'min:1', 'max:50'],
            'authors.*.full_name' => ['required', 'string', 'max:190'],
            'authors.*.position' => ['required', Rule::in(array_keys(config('uniscience.position_labels')))],
            'authors.*.is_submitter' => ['sometimes', 'boolean'],
            'authors.*.user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('category', ['professor', 'tadqiqotchi'])),
            ],
            'pdf' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'conference_certificate' => [
                Rule::excludeIf($family !== 'conference'),
                Rule::requiredIf($family === 'conference'),
                'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute maydonini to‘ldiring.',
            'string' => ':attribute matn shaklida bo‘lishi kerak.',
            'array' => ':attribute ro‘yxat shaklida bo‘lishi kerak.',
            'max' => ':attribute :max belgidan/KBdan oshmasligi kerak.',
            'min' => ':attribute kamida :min qiymatga ega bo‘lishi kerak.',
            'date' => ':attribute sanasi noto‘g‘ri.',
            'url' => ':attribute to‘g‘ri URL bo‘lishi kerak.',
            'integer' => ':attribute butun son bo‘lishi kerak.',
            'boolean' => ':attribute qiymati noto‘g‘ri.',
            'exists' => ':attribute tanlangan foydalanuvchi topilmadi.',
            'file' => ':attribute fayl bo‘lishi kerak.',
            'mimes' => ':attribute fayl formati ruxsat etilmagan.',
            'mimetypes' => ':attribute faylining tarkib formati ruxsat etilmagan.',
            'title.required' => 'Maqola nomini kiriting.',
            'title.max' => 'Maqola nomi 500 belgidan oshmasligi kerak.',
            'annotation_uz.required' => 'O‘zbek tilidagi annotatsiyani kiriting.',
            'annotation_uz.min' => 'O‘zbek tilidagi annotatsiya kamida 50 belgidan iborat bo‘lishi kerak.',
            'annotation_ru.required' => 'Rus tilidagi annotatsiyani kiriting.',
            'annotation_ru.min' => 'Rus tilidagi annotatsiya kamida 50 belgidan iborat bo‘lishi kerak.',
            'annotation_en.required' => 'Ingliz tilidagi annotatsiyani kiriting.',
            'annotation_en.min' => 'Ingliz tilidagi annotatsiya kamida 50 belgidan iborat bo‘lishi kerak.',
            'keywords_uz.required' => 'O‘zbek tilidagi kalit so‘zlarni kiriting.',
            'keywords_ru.required' => 'Rus tilidagi kalit so‘zlarni kiriting.',
            'keywords_en.required' => 'Ingliz tilidagi kalit so‘zlarni kiriting.',
            'type.required' => 'Nashr turini tanlang.',
            'type.in' => 'Tanlangan nashr turi noto‘g‘ri.',
            'field.required' => 'Scopus/WoS yoki konferensiya nashri uchun sohani tanlang.',
            'field.in' => 'Tanlangan soha ro‘yxatda mavjud emas.',
            'journal_id.exists' => 'Tanlangan jurnal ma’lumotnomada topilmadi.',
            'conference_certificate.required' => 'Konferensiya sertifikati PDF faylini yuklang.',
            'conference_certificate.mimes' => 'Konferensiya sertifikati PDF bo‘lishi kerak.',
            'conference_certificate.mimetypes' => 'Sertifikat faylining tarkibi PDF bo‘lishi kerak.',
            'conference_certificate.max' => 'Sertifikat hajmi 10 MB dan oshmasligi kerak.',
            'published_at.required' => 'Chop etilgan sanani kiriting.',
            'published_at.before_or_equal' => 'Chop etilgan sana bugungi kundan keyin bo‘lishi mumkin emas.',
            'journal_name.required' => 'Jurnal yoki nashr nomini kiriting.',
            'issn.regex' => 'ISSN formati 0000-0000 ko‘rinishida bo‘lishi kerak.',
            'url.required' => 'Nashr havolasini kiriting.',
            'url.url' => 'Nashr havolasi URL formatida bo‘lishi kerak.',
            'doi.max' => 'DOI 190 belgidan oshmasligi kerak.',
            'authors.required' => 'Kamida bitta muallif kiriting.',
            'authors.min' => 'Kamida bitta muallif kiriting.',
            'authors.*.full_name.required' => 'Har bir muallifning F.I.Sh.ini kiriting.',
            'authors.*.position.required' => 'Har bir muallifning pozitsiyasini tanlang.',
            'authors.*.position.in' => 'Muallif pozitsiyasi noto‘g‘ri.',
            'authors.*.user_id.exists' => 'Tizimdagi muallif professor yoki tadqiqotchi bo‘lishi kerak.',
            'pdf.required' => 'Maqola PDF faylini yuklang.',
            'pdf.mimes' => 'Faqat PDF faylini yuklash mumkin.',
            'pdf.mimetypes' => 'Yuklangan faylning tarkibi PDF bo‘lishi kerak.',
            'pdf.max' => 'PDF fayl hajmi 20 MB dan oshmasligi kerak.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'maqola nomi',
            'annotation_uz' => 'o‘zbek tilidagi annotatsiya',
            'annotation_ru' => 'rus tilidagi annotatsiya',
            'annotation_en' => 'ingliz tilidagi annotatsiya',
            'keywords_uz' => 'o‘zbek tilidagi kalit so‘zlar',
            'keywords_ru' => 'rus tilidagi kalit so‘zlar',
            'keywords_en' => 'ingliz tilidagi kalit so‘zlar',
            'type' => 'nashr turi',
            'field' => 'soha',
            'journal_id' => 'jurnal',
            'conference_certificate' => 'konferensiya sertifikati',
            'published_at' => 'chop etilgan sana',
            'journal_name' => 'jurnal yoki nashr nomi',
            'issn' => 'ISSN',
            'url' => 'nashr havolasi',
            'doi' => 'DOI',
            'authors' => 'mualliflar',
            'authors.*.full_name' => 'muallif F.I.Sh.',
            'authors.*.position' => 'muallif pozitsiyasi',
            'authors.*.user_id' => 'tizimdagi muallif',
            'pdf' => 'maqola PDF fayli',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (['keywords_uz' => 'O‘zbek', 'keywords_ru' => 'Rus', 'keywords_en' => 'Ingliz'] as $field => $language) {
                if ($validator->errors()->has($field)) {
                    continue;
                }

                $keywords = array_filter(array_map('trim', explode(',', (string) $this->input($field))));
                if (count($keywords) < 3) {
                    $validator->errors()->add($field, $language.' tilida kamida 3 ta kalit so‘z kiriting.');
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $authors = $this->input('authors', []);
            $submitters = collect($authors)->filter(fn (array $author): bool => filter_var($author['is_submitter'] ?? false, FILTER_VALIDATE_BOOLEAN))->count();
            if ($submitters !== 1) {
                $validator->errors()->add('authors', 'Mualliflardan aynan bittasini yuboruvchi sifatida belgilang.');
            }

            if (collect($authors)->contains(fn (array $author): bool => ! empty($author['user_id']) && ! User::whereKey($author['user_id'])->whereIn('category', ['professor', 'tadqiqotchi'])->exists())) {
                $validator->errors()->add('authors', 'Tizimdagi muallif faqat professor yoki tadqiqotchi bo‘lishi mumkin.');
            }

            $normalizedTitle = Verifier::norm((string) $this->input('title'));
            if (Article::where('user_id', $this->user()->getAuthIdentifier())->select('title')->cursor()->contains(
                fn (Article $article): bool => Verifier::norm($article->title) === $normalizedTitle,
            )) {
                $validator->errors()->add('title', 'Shu nomdagi maqola portfelingizda mavjud.');
            }

            if (filled($this->input('doi')) && Article::whereRaw('LOWER(doi) = ?', [mb_strtolower(trim($this->input('doi')))])->exists()) {
                $validator->errors()->add('doi', 'Bu DOI bilan maqola avval yuklangan.');
            }

            Article::where('url', $this->input('url'))
                ->when($this->input('issn'), fn ($query, $issn) => $query->where('issn', $issn))
                ->exists() && $validator->errors()->add('url', 'Bu URL bilan maqola avval yuklangan.');
        }];
    }
}
