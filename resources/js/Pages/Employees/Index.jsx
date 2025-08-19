// ============================================================================
// EMPLOYEE INDEX - BASIC TESTING VERSION
// ============================================================================
// Simple version untuk test navigation dan basic functionality

import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    Users,
    Plus,
    Search,
    Building2,
    Calendar,
    UserCheck
} from 'lucide-react';
import DeleteButton from '@/Components/DeleteButton';

export default function EmployeesIndex({
    employees = { data: [] },
    statistics = {},
    title = "Data Karyawan",
    subtitle = "Kelola data kepegawaian sistem training GAPURA",
    auth,
    error = null,
}) {
    const [searchQuery, setSearchQuery] = useState('');

    // Default statistics jika tidak ada data
    const stats = {
        total: statistics?.total || 0,
        departments: statistics?.departments || 0,
        this_month: statistics?.this_month || 0,
        units: statistics?.units || 0,
        ...statistics
    };

    return (
        <DashboardLayout auth={auth} header={title}>
            <Head title={title} />

            <div className="p-6 space-y-6">
                {/* Error Message */}
                {error && (
                    <div className="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div className="flex items-center gap-2 text-red-800">
                            <div className="w-2 h-2 bg-red-500 rounded-full"></div>
                            <p className="font-medium">{error}</p>
                        </div>
                    </div>
                )}

                {/* Header Section */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                        <p className="text-sm text-gray-600 mt-1">{subtitle}</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href="/employees/create"
                            className="flex items-center gap-2 px-4 py-2 bg-[#439454] text-white rounded-xl hover:bg-[#358945] transition-all duration-300 shadow-lg hover:shadow-xl font-medium"
                        >
                            <Plus className="w-4 h-4" />
                            Tambah Karyawan
                        </Link>

                        <button
                            onClick={() => alert('Export functionality coming soon!')}
                            className="flex items-center gap-2 px-4 py-2 border-2 border-[#439454] text-[#439454] rounded-xl hover:bg-[#439454] hover:text-white transition-all duration-300 font-medium"
                        >
                            Export Data
                        </button>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-gradient-to-br from-[#439454] to-[#358945] rounded-2xl p-6 text-white shadow-lg">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-2xl font-bold">{stats.total}</h3>
                                <p className="text-green-100">Total Karyawan</p>
                            </div>
                            <Users className="w-8 h-8 text-green-200" />
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-2xl font-bold text-gray-900">{stats.departments}</h3>
                                <p className="text-gray-600">Departemen</p>
                            </div>
                            <Building2 className="w-8 h-8 text-[#439454]" />
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-2xl font-bold text-gray-900">{stats.this_month}</h3>
                                <p className="text-gray-600">Baru Bulan Ini</p>
                            </div>
                            <Calendar className="w-8 h-8 text-blue-500" />
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-2xl font-bold text-gray-900">{stats.units}</h3>
                                <p className="text-gray-600">Unit Organisasi</p>
                            </div>
                            <UserCheck className="w-8 h-8 text-purple-500" />
                        </div>
                    </div>
                </div>

                {/* Main Content Card */}
                <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    {/* Search Header */}
                    <div className="p-6 border-b border-gray-100 bg-gray-50">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            {/* Search */}
                            <div className="relative flex-1 max-w-md">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <input
                                    type="text"
                                    placeholder="Cari nama, NIP, atau NIK..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                />
                            </div>

                            <div className="text-sm text-gray-600">
                                Total: {employees?.data?.length || 0} karyawan
                            </div>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-6">
                        {employees?.data?.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Karyawan
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                NIP
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Department
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {employees.data.map((employee, index) => (
                                            <tr key={employee.id || index} className="hover:bg-gray-50 transition-colors duration-200">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 bg-gradient-to-br from-[#439454] to-[#358945] rounded-full flex items-center justify-center text-white font-medium">
                                                            {employee.nama_lengkap?.charAt(0).toUpperCase() || 'N'}
                                                        </div>
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {employee.nama_lengkap || 'Nama tidak tersedia'}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {employee.jabatan || '-'}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">
                                                    {employee.nip || '-'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#439454] bg-opacity-10 text-[#439454]">
                                                        {employee.department || 'Unknown'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {employee.is_active ? 'Aktif' : 'Tidak Aktif'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <Link
                                                            href={`/employees/${employee.id}`}
                                                            className="text-[#439454] hover:text-[#358945] font-medium text-sm"
                                                        >
                                                            View
                                                        </Link>
                                                        <span className="text-gray-300">|</span>
                                                        <Link
                                                            href={`/employees/${employee.id}/edit`}
                                                            className="text-blue-600 hover:text-blue-500 font-medium text-sm"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <span className="text-gray-300">|</span>
                                                        <DeleteButton employee={employee} />
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            // Empty State
                            <div className="text-center py-12">
                                <div className="flex flex-col items-center gap-4">
                                    <Users className="w-16 h-16 text-gray-300" />
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">Tidak ada data karyawan</h3>
                                        <p className="text-gray-500 mb-6">
                                            Belum ada karyawan yang terdaftar dalam sistem
                                        </p>
                                        <Link
                                            href="/employees/create"
                                            className="inline-flex items-center gap-2 px-4 py-2 bg-[#439454] text-white rounded-lg hover:bg-[#358945] transition-colors duration-200"
                                        >
                                            <Plus className="w-4 h-4" />
                                            Tambah Karyawan Pertama
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Development Notice */}
                <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div className="flex items-center gap-2 text-blue-800">
                        <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <p className="font-medium">Phase 1 Development</p>
                    </div>
                    <p className="text-blue-700 text-sm mt-1">
                        Employee CRUD system is under development.
                        Current status: Navigation ✅ | Basic UI ✅ | Backend Integration 🔄
                    </p>
                </div>
            </div>
        </DashboardLayout>
    );
}
