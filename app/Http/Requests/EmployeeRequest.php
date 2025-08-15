<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Employee;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * MPGA-specific validation rules
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            // MPGA Core Fields (Required dari Excel)
            'nip' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9]+$/', // Only numbers
                Rule::unique('employees')->ignore($employeeId)
            ],
            'nama_lengkap' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.]+$/' // Only letters, spaces, and dots
            ],
            'unit_organisasi' => [
                'required',
                'string',
                'max:100'
            ],
            'department' => [
                'required',
                'string',
                Rule::in(Employee::MPGA_DEPARTMENTS)
            ],

            // Identity Fields
            'nik' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/', // Alphanumeric uppercase
                Rule::unique('employees')->ignore($employeeId)
            ],

            // Personal Information
            'jenis_kelamin' => [
                'nullable',
                Rule::in(['L', 'P'])
            ],
            'tempat_lahir' => [
                'nullable',
                'string',
                'max:100'
            ],
            'tanggal_lahir' => [
                'nullable',
                'date',
                'before:today',
                'after:1950-01-01'
            ],
            'usia' => [
                'nullable',
                'integer',
                'min:17',
                'max:65'
            ],

            // Contact Information
            'handphone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\-\(\)\s]+$/' // Phone number format
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('employees')->ignore($employeeId)
            ],
            'alamat' => [
                'nullable',
                'string',
                'max:500'
            ],
            'kota_domisili' => [
                'nullable',
                'string',
                'max:100'
            ],

            // Work Information
            'status_pegawai' => [
                'required',
                Rule::in(Employee::STATUS_PEGAWAI_OPTIONS)
            ],
            'status_kerja' => [
                'required',
                'string',
                'max:50'
            ],
            'lokasi_kerja' => [
                'nullable',
                'string',
                'max:100'
            ],
            'cabang' => [
                'nullable',
                'string',
                'max:100'
            ],
            'provider' => [
                'nullable',
                'string',
                'max:100'
            ],

            // Organizational Structure
            'jabatan' => [
                'nullable',
                'string',
                'max:100'
            ],
            'nama_jabatan' => [
                'nullable',
                'string',
                'max:100'
            ],
            'kelompok_jabatan' => [
                'nullable',
                'string',
                Rule::in([
                    'SUPERVISOR',
                    'STAFF',
                    'MANAGER',
                    'EXECUTIVE GENERAL MANAGER',
                    'ACCOUNT EXECUTIVE/AE'
                ])
            ],

            // Employment Dates
            'tmt_mulai_kerja' => [
                'nullable',
                'date',
                'before_or_equal:today'
            ],
            'tmt_mulai_jabatan' => [
                'nullable',
                'date',
                'before_or_equal:today'
            ],
            'tmt_berakhir_jabatan' => [
                'nullable',
                'date',
                'after:tmt_mulai_jabatan'
            ],

            // Education
            'pendidikan_terakhir' => [
                'nullable',
                'string',
                'max:50'
            ],
            'instansi_pendidikan' => [
                'nullable',
                'string',
                'max:100'
            ],
            'jurusan' => [
                'nullable',
                'string',
                'max:100'
            ],
            'tahun_lulus' => [
                'nullable',
                'integer',
                'min:1980',
                'max:' . date('Y')
            ],

            // Physical Data
            'weight' => [
                'nullable',
                'integer',
                'min:30',
                'max:200'
            ],
            'height' => [
                'nullable',
                'integer',
                'min:140',
                'max:220'
            ],

            // Equipment
            'jenis_sepatu' => [
                'nullable',
                'string',
                'max:50'
            ],
            'ukuran_sepatu' => [
                'nullable',
                'integer',
                'min:35',
                'max:50'
            ],

            // Benefits
            'no_bpjs_kesehatan' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]+$/'
            ],
            'no_bpjs_ketenagakerjaan' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]+$/'
            ]
        ];
    }

    /**
     * Get custom error messages for validation
     */
    public function messages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'nip.regex' => 'NIP hanya boleh berisi angka.',

            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.regex' => 'Nama lengkap hanya boleh berisi huruf dan spasi.',

            'unit_organisasi.required' => 'Unit organisasi wajib diisi.',

            'department.required' => 'Departemen wajib dipilih.',
            'department.in' => 'Departemen tidak valid.',

            'nik.unique' => 'NIK sudah terdaftar.',
            'nik.regex' => 'NIK hanya boleh berisi huruf kapital dan angka.',

            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',

            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'tanggal_lahir.after' => 'Tanggal lahir tidak valid.',

            'usia.min' => 'Usia minimal 17 tahun.',
            'usia.max' => 'Usia maksimal 65 tahun.',

            'handphone.regex' => 'Format nomor handphone tidak valid.',

            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',

            'status_pegawai.required' => 'Status pegawai wajib dipilih.',
            'status_pegawai.in' => 'Status pegawai tidak valid.',

            'status_kerja.required' => 'Status kerja wajib diisi.',

            'tmt_mulai_kerja.before_or_equal' => 'TMT mulai kerja tidak boleh lebih dari hari ini.',

            'tahun_lulus.min' => 'Tahun lulus minimal 1980.',
            'tahun_lulus.max' => 'Tahun lulus tidak boleh lebih dari tahun ini.',

            'weight.min' => 'Berat badan minimal 30 kg.',
            'weight.max' => 'Berat badan maksimal 200 kg.',

            'height.min' => 'Tinggi badan minimal 140 cm.',
            'height.max' => 'Tinggi badan maksimal 220 cm.',

            'no_bpjs_kesehatan.regex' => 'Nomor BPJS Kesehatan hanya boleh berisi angka.',
            'no_bpjs_ketenagakerjaan.regex' => 'Nomor BPJS Ketenagakerjaan hanya boleh berisi angka.'
        ];
    }

    /**
     * Get custom attributes for validation
     */
    public function attributes(): array
    {
        return [
            'nip' => 'NIP',
            'nama_lengkap' => 'Nama Lengkap',
            'unit_organisasi' => 'Unit Organisasi',
            'department' => 'Departemen',
            'nik' => 'NIK',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'handphone' => 'Handphone',
            'status_pegawai' => 'Status Pegawai',
            'status_kerja' => 'Status Kerja',
            'tmt_mulai_kerja' => 'TMT Mulai Kerja',
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'tahun_lulus' => 'Tahun Lulus',
            'no_bpjs_kesehatan' => 'No. BPJS Kesehatan',
            'no_bpjs_ketenagakerjaan' => 'No. BPJS Ketenagakerjaan'
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: Auto-generate NIK if not provided
            if (empty($this->nik) && !empty($this->nip)) {
                $this->merge([
                    'nik' => Employee::generateNik($this->nip, $this->department)
                ]);
            }

            // Custom validation: Calculate age from birth date
            if ($this->tanggal_lahir && empty($this->usia)) {
                $age = \Carbon\Carbon::parse($this->tanggal_lahir)->age;
                $this->merge(['usia' => $age]);
            }
        });
    }
}
