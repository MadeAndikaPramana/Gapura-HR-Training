// ============================================================================
// TRAINING EMPLOYEES PAGE - KONSISTEN DENGAN UI FIRMAN HR GAPURA
// ============================================================================

import React, { useState, useEffect } from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    Search,
    Users,
    Building2,
    Filter,
    FileDown,
    Eye,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    UserCheck,
    Clock,
    Calendar,
} from "lucide-react";

export default function TrainingEmployees({
    employees = { data: [] },
    departments = {},
    filters = {},
    statistics = {},
    title = "Data Karyawan Training",
    subtitle = "Data karyawan berdasarkan file MPGA - Menampilkan Nama dan NIP",
    auth,
}) {
    // State management (sama seperti Employee Index di Firman)
    const [searchQuery, setSearchQuery] = useState(filters.search || "");
    const [departmentFilter, setDepartmentFilter] = useState(filters.department || "all");
    const [showFilters, setShowFilters] = useState(false);
    const [loading, setLoading] = useState(false);

    // Debounced search
    const [searchTimeout, setSearchTimeout] = useState(null);

    useEffect(() => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        const timeout = setTimeout(() => {
            if (searchQuery !== filters.search) {
                handleSearch();
            }
        }, 500);

        setSearchTimeout(timeout);

        return () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
        };
    }, [searchQuery]);

    const handleSearch = () => {
        setLoading(true);
        router.get(route('training.employees'), {
            search: searchQuery,
            department: departmentFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setLoading(false),
        });
    };

    const handleFilterChange = () => {
        setLoading(true);
        router.get(route('training.employees'), {
            search: searchQuery,
            department: departmentFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setLoading(false),
        });
    };

    const clearFilters = () => {
        setSearchQuery("");
        setDepartmentFilter("all");
        setLoading(true);
        router.get(route('training.employees'), {}, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setLoading(false),
        });
    };

    // Pagination helper (sama seperti Firman)
    const renderPagination = () => {
        if (!employees.last_page || employees.last_page <= 1) return null;

        return (
            <div className="flex items-center justify-between px-6 py-4 bg-white border-t border-gray-200">
                <div className="flex items-center text-sm text-gray-600">
                    Menampilkan {employees.from || 0} - {employees.to || 0} dari {employees.total || 0} karyawan
                </div>

                <div className="flex items-center gap-2">
                    {/* First Page */}
                    <button
                        onClick={() => router.get(employees.first_page_url)}
                        disabled={employees.current_page === 1}
                        className="p-2 text-gray-400 hover:text-[#439454] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronsLeft className="w-4 h-4" />
                    </button>

                    {/* Previous Page */}
                    <button
                        onClick={() => router.get(employees.prev_page_url)}
                        disabled={!employees.prev_page_url}
                        className="p-2 text-gray-400 hover:text-[#439454] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronLeft className="w-4 h-4" />
                    </button>

                    {/* Page Numbers */}
                    <span className="px-3 py-1 text-sm font-medium text-gray-700">
                        {employees.current_page} dari {employees.last_page}
                    </span>

                    {/* Next Page */}
                    <button
                        onClick={() => router.get(employees.next_page_url)}
                        disabled={!employees.next_page_url}
                        className="p-2 text-gray-400 hover:text-[#439454] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronRight className="w-4 h-4" />
                    </button>

                    {/* Last Page */}
                    <button
                        onClick={() => router.get(employees.last_page_url)}
                        disabled={employees.current_page === employees.last_page}
                        className="p-2 text-gray-400 hover:text-[#439454] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronsRight className="w-4 h-4" />
                    </button>
                </div>
            </div>
        );
    };

    return (
        <DashboardLayout>
            <Head title={title} />

            <div className="p-6 space-y-6">
                {/* Header Section - Style Firman */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                        <p className="text-sm text-gray-600 mt-1">{subtitle}</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={route('training.index')}
                            className="flex items-center gap-2 px-4 py-2 bg-[#439454] text-white rounded-xl hover:bg-[#358945] transition-all duration-300 shadow-lg hover:shadow-gapura font-medium"
                        >
                            <Eye className="w-4 h-4" />
                            Lihat Training Records
                        </Link>

                        <button
                            onClick={() => window.print()}
                            className="flex items-center gap-2 px-4 py-2 border-2 border-[#439454] text-[#439454] rounded-xl hover:bg-[#439454] hover:text-white transition-all duration-300 font-medium"
                        >
                            <FileDown className="w-4 h-4" />
                            Export Data
                        </button>
                    </div>
                </div>

                {/* Statistics Cards - Style Firman */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl border border-blue-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-blue-500 text-white rounded-xl">
                                <Users className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">{employees.total || 0}</h3>
                                <p className="text-sm text-gray-600">Total Karyawan</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-2xl border border-green-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-[#439454] text-white rounded-xl">
                                <Building2 className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">{Object.keys(departments).length}</h3>
                                <p className="text-sm text-gray-600">Department</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-2xl border border-purple-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-purple-500 text-white rounded-xl">
                                <UserCheck className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">{statistics.active_employees || 0}</h3>
                                <p className="text-sm text-gray-600">Karyawan Aktif</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-2xl border border-orange-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-orange-500 text-white rounded-xl">
                                <Calendar className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">
                                    {new Date().toLocaleDateString('id-ID', {
                                        year: 'numeric',
                                        month: 'long'
                                    })}
                                </h3>
                                <p className="text-sm text-gray-600">Data MPGA</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content Card - Style Firman */}
                <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    {/* Filters Header */}
                    <div className="p-6 border-b border-gray-100 bg-gray-50">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            {/* Search */}
                            <div className="relative flex-1 max-w-md">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <input
                                    type="text"
                                    placeholder="Cari nama atau NIP karyawan..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                />
                            </div>

                            {/* Filter Button */}
                            <div className="flex items-center gap-3">
                                <button
                                    onClick={() => setShowFilters(!showFilters)}
                                    className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 transition-all duration-300 font-medium ${
                                        showFilters
                                            ? 'border-[#439454] bg-[#439454] text-white'
                                            : 'border-gray-300 text-gray-700 hover:border-[#439454] hover:text-[#439454]'
                                    }`}
                                >
                                    <Filter className="w-4 h-4" />
                                    Filter
                                    <ChevronDown className={`w-4 h-4 transition-transform duration-300 ${showFilters ? 'rotate-180' : ''}`} />
                                </button>

                                {(departmentFilter !== 'all' || searchQuery) && (
                                    <button
                                        onClick={clearFilters}
                                        className="px-4 py-2.5 text-sm text-gray-600 hover:text-[#439454] transition-colors duration-300"
                                    >
                                        Reset Filter
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Extended Filters */}
                        {showFilters && (
                            <div className="mt-4 p-4 bg-white rounded-xl border border-gray-200">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    {/* Department Filter */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Department
                                        </label>
                                        <select
                                            value={departmentFilter}
                                            onChange={(e) => setDepartmentFilter(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-transparent"
                                        >
                                            <option value="all">Semua Department</option>
                                            {Object.entries(departments).map(([key, name]) => (
                                                <option key={key} value={key}>
                                                    {name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="md:col-span-2 flex items-end">
                                        <button
                                            onClick={handleFilterChange}
                                            disabled={loading}
                                            className="w-full px-4 py-2 bg-[#439454] text-white rounded-lg hover:bg-[#358945] transition-colors duration-300 disabled:opacity-50"
                                        >
                                            {loading ? 'Memuat...' : 'Terapkan Filter'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Table Content */}
                    <div className="overflow-hidden">
                        {loading ? (
                            <div className="p-12 text-center">
                                <div className="inline-block w-8 h-8 border-4 border-[#439454] border-t-transparent rounded-full animate-spin"></div>
                                <p className="mt-4 text-gray-600">Memuat data karyawan...</p>
                            </div>
                        ) : employees.data && employees.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                No
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                Nama Lengkap
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                NIP
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                Unit Kerja
                                            </th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                Department
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {employees.data.map((employee, index) => (
                                            <tr
                                                key={employee.id}
                                                className="hover:bg-gray-50 transition-colors duration-200"
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {(employees.current_page - 1) * employees.per_page + index + 1}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="w-10 h-10 bg-gradient-to-br from-[#439454] to-[#358945] rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                                            {employee.nama_lengkap?.charAt(0)?.toUpperCase() || 'N'}
                                                        </div>
                                                        <div className="ml-3">
                                                            <div className="text-sm font-semibold text-gray-900">
                                                                {employee.nama_lengkap}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {employee.nip}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {employee.unit_kerja || '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-[#439454]/10 text-[#439454]">
                                                        {departments[employee.department] || employee.department}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="p-12 text-center">
                                <Users className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    Tidak ada data karyawan
                                </h3>
                                <p className="text-gray-600 mb-6">
                                    {searchQuery || departmentFilter !== 'all'
                                        ? 'Tidak ada karyawan yang sesuai dengan filter yang dipilih.'
                                        : 'Belum ada data karyawan. Import data MPGA terlebih dahulu.'}
                                </p>

                                {!searchQuery && departmentFilter === 'all' && (
                                    <Link
                                        href={route('training.import')}
                                        className="inline-flex items-center gap-2 px-6 py-3 bg-[#439454] text-white rounded-xl hover:bg-[#358945] transition-all duration-300 font-medium"
                                    >
                                        <FileDown className="w-4 h-4" />
                                        Import Data MPGA
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {renderPagination()}
                </div>

                {/* Info Note - Data MPGA */}
                <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div className="flex items-start gap-3">
                        <div className="w-5 h-5 text-blue-500 mt-0.5">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h4 className="font-medium text-blue-900 mb-1">Informasi Data MPGA</h4>
                            <p className="text-sm text-blue-700">
                                Data karyawan ini diambil dari file MPGA Excel dengan struktur: <strong>Nama Lengkap</strong> dan <strong>NIP (NIPP)</strong>.
                                Data NIK tidak tersedia dalam file MPGA original. Untuk melihat detail training dan sertifikasi, klik "Lihat Training Records".
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
