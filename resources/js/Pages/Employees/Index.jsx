import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    Users,
    UserPlus,
    GraduationCap,
    Search,
    Filter,
    Download,
    Edit,
    Trash2,
    Eye,
    ChevronLeft,
    ChevronRight,
    Building,
    UserCheck
} from 'lucide-react';

export default function EmployeesIndex({ employees, statistics, filterOptions, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [unitFilter, setUnitFilter] = useState(filters.unit_organisasi || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status_pegawai || 'all');
    const [statusKerjaFilter, setStatusKerjaFilter] = useState(filters.status_kerja || 'all');
    const [showFilters, setShowFilters] = useState(false);

    // Handle search with debounce
    useEffect(() => {
        const delayedSearch = setTimeout(() => {
            router.get('/employees', {
                search,
                unit_organisasi: unitFilter,
                status_pegawai: statusFilter,
                status_kerja: statusKerjaFilter
            }, {
                preserveState: true,
                replace: true
            });
        }, 300);

        return () => clearTimeout(delayedSearch);
    }, [search, unitFilter, statusFilter, statusKerjaFilter]);

    const handleDeleteEmployee = (employee) => {
        if (confirm(`Apakah Anda yakin ingin menghapus data ${employee.nama_lengkap}?`)) {
            router.delete(`/employees/${employee.id}`, {
                onSuccess: () => {
                    // Refresh will be handled by Inertia
                }
            });
        }
    };

    const clearFilters = () => {
        setSearch('');
        setUnitFilter('all');
        setStatusFilter('all');
        setStatusKerjaFilter('all');
    };

    return (
        <DashboardLayout title="Management Karyawan">
            <Head title="Management Karyawan" />

            <div className="px-4 sm:px-6 lg:px-8">
                {/* Header Section */}
                <div className="md:flex md:items-center md:justify-between mb-8">
                    <div className="min-w-0 flex-1">
                        <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                            Management Karyawan
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Kelola data karyawan PT Gapura Angkasa - Bandar Udara Ngurah Rai
                        </p>
                    </div>
                    <div className="mt-4 flex md:ml-4 md:mt-0 space-x-3">
                        <Link
                            href="/training"
                            className="inline-flex items-center px-4 py-2 border border-gapura-green text-sm font-medium rounded-md text-gapura-green bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gapura-green transition-colors"
                        >
                            <GraduationCap className="mr-2 h-4 w-4" />
                            Training Records
                        </Link>
                        <Link
                            href="/employees/create"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gapura-green hover:bg-gapura-green-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gapura-green transition-colors"
                        >
                            <UserPlus className="mr-2 h-4 w-4" />
                            Tambah Karyawan
                        </Link>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <Users className="h-6 w-6 text-blue-600" />
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <div className="text-2xl font-bold text-gray-900">
                                        {statistics.total_employees}
                                    </div>
                                    <div className="text-sm text-gray-500">Total Karyawan</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <UserCheck className="h-6 w-6 text-green-600" />
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <div className="text-2xl font-bold text-gray-900">
                                        {statistics.active_employees}
                                    </div>
                                    <div className="text-sm text-gray-500">Karyawan Aktif</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <UserPlus className="h-6 w-6 text-purple-600" />
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <div className="text-2xl font-bold text-gray-900">
                                        {statistics.new_employees}
                                    </div>
                                    <div className="text-sm text-gray-500">Karyawan Baru</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <Building className="h-6 w-6 text-orange-600" />
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <div className="text-2xl font-bold text-gray-900">
                                        {statistics.total_units}
                                    </div>
                                    <div className="text-sm text-gray-500">Unit Organisasi</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Search and Filters */}
                <div className="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
                    <div className="p-6">
                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0 lg:space-x-4">
                            {/* Search */}
                            <div className="flex-1 max-w-lg">
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Search className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-gapura-green focus:border-gapura-green"
                                        placeholder="Cari nama, NIP, atau NIK..."
                                    />
                                </div>
                            </div>

                            {/* Filter Toggle & Export */}
                            <div className="flex items-center space-x-3">
                                <button
                                    onClick={() => setShowFilters(!showFilters)}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gapura-green"
                                >
                                    <Filter className="mr-2 h-4 w-4" />
                                    Filter
                                </button>
                                <button className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gapura-green">
                                    <Download className="mr-2 h-4 w-4" />
                                    Export
                                </button>
                            </div>
                        </div>

                        {/* Filter Options */}
                        {showFilters && (
                            <div className="mt-6 pt-6 border-t border-gray-200">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Unit Organisasi
                                        </label>
                                        <select
                                            value={unitFilter}
                                            onChange={(e) => setUnitFilter(e.target.value)}
                                            className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gapura-green focus:border-gapura-green"
                                        >
                                            <option value="all">Semua Unit</option>
                                            {filterOptions.unit_organisasi.map(unit => (
                                                <option key={unit} value={unit}>{unit}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Status Pegawai
                                        </label>
                                        <select
                                            value={statusFilter}
                                            onChange={(e) => setStatusFilter(e.target.value)}
                                            className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gapura-green focus:border-gapura-green"
                                        >
                                            <option value="all">Semua Status</option>
                                            {filterOptions.status_pegawai.map(status => (
                                                <option key={status} value={status}>{status}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Status Kerja
                                        </label>
                                        <select
                                            value={statusKerjaFilter}
                                            onChange={(e) => setStatusKerjaFilter(e.target.value)}
                                            className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gapura-green focus:border-gapura-green"
                                        >
                                            <option value="all">Semua Status Kerja</option>
                                            {filterOptions.status_kerja.map(status => (
                                                <option key={status} value={status}>{status}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                                <div className="mt-4">
                                    <button
                                        onClick={clearFilters}
                                        className="text-sm text-gapura-green hover:text-gapura-green-dark"
                                    >
                                        Clear All Filters
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Employee Table */}
                <div className="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        NO
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        NAMA LENGKAP
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        NIK
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        NIP
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        UNIT ORGANISASI
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        STATUS
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ACTIONS
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {employees.data.map((employee, index) => (
                                    <tr key={employee.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {employees.from + index}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                <div className="h-8 w-8 bg-gapura-green rounded-full flex items-center justify-center text-white font-semibold text-sm mr-3">
                                                    {employee.nama_lengkap.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {employee.nama_lengkap}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {employee.jabatan}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {employee.nik}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {employee.nip}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {employee.unit_organisasi}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                employee.status_kerja === 'Aktif'
                                                    ? 'bg-green-100 text-green-800'
                                                    : employee.status_pegawai === 'PKWT'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}>
                                                {employee.status_pegawai}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link
                                                href={`/employees/${employee.id}`}
                                                className="text-blue-600 hover:text-blue-800 inline-flex items-center"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                            <Link
                                                href={`/employees/${employee.id}/edit`}
                                                className="text-gapura-green hover:text-gapura-green-dark inline-flex items-center"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Link>
                                            <button
                                                onClick={() => handleDeleteEmployee(employee)}
                                                className="text-red-600 hover:text-red-800 inline-flex items-center"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {employees.last_page > 1 && (
                        <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div className="flex-1 flex justify-between sm:hidden">
                                {employees.prev_page_url && (
                                    <Link
                                        href={employees.prev_page_url}
                                        className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {employees.next_page_url && (
                                    <Link
                                        href={employees.next_page_url}
                                        className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-sm text-gray-700">
                                        Showing <span className="font-medium">{employees.from}</span> to{' '}
                                        <span className="font-medium">{employees.to}</span> of{' '}
                                        <span className="font-medium">{employees.total}</span> results
                                    </p>
                                </div>
                                <div>
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        {employees.prev_page_url && (
                                            <Link
                                                href={employees.prev_page_url}
                                                className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                            >
                                                <ChevronLeft className="h-5 w-5" />
                                            </Link>
                                        )}
                                        <span className="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            Page {employees.current_page} of {employees.last_page}
                                        </span>
                                        {employees.next_page_url && (
                                            <Link
                                                href={employees.next_page_url}
                                                className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                            >
                                                <ChevronRight className="h-5 w-5" />
                                            </Link>
                                        )}
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
