<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    private function requireAdmin(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->role === 'admin',
            403,
            'Only administrators can manage accounts.'
        );
    }

    public function index(Request $request)
    {
        $this->requireAdmin($request);

        $accounts = User::where('role', 'office')
            ->orderBy('department_office')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'department_office',
                'created_at',
            ]);

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $departments = [
            'Office of the TRC Chief',
            'Office of the Chief Health Program Officer (RITD)',
            'Office of the Chief Health Program Officer (OAD)',
            'Office of the Chief Administrative Officer',
            'Office of the Supervising Administrative Officer',
            'Legal Unit',
            'Information and Communication Technology Unit',
            'Residential Program Services',
            'Medical Section',
            'Nursing Section',
            'Dental Section',
            'Clinical Laboratory Section',
            'Psychology Section',
            'Medical Social Work Section',
            'Nutritionist and Dietetics Section',
            'Dormitory Management Section',
            'Livelihood Training Section',
            'Radiology Section',
            'Outpatient Section',
            'Aftercare Section',
            'Health Information Management Section',
            'Accounting Section',
            'Budget Section',
            'Billing and Claims Section',
            'Cash Operations Section',
            'General Services Section',
            'Materials Management Section',
            'Procurement Section',
            'Human Resource Development Section',
            'Quality Management System Section',
        ];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_office' => [
                'required',
                'string',
                Rule::in($departments),
            ],
        ]);

        $account = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'office',
            'department_office' => $validated['department_office'],
        ]);

        return response()->json([
            'message' => 'Office account created successfully.',
            'data' => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'role' => $account->role,
                'department_office' => $account->department_office,
            ],
        ], 201);
    }
}