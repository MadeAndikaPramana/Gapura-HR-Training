import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Save, X, User } from 'lucide-react';

export default function Edit({
    auth,
    employee,
    departments = [],
    statusPegawaiOptions = [],
    unitOptions = [],
    title = "Edit Karyawan",
    subtitle = "Edit data karyawan"
}) {
    const { data, setData, put, processing, errors } = useForm({
        nip: employee.nip || '',
        nama_lengkap: employee.nama_lengkap || '',
        unit_organisasi: employee.unit_organisasi || '',
        unit_kerja: employee.unit_kerja || '',
        department: employee.department || '',
        status_pegawai: employee.status_pegawai || 'PEGAWAI TETAP',
        status_kerja: employee.status_kerja || 'Aktif',
        jenis_kelamin: employee.jenis_kelamin || '',
        email: employee.email || '',
        handphone: employee.handphone || '',
        alamat: employee.alamat || '',
        tempat_lahir: employee.tempat_lahir || '',
        tanggal_lahir: employee.tanggal_lahir || '',
        usia: employee.usia || '',
        lokasi_kerja: employee.lokasi_kerja || '',
        cabang: employee.cabang || '',
        provider: employee.provider || '',
        jabatan: employee.jabatan || '',
        kelompok_jabatan: employee.kelompok_jabatan || '',
    });

    // Default MPGA departments
    const mpgaDepartments = departments.length > 0 ? departments : [
        'DEDICATED', 'LOADING', 'RAMP', 'LOCO', 'ULD',
        'LOST & FOUND', 'CARGO', 'ARRIVAL', 'GSE OPERATOR',
        'FLOP', 'AVSEC', 'PORTER'
    ];

    const statusOptions = statusPegawaiOptions.length > 0 ? statusPegawaiOptions : [
        'PEGAWAI TETAP', 'PKWT', 'TAD PAKET SDM', 'TAD PAKET PEKERJAAN'
    ];

    const kelompokJabatanOptions = [
        'SUPERVISOR', 'STAFF', 'MANAGER', 'EXECUTIVE GENERAL MANAGER', 'ACCOUNT EXECUTIVE/AE'
    ];

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id));
    };

    return (
        <DashboardLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {title}
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            {subtitle} - {employee.nama_lengkap}
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Link
                            href={route('employees.show', employee.id)}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <User className="w-4 h-4 mr-2" />
                            Lihat Detail
                        </Link>
                        <Link
                            href={route('employees.index')}
                            className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Kembali
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`${title} - ${employee.nama_lengkap}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">

                            {/* Employee Info Header */}
                            <div className="bg-gradient-to-r from-[#439454] to-[#367a41] rounded-lg p-4 mb-6">
                                <div className="flex items-center text-white">
                                    <div className="flex-shrink-0">
                                        <div className="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <User className="w-6 h-6" />
                                        </div>
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-lg font-semibold">{employee.nama_lengkap}</h3>
                                        <p className="text-sm opacity-90">
                                            NIP: {employee.nip} | NIK: {employee.nik} | Department: {employee.department}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-8">

                                {/* Basic Information Section */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Informasi Dasar
                                    </h3>
                                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">

                                        {/* NIP */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                NIP <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={data.nip}
                                                onChange={(e) => setData('nip', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                                required
                                            />
                                            {errors.nip && <div className="text-red-500 text-sm mt-1">{errors.nip}</div>}
                                        </div>

                                        {/* Nama Lengkap */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Nama Lengkap <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={data.nama_lengkap}
                                                onChange={(e) => setData('nama_lengkap', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                                required
                                            />
                                            {errors.nama_lengkap && <div className="text-red-500 text-sm mt-1">{errors.nama_lengkap}</div>}
                                        </div>

                                        {/* Jenis Kelamin */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Jenis Kelamin
                                            </label>
                                            <select
                                                value={data.jenis_kelamin}
                                                onChange={(e) => setData('jenis_kelamin', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            >
                                                <option value="">Pilih Jenis Kelamin</option>
                                                <option value="L">Laki-laki</option>
                                                <option value="P">Perempuan</option>
                                            </select>
                                            {errors.jenis_kelamin && <div className="text-red-500 text-sm mt-1">{errors.jenis_kelamin}</div>}
                                        </div>

                                        {/* Tempat Lahir */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Tempat Lahir
                                            </label>
                                            <input
                                                type="text"
                                                value={data.tempat_lahir}
                                                onChange={(e) => setData('tempat_lahir', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.tempat_lahir && <div className="text-red-500 text-sm mt-1">{errors.tempat_lahir}</div>}
                                        </div>

                                        {/* Tanggal Lahir */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Tanggal Lahir
                                            </label>
                                            <input
                                                type="date"
                                                value={data.tanggal_lahir}
                                                onChange={(e) => setData('tanggal_lahir', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.tanggal_lahir && <div className="text-red-500 text-sm mt-1">{errors.tanggal_lahir}</div>}
                                        </div>

                                        {/* Usia */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Usia
                                            </label>
                                            <input
                                                type="number"
                                                value={data.usia}
                                                onChange={(e) => setData('usia', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                                min="17"
                                                max="65"
                                            />
                                            {errors.usia && <div className="text-red-500 text-sm mt-1">{errors.usia}</div>}
                                        </div>

                                    </div>
                                </div>

                                {/* Work Information Section */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Informasi Pekerjaan
                                    </h3>
                                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">

                                        {/* Unit Organisasi */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Unit Organisasi <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={data.unit_organisasi}
                                                onChange={(e) => setData('unit_organisasi', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                                required
                                            />
                                            {errors.unit_organisasi && <div className="text-red-500 text-sm mt-1">{errors.unit_organisasi}</div>}
                                        </div>

                                        {/* Unit Kerja */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Unit Kerja <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={data.unit_kerja}
                                                onChange={(e) => setData('unit_kerja', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                                required
                                            />
                                            {errors.unit_kerja && <div className="text-red-500 text-sm mt-1">{errors.unit_kerja}</div>}
                                        </div>

                                        {/* Department MPGA */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Department MPGA <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                value={data.department}
                                                onChange={(e) => setData('department', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                                required
                                            >
                                                <option value="">Pilih Department</option>
                                                {mpgaDepartments.map((dept) => (
                                                    <option key={dept} value={dept}>
                                                        {dept}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.department && <div className="text-red-500 text-sm mt-1">{errors.department}</div>}
                                            {data.department !== employee.department && (
                                                <p className="text-yellow-600 text-sm mt-1">
                                                    ⚠️ Mengubah department akan mengupdate NIK secara otomatis
                                                </p>
                                            )}
                                        </div>

                                        {/* Jabatan */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Jabatan
                                            </label>
                                            <input
                                                type="text"
                                                value={data.jabatan}
                                                onChange={(e) => setData('jabatan', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.jabatan && <div className="text-red-500 text-sm mt-1">{errors.jabatan}</div>}
                                        </div>

                                        {/* Kelompok Jabatan */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Kelompok Jabatan
                                            </label>
                                            <select
                                                value={data.kelompok_jabatan}
                                                onChange={(e) => setData('kelompok_jabatan', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            >
                                                <option value="">Pilih Kelompok Jabatan</option>
                                                {kelompokJabatanOptions.map((option) => (
                                                    <option key={option} value={option}>
                                                        {option}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.kelompok_jabatan && <div className="text-red-500 text-sm mt-1">{errors.kelompok_jabatan}</div>}
                                        </div>

                                        {/* Status Pegawai */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Status Pegawai <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                value={data.status_pegawai}
                                                onChange={(e) => setData('status_pegawai', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                                required
                                            >
                                                {statusOptions.map((status) => (
                                                    <option key={status} value={status}>
                                                        {status}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.status_pegawai && <div className="text-red-500 text-sm mt-1">{errors.status_pegawai}</div>}
                                        </div>

                                        {/* Status Kerja */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Status Kerja
                                            </label>
                                            <select
                                                value={data.status_kerja}
                                                onChange={(e) => setData('status_kerja', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            >
                                                <option value="Aktif">Aktif</option>
                                                <option value="Non-Aktif">Non-Aktif</option>
                                                <option value="Cuti">Cuti</option>
                                                <option value="Pensiun">Pensiun</option>
                                            </select>
                                            {errors.status_kerja && <div className="text-red-500 text-sm mt-1">{errors.status_kerja}</div>}
                                        </div>

                                    </div>
                                </div>

                                {/* Contact Information Section */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Informasi Kontak
                                    </h3>
                                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">

                                        {/* Email */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Email
                                            </label>
                                            <input
                                                type="email"
                                                value={data.email}
                                                onChange={(e) => setData('email', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.email && <div className="text-red-500 text-sm mt-1">{errors.email}</div>}
                                        </div>

                                        {/* Handphone */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Handphone
                                            </label>
                                            <input
                                                type="text"
                                                value={data.handphone}
                                                onChange={(e) => setData('handphone', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.handphone && <div className="text-red-500 text-sm mt-1">{errors.handphone}</div>}
                                        </div>

                                        {/* Alamat */}
                                        <div className="sm:col-span-2">
                                            <label className="block text-sm font-medium text-gray-700">
                                                Alamat
                                            </label>
                                            <textarea
                                                value={data.alamat}
                                                onChange={(e) => setData('alamat', e.target.value)}
                                                rows={3}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.alamat && <div className="text-red-500 text-sm mt-1">{errors.alamat}</div>}
                                        </div>

                                    </div>
                                </div>

                                {/* Additional Information Section */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Informasi Tambahan
                                    </h3>
                                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">

                                        {/* Lokasi Kerja */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Lokasi Kerja
                                            </label>
                                            <input
                                                type="text"
                                                value={data.lokasi_kerja}
                                                onChange={(e) => setData('lokasi_kerja', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.lokasi_kerja && <div className="text-red-500 text-sm mt-1">{errors.lokasi_kerja}</div>}
                                        </div>

                                        {/* Cabang */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Cabang
                                            </label>
                                            <input
                                                type="text"
                                                value={data.cabang}
                                                onChange={(e) => setData('cabang', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.cabang && <div className="text-red-500 text-sm mt-1">{errors.cabang}</div>}
                                        </div>

                                        {/* Provider */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Provider
                                            </label>
                                            <input
                                                type="text"
                                                value={data.provider}
                                                onChange={(e) => setData('provider', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#439454] focus:ring-[#439454] sm:text-sm"
                                            />
                                            {errors.provider && <div className="text-red-500 text-sm mt-1">{errors.provider}</div>}
                                        </div>

                                    </div>
                                </div>

                                {/* Form Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                    <Link
                                        href={route('employees.index')}
                                        className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <X className="w-4 h-4 mr-2" />
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 bg-[#439454] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#367a41] active:bg-[#2d5d33] focus:outline-none focus:ring-2 focus:ring-[#439454] focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        <Save className="w-4 h-4 mr-2" />
                                        {processing ? 'Menyimpan...' : 'Update Karyawan'}
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
