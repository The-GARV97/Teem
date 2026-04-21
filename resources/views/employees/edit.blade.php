<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-indigo-600 leading-tight">
            {{ __('Edit Employee') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow p-6">
                <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    {{-- Name --}}
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            value="{{ old('name', $employee->name) }}" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- Email --}}
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                            value="{{ old('email', $employee->email) }}" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Phone --}}
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                            value="{{ old('phone', $employee->phone) }}" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    {{-- Department --}}
                    <div>
                        <x-input-label for="department_id" :value="__('Department')" />
                        <select id="department_id" name="department_id"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Select Department</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    @selected(old('department_id', $employee->department_id) == $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>

                    {{-- Designation --}}
                    <div>
                        <x-input-label for="designation_id" :value="__('Designation')" />
                        <select id="designation_id" name="designation_id"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Select Designation</option>
                            @foreach ($designations as $desig)
                                <option value="{{ $desig->id }}"
                                    @selected(old('designation_id', $employee->designation_id) == $desig->id)>
                                    {{ $desig->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('designation_id')" class="mt-2" />
                    </div>

                    {{-- Manager (excludes current employee) --}}
                    <div>
                        <x-input-label for="manager_id" :value="__('Manager')" />
                        <select id="manager_id" name="manager_id"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">No Manager</option>
                            @foreach ($managers->where('id', '!=', $employee->id) as $manager)
                                <option value="{{ $manager->id }}"
                                    @selected(old('manager_id', $employee->manager_id) == $manager->id)>
                                    {{ $manager->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
                    </div>

                    {{-- Joining Date --}}
                    <div>
                        <x-input-label for="joining_date" :value="__('Joining Date')" />
                        <x-text-input id="joining_date" name="joining_date" type="date" class="mt-1 block w-full"
                            value="{{ old('joining_date', $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('Y-m-d') : '') }}" />
                        <x-input-error :messages="$errors->get('joining_date')" class="mt-2" />
                    </div>

                    {{-- Status --}}
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="active" @selected(old('status', $employee->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>Inactive</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-4 pt-2">
                        <x-primary-button>{{ __('Update Employee') }}</x-primary-button>
                        <a href="{{ route('employees.show', $employee) }}"
                            class="text-sm text-gray-600 hover:text-gray-900 underline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
