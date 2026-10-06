<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Dedicated admin user management: search/filter, plus staff (admin/moderator/rahbariyat) account creation. */
class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'role' => ['nullable', Rule::in(['student', 'moderator', 'admin', 'rahbariyat'])],
            'category' => ['nullable', 'string', 'max:20'],
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'faculty' => ['nullable', 'string', 'max:190'],
        ]);

        $query = User::query()->latest('id');
        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%')
                ->orWhere('username', 'like', '%'.$term.'%')
                ->orWhere('student_id', 'like', '%'.$term.'%'));
        }
        foreach (['role', 'category', 'approval_status', 'faculty'] as $key) {
            if ($value = $filters[$key] ?? null) {
                $query->where($key, $value);
            }
        }

        return view('admin.users.index', [
            'users' => $query->paginate(25)->withQueryString(),
            'filters' => $filters,
            'faculties' => User::query()->whereNotNull('faculty')->where('faculty', '!=', '')->distinct()->orderBy('faculty')->pluck('faculty'),
            'categories' => ['bakalavr', 'magistr', 'tadqiqotchi', 'professor'],
        ]);
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'moderator', 'rahbariyat'])],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ], [
            'email.unique' => 'Bu email bilan hisob allaqachon mavjud.',
            'password.min' => 'Parol kamida 8 belgidan iborat bo‘lishi kerak.',
        ]);

        $user = DB::transaction(fn () => User::create([
            'name' => $data['name'],
            'first_name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'category' => null,
            'approval_status' => 'approved',
            'email_verified_at' => now(),
            'consent_at' => now(),
        ]));
        AuditLog::record('user.staff_created', $user, null, ['role' => $user->role, 'email' => $user->email, 'name' => $user->name]);

        return back()->with('ok', 'Xodim hisobi yaratildi: '.$user->email);
    }
}
