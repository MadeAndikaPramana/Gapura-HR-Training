<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;

class TrainingController extends Controller
{
    /**
     * Display training records listing
     */
    public function index(Request $request)
    {
        // Untuk sementara, return simple data
        $trainingRecords = collect([
            (object)[
                'id' => 1,
                'employee_name' => 'John Doe',
                'training_type' => 'Safety Training',
                'certificate_number' => 'GLC/OPR-001/2024',
                'valid_until' => '2025-12-31',
                'status' => 'active'
            ],
            (object)[
                'id' => 2,
                'employee_name' => 'Jane Smith',
                'training_type' => 'PAX Handling',
                'certificate_number' => 'GLC/OPR-002/2024',
                'valid_until' => '2025-11-30',
                'status' => 'active'
            ]
        ]);

        // Mock pagination
        $pagination = (object)[
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => 2,
            'from' => 1,
            'to' => 2,
            'data' => $trainingRecords
        ];

        return Inertia::render('Training/Index', [
            'trainingRecords' => $pagination,
            'filterOptions' => [
                'departments' => [
                    'DEDICATED' => 'Dedicated Service',
                    'LOADING' => 'Loading Operations',
                    'RAMP' => 'Ramp Operations'
                ],
                'trainingTypes' => [
                    1 => 'Safety Training',
                    2 => 'PAX Handling',
                    3 => 'Security Awareness'
                ]
            ],
            'statistics' => [
                'total_employees' => 150,
                'total_trainings' => 300,
                'active_certificates' => 280,
                'expiring_soon' => 15,
                'expired' => 5
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'department' => $request->get('department', 'all'),
                'training_type' => $request->get('training_type', 'all'),
                'status' => $request->get('status', 'all')
            ],
            'title' => 'Training Records Management',
            'subtitle' => 'Kelola data pelatihan dan sertifikasi karyawan'
        ]);
    }

    /**
     * Display employee listing for training
     */
    public function employees(Request $request)
    {
        // Sample employee data (sesuai MPGA structure)
        $employees = collect([
            (object)[
                'id' => 1,
                'nip' => '2160800',
                'nama_lengkap' => 'PUTU EKA RESMAWAN',
                'unit_kerja' => 'AE',
                'department' => 'DEDICATED'
            ],
            (object)[
                'id' => 2,
                'nip' => '2980961',
                'nama_lengkap' => 'PUTU ERNAWATI',
                'unit_kerja' => 'Controller',
                'department' => 'DEDICATED'
            ],
            (object)[
                'id' => 3,
                'nip' => '2160798',
                'nama_lengkap' => 'KADEK MEGAYANA',
                'unit_kerja' => 'Controller',
                'department' => 'LOADING'
            ],
            (object)[
                'id' => 4,
                'nip' => '2160792',
                'nama_lengkap' => 'I KOMANG JULIANTARA',
                'unit_kerja' => 'Controller',
                'department' => 'LOADING'
            ],
            (object)[
                'id' => 5,
                'nip' => '9794158',
                'nama_lengkap' => 'PUTU SENDIANA SAPUTRA',
                'unit_kerja' => 'Controller',
                'department' => 'RAMP'
            ]
        ]);

        // Apply search filter
        $search = $request->get('search', '');
        if ($search) {
            $employees = $employees->filter(function($employee) use ($search) {
                return stripos($employee->nama_lengkap, $search) !== false ||
                       stripos($employee->nip, $search) !== false;
            });
        }

        // Apply department filter
        $department = $request->get('department', 'all');
        if ($department !== 'all') {
            $employees = $employees->filter(function($employee) use ($department) {
                return $employee->department === $department;
            });
        }

        // Mock pagination
        $pagination = (object)[
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => $employees->count(),
            'from' => 1,
            'to' => $employees->count(),
            'data' => $employees->values(),
            'first_page_url' => url()->current() . '?page=1',
            'last_page_url' => url()->current() . '?page=1',
            'next_page_url' => null,
            'prev_page_url' => null
        ];

        return Inertia::render('Training/Employees', [
            'employees' => $pagination,
            'departments' => [
                'DEDICATED' => 'Dedicated Service',
                'LOADING' => 'Loading Operations',
                'RAMP' => 'Ramp Operations',
                'LOCO' => 'Ground Support Equipment',
                'ULD' => 'Unit Load Device',
                'LOST & FOUND' => 'Lost & Found Service',
                'CARGO' => 'Cargo Operations',
                'ARRIVAL' => 'Arrival Service',
                'GSE OPERATOR' => 'GSE Operator',
                'FLOP' => 'Flight Operations',
                'AVSEC' => 'Aviation Security',
                'PORTER' => 'Porter Service'
            ],
            'filters' => [
                'search' => $search,
                'department' => $department
            ],
            'statistics' => [
                'active_employees' => $employees->count(),
                'total_departments' => 12
            ],
            'title' => 'Data Karyawan Training',
            'subtitle' => 'Data karyawan berdasarkan file MPGA - Menampilkan Nama dan NIP'
        ]);
    }

    /**
     * Training dashboard
     */
    public function dashboard()
    {
        return Inertia::render('Training/Dashboard', [
            'title' => 'Training Dashboard',
            'subtitle' => 'Overview training dan compliance karyawan'
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Training/Create', [
            'title' => 'Add Training Record'
        ]);
    }

    /**
     * Store new training record
     */
    public function store(Request $request)
    {
        // Implementation here
        return redirect()->route('training.index')->with('success', 'Training record created successfully');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        return Inertia::render('Training/Edit', [
            'title' => 'Edit Training Record'
        ]);
    }

    /**
     * Update training record
     */
    public function update(Request $request, $id)
    {
        // Implementation here
        return redirect()->route('training.index')->with('success', 'Training record updated successfully');
    }

    /**
     * Delete training record
     */
    public function destroy($id)
    {
        // Implementation here
        return redirect()->route('training.index')->with('success', 'Training record deleted successfully');
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return Inertia::render('Training/Import', [
            'title' => 'Import MPGA Data'
        ]);
    }

    /**
     * Process import
     */
    public function importData(Request $request)
    {
        // Implementation here
        return redirect()->route('training.employees')->with('success', 'Data imported successfully');
    }

    /**
     * Export data
     */
    public function export()
    {
        // Implementation here
        return response()->download('path/to/export.xlsx');
    }
}
