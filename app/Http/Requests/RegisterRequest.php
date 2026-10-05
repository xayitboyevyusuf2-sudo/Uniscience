<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator as ValidationValidator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->input('category');
        $isStudent = in_array($category, ['bakalavr', 'magistr'], true);
        $isAcademic = in_array($category, ['tadqiqotchi', 'professor'], true);

        return [
            'category' => ['required', Rule::in(['bakalavr', 'magistr', 'tadqiqotchi', 'professor'])],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'patronymic' => ['required', 'string', 'max:60'],
            'university' => ['required', 'string', 'max:190'],
            'direction' => ['required', Rule::in(config('uniscience.fields'))],
            'faculty' => [Rule::excludeIf(! $isStudent), Rule::requiredIf($isStudent), 'string', 'max:120'],
            'course' => [Rule::excludeIf(! $isStudent), Rule::requiredIf($isStudent), 'integer', 'between:1,6'],
            'group_name' => [Rule::excludeIf(! $isStudent), Rule::requiredIf($isStudent), 'string', 'max:40'],
            'gpa' => [Rule::excludeIf(! $isStudent), Rule::requiredIf($isStudent), 'numeric', 'between:0,5', 'decimal:0,2'],
            'birth_date' => [Rule::excludeIf(! $isStudent), Rule::requiredIf($isStudent), 'date', 'before:today'],
            'student_id' => [Rule::excludeIf(! $isStudent), Rule::requiredIf($isStudent), 'string', 'max:30', Rule::unique('users', 'student_id')],
            'bachelor_university' => [Rule::excludeIf($category !== 'magistr'), 'nullable', 'string', 'max:190'],
            'academic_degree' => [Rule::excludeIf(! $isAcademic), Rule::requiredIf($isAcademic), 'string', 'max:120'],
            'position_title' => [Rule::excludeIf(! $isAcademic), Rule::requiredIf($isAcademic), 'string', 'max:120'],
            'department' => [Rule::excludeIf(! $isAcademic), Rule::requiredIf($isAcademic), 'string', 'max:190'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute maydoni majburiy.',
            'string' => ':attribute matn shaklida bo‘lishi kerak.',
            'max' => ':attribute :max belgidan oshmasligi kerak.',
            'integer' => ':attribute butun son bo‘lishi kerak.',
            'numeric' => ':attribute son bo‘lishi kerak.',
            'between' => ':attribute :min va :max oralig‘ida bo‘lishi kerak.',
            'date' => ':attribute sanasi noto‘g‘ri.',
            'before' => ':attribute o‘tmishdagi sana bo‘lishi kerak.',
            'email' => 'Email manzili noto‘g‘ri.',
            'unique' => 'Bu qiymatdan foydalanuvchi allaqachon foydalanmoqda.',
            'confirmed' => 'Parollar mos kelmadi.',
            'accepted' => ':attribute uchun rozilik berish majburiy.',
            'in' => 'Tanlangan qiymat ruxsat etilgan qiymatlardan biri bo‘lishi kerak.',
            'category.required' => 'Toifani tanlang.',
            'category.in' => 'Tanlangan toifa noto‘g‘ri.',
            'first_name.required' => 'Ismni kiriting.',
            'last_name.required' => 'Familiyani kiriting.',
            'patronymic.required' => 'Otasining ismini kiriting.',
            'university.required' => 'OTM nomini kiriting.',
            'direction.required' => 'Yo‘nalishni tanlang.',
            'direction.in' => 'Tanlangan yo‘nalish ro‘yxatda mavjud emas.',
            'faculty.required' => 'Fakultetni kiriting.',
            'course.required' => 'Kursni tanlang.',
            'course.between' => 'Kurs 1 dan 6 gacha bo‘lishi kerak.',
            'group_name.required' => 'Guruhni kiriting.',
            'gpa.required' => 'GPA ko‘rsatkichini kiriting.',
            'gpa.between' => 'GPA 0 dan 5 gacha bo‘lishi kerak.',
            'gpa.decimal' => 'GPA ko‘pi bilan ikki xona kasr bilan kiritiladi.',
            'birth_date.required' => 'Tug‘ilgan sanani kiriting.',
            'birth_date.before' => 'Tug‘ilgan sana o‘tmishda bo‘lishi kerak.',
            'student_id.required' => 'Talaba ID raqamini kiriting.',
            'student_id.unique' => 'Bu talaba ID raqami bilan foydalanuvchi mavjud.',
            'academic_degree.required' => 'Ilmiy darajani kiriting.',
            'position_title.required' => 'Lavozimni kiriting.',
            'department.required' => 'Kafedra yoki bo‘limni kiriting.',
            'email.required' => 'Email manzilini kiriting.',
            'email.email' => 'Email manzili noto‘g‘ri.',
            'email.unique' => 'Bu email bilan foydalanuvchi mavjud.',
            'password.required' => 'Parolni kiriting.',
            'password.confirmed' => 'Parollar mos kelmadi.',
            'password.min' => 'Parol kamida 8 belgidan iborat bo‘lishi kerak.',
            'password.letters' => 'Parolda kamida bitta harf bo‘lishi kerak.',
            'password.numbers' => 'Parolda kamida bitta raqam bo‘lishi kerak.',
            'consent.accepted' => 'Shaxsiy ma’lumotlarni qayta ishlashga rozilik bering.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'ism',
            'last_name' => 'familiya',
            'patronymic' => 'otasining ismi',
            'university' => 'OTM',
            'faculty' => 'fakultet',
            'direction' => 'yo‘nalish',
            'course' => 'kurs',
            'group_name' => 'guruh',
            'gpa' => 'GPA',
            'birth_date' => 'tug‘ilgan sana',
            'student_id' => 'talaba ID raqami',
            'bachelor_university' => 'bakalavr OTMsi',
            'academic_degree' => 'ilmiy daraja',
            'position_title' => 'lavozim',
            'department' => 'kafedra yoki bo‘lim',
            'email' => 'email',
            'password' => 'parol',
            'consent' => 'rozilik',
        ];
    }

    public function after(): array
    {
        return [function (ValidationValidator $validator): void {
            if ($validator->errors()->hasAny(['last_name', 'first_name', 'patronymic'])) {
                return;
            }

            $username = trim($this->input('last_name').' '.$this->input('first_name').' '.$this->input('patronymic'));

            if (User::whereRaw('LOWER(username) = ?', [Str::lower($username)])->exists()) {
                $validator->errors()->add('username', 'Bu F.I.Sh. bilan foydalanuvchi mavjud. Agar bu siz bo‘lsangiz, kirish sahifasidan foydalaning yoki administratorga murojaat qiling.');
            }
        }];
    }
}
