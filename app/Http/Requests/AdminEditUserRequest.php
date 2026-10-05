<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminEditUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'patronymic' => ['nullable', 'string', 'max:60'],
            'university' => ['required', 'string', 'max:190'],
            'faculty' => ['required', 'string', 'max:120'],
            'direction' => ['required', Rule::in(config('uniscience.fields'))],
            'group_name' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Ismni kiriting.',
            'last_name.required' => 'Familiyani kiriting.',
            'patronymic.string' => 'Otasining ismi matn bo‘lishi kerak.',
            'university.required' => 'OTM nomini kiriting.',
            'faculty.required' => 'Fakultetni kiriting.',
            'direction.required' => 'Yo‘nalishni tanlang.',
            'direction.in' => 'Tanlangan yo‘nalish ro‘yxatda mavjud emas.',
            'group_name.string' => 'Guruh matn bo‘lishi kerak.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['first_name', 'last_name', 'patronymic'])) {
                return;
            }

            $username = trim($this->input('last_name').' '.$this->input('first_name').' '.$this->input('patronymic'));
            $user = $this->route('user');

            if (User::whereRaw('LOWER(username) = ?', [Str::lower($username)])
                ->where('id', '!=', $user->getKey())
                ->exists()) {
                $validator->errors()->add('first_name', 'Bu F.I.Sh. bilan boshqa foydalanuvchi mavjud.');
            }
        }];
    }
}
