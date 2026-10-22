@extends('layouts.app')
@section('title',$editing?'Edit employee':'Employee registration')@section('breadcrumb','Employees')@section('heading',$editing?'Edit employee':'Register an employee')
@section('content')
<div class="mx-auto max-w-4xl">
    <a href="{{ route('employees.index') }}" class="text-sm font-semibold text-gold-600">← Back to employees</a>
    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
        <div class="border-b border-slate-100 bg-white px-5 py-6 sm:px-8">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-gold-600">{{ $editing?'Employee directory':'Team management' }}</p>
            <h2 class="mt-2 text-2xl font-bold text-navy-950">{{ $editing?'Update employee details':'Employee registration' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $editing?'Keep this employee’s information up to date.':'Enter the employee’s details below to add them to the company directory.' }} Fields marked <span class="font-semibold text-rose-500">*</span> are required.</p>
        </div>
        <form method="POST" action="{{ $editing?route('employees.update',$employee):route('employees.store') }}" class="p-5 sm:p-8">
            @csrf
            @if($editing)@method('PUT')@endif
            <section aria-labelledby="personal-details-heading">
                <h3 id="personal-details-heading" class="text-base font-bold text-navy-950">Personal and contact details</h3>
                <p class="mt-1 text-sm text-slate-500">Basic information used to identify and contact the employee.</p>
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="employee_code" class="mb-1.5 block text-sm font-semibold">Employee ID <span class="text-rose-500">*</span></label>
                        <input id="employee_code" class="field" type="text" name="employee_code" value="{{ old('employee_code',$employee->employee_code) }}" placeholder="ABJ-001" maxlength="30" required>
                        @error('employee_code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold">Full name <span class="text-rose-500">*</span></label>
                        <input id="name" class="field" type="text" name="name" value="{{ old('name',$employee->name) }}" placeholder="Employee full name" maxlength="150" autocomplete="name" required>
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold">Email address <span class="text-rose-500">*</span></label>
                        <input id="email" class="field" type="email" name="email" value="{{ old('email',$employee->email) }}" placeholder="name@example.com" maxlength="150" autocomplete="email" required>
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-semibold">Phone number</label>
                        <input id="phone" class="field" type="tel" name="phone" value="{{ old('phone',$employee->phone) }}" placeholder="Mobile number" maxlength="25" autocomplete="tel">
                        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="mb-1.5 block text-sm font-semibold">Address</label>
                        <textarea id="address" class="field" rows="3" name="address" maxlength="1000" autocomplete="street-address">{{ old('address',$employee->address) }}</textarea>
                        @error('address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>
            <section aria-labelledby="employment-details-heading" class="mt-8 border-t border-slate-100 pt-7">
                <h3 id="employment-details-heading" class="text-base font-bold text-navy-950">Employment details</h3>
                <p class="mt-1 text-sm text-slate-500">Set the employee’s role and current employment information.</p>
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="department" class="mb-1.5 block text-sm font-semibold">Department <span class="text-rose-500">*</span></label>
                        <select id="department" class="field" name="department" required>
                            <option value="">Select department</option>
                            @foreach($departments as $department)<option value="{{ $department }}" @selected(old('department',$employee->department)===$department)>{{ $department }}</option>@endforeach
                        </select>
                        @error('department')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="designation" class="mb-1.5 block text-sm font-semibold">Designation <span class="text-rose-500">*</span></label>
                        <input id="designation" class="field" type="text" name="designation" value="{{ old('designation',$employee->designation) }}" placeholder="e.g. Sales Executive" maxlength="100" required>
                        @error('designation')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="role" class="mb-1.5 block text-sm font-semibold">Account role <span class="text-rose-500">*</span></label>
                        <select id="role" class="field" name="role" required>
                            @foreach($roles as $role)<option value="{{ $role }}" @selected(old('role',$selectedRole)===$role)>{{ ucfirst($role) }}</option>@endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">New accounts receive an email link to set their password.</p>
                        @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="joining_date" class="mb-1.5 block text-sm font-semibold">Joining date <span class="text-rose-500">*</span></label>
                        <input id="joining_date" class="field" type="date" name="joining_date" value="{{ old('joining_date',$employee->joining_date?->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" required>
                        @error('joining_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="employment_status" class="mb-1.5 block text-sm font-semibold">Employment status <span class="text-rose-500">*</span></label>
                        <select id="employment_status" class="field" name="employment_status" required>
                            <option value="Active" @selected(old('employment_status',$employee->employment_status??'Active')==='Active')>Active</option>
                            <option value="Inactive" @selected(old('employment_status',$employee->employment_status)==='Inactive')>Inactive</option>
                        </select>
                        @error('employment_status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>
            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('employees.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $editing?'Save changes':'Register employee' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
