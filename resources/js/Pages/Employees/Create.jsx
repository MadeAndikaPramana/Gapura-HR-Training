import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Create({ auth, departments = [], statusPegawaiOptions = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        nip: '',
        nama_lengkap: '',
        unit_organisasi: '',
        unit_kerja: '',
        department: '',
        status_pegawai: 'PEGAWAI TETAP',
        status_kerja: 'Aktif',
        jenis_kelamin: '',
        email: '',
        handphone: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('employees.store'));
    };

    // Default MPGA departments if not provided from backend
    const mpgaDepartments = departments.length > 0 ? departments : [
        'DEDICATED', 'LOADING', 'RAMP', 'LOCO', 'ULD',
        'LOST & FOUND', 'CARGO', 'ARRIVAL', 'GSE OPERATOR',
        'FLOP', 'AVSEC', 'PORTER'
    ];

    const statusOptions = statusPegawaiOptions.length > 0 ? statusPegawaiOptions : [
        'PEGAWAI TETAP', 'PKWT', 'TAD PAKET SDM', 'TAD PAKET PEKERJAAN'
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Tambah Karyawan</h2>}
        >
            <Head title="Tambah Karyawan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">

                            {/* Form Header */}
                            <div className="mb-6">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Tambah Karyawan Baru
                                </h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    Isi data karyawan sesuai dengan struktur MPGA
                                </p>
                            </div>

                            {/* Form */}
                            <form onSubmit={handleSubmit} className="space-y-6">

                                {/* Basic Info */}
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
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Contoh: 2160800"
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
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Contoh: PUTU EKA RESMAWAN"
                                            required
                                        />
                                        {errors.nama_lengkap && <div className="text-red-500 text-sm mt-1">{errors.nama_lengkap}</div>}
                                    </div>

                                    {/* Unit Organisasi */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Unit Organisasi <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.unit_organisasi}
                                            onChange={(e) => setData('unit_organisasi', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Contoh: AE, Controller, Operations"
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
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Contoh: Account Executive, Flight Controller"
                                            required
                                        />
                                        {errors.unit_kerja && <div className="text-red-500 text-sm mt-1">{errors.unit_kerja}</div>}
                                    </div>

                                    {/* Department */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Department MPGA <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={data.department}
                                            onChange={(e) => setData('department', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    </div>

                                    {/* Status Pegawai */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Status Pegawai <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={data.status_pegawai}
                                            onChange={(e) => setData('status_pegawai', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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

                                    {/* Jenis Kelamin */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Jenis Kelamin
                                        </label>
                                        <select
                                            value={data.jenis_kelamin}
                                            onChange={(e) => setData('jenis_kelamin', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Pilih Jenis Kelamin</option>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                        {errors.jenis_kelamin && <div className="text-red-500 text-sm mt-1">{errors.jenis_kelamin}</div>}
                                    </div>

                                    {/* Email */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="email@gapura.com"
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
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="081234567890"
                                        />
                                        {errors.handphone && <div className="text-red-500 text-sm mt-1">{errors.handphone}</div>}
                                    </div>

                                </div>

                                {/* Form Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-4">
                                    <a
                                        href={route('employees.index')}
                                        className="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                                    >
                                        Batal
                                    </a>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                    >
                                        {processing ? 'Menyimpan...' : 'Simpan Karyawan'}
                                    </button>
                                </div>

                            </form>

                            {/* Debug Info (remove in production) */}
                            <div className="mt-8 p-4 bg-gray-100 rounded text-xs text-gray-600">
                                <strong>Debug Info:</strong>
                                <br />• NIK akan auto-generate saat submit
                                <br />• Department: {data.department || 'Belum dipilih'}
                                <br />• Status: {data.status_pegawai}
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
