<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\OrganizationStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationStructureController extends Controller
{
    public function index(Classroom $class): View
    {
        $structures = $class->organizationStructures()
            ->with('student')->orderBy('sort_order')->paginate(50);

        return view('organization.index', [
            'classroom' => $class,
            'structures' => $structures,
            'roles' => config('walikelas.student_roles'),
        ]);
    }

    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['nullable', Rule::exists('students', 'id')->where('class_id', $class->id)],
            'role' => ['required', Rule::in(array_keys(config('walikelas.student_roles')))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $class->organizationStructures()->create($data + ['user_id' => $class->user_id]);

        return back()->with('success', 'Jabatan ditambahkan.');
    }

    public function destroy(Classroom $class, OrganizationStructure $organizationStructure): RedirectResponse
    {
        abort_unless($organizationStructure->class_id === $class->id, 403);
        $organizationStructure->delete();

        return back()->with('success', 'Jabatan dihapus.');
    }
}
