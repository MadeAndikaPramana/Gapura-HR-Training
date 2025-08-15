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

export default function EmployeesIndex({
    employees = { data: [] },
    statistics = {},
    filterOptions = {},
    filters = {}, // Default empty object to prevent undefined errors
    auth,
    title = "Management Karyawan",
    subtitle = "Kelola data karyawan PT Gapura Angkasa"
}) {
    // Safe state initialization with default values
    const [search, setSearch] = useState(filters?.search || '');
    const [unitFilter, setUnitFilter] = useState(filters?.unit_organisasi || 'all');
    const [statusFilter, setStatusFilter] = useState(filters?.status_pegawai || 'all');
    const [statusKerjaFilter, setStatusKerjaFilter] = useState(filters?.status_kerja || 'all');
    const [departmentFilter, setDepartmentFilter] = useState(filters?.department || 'all');
    const [showFilters, setShowFilters] = useState(false);

    // Handle search with debounce
    useEffect(() => {
        const delayedSearch = setTimeout(() => {
            const searchParams = {
                search,
                unit_organisasi: unitFilter !== 'all' ? unitFilter : undefined,
                status_pegawai: statusFilter !== 'all' ? statusFilter : undefined,
                status_kerja: statusKerjaFilter !== 'all' ? statusKerjaFilter : undefined,
                department: departmentFilter !== 'all' ? departmentFilter : undefined,
            };

            // Remove undefined values
            Object.keys(searchParams).forEach(key =>
                searchParams[key] === undefined && delete searchParams[key]
            );

            router.get(route('employees.index'), searchParams, {
                preserveState: true,
                replace: true
            });
        }, 300);

        return () => clearTimeout(delayedSearch);
    }, [search, unitFilter, statusFilter, statusKerjaFilter, departmentFilter]);

    const handleDeleteEmployee = (employee) => {
        if (confirm(`Apakah Anda yakin ingin menghapus data ${employee.nama_lengkap}?`)) {
            router.delete(route('employees.destroy', employee.id), {
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
        setDepartmentFilter('all');
    };

    // Safe access to filter options with defaults
    const safeFilterOptions = {
        departments: filterOptions?.departments || ['DEDICATED', 'LOADING', 'RAMP', 'LOCO', 'ULD', 'LOST & FOUND', 'CARGO', 'ARRIVAL', 'GSE OPERATOR', 'FLOP', 'AVSEC', 'PORTER'],
        units: filterOptions?.units || [],
        statusPegawai: filterOptions?.statusPegawai || ['PEGAWAI TETAP', 'PKWT', 'TAD PAKET SDM', 'TAD PAKET PEKERJAAN'],
        statusKerja: filterOptions?.statusKerja || ['Aktif', 'Non-Aktif', 'Cuti', 'Pensiun']
    };

    // Safe access to statistics with defaults
    const safeStatistics = {
        total_employees: statistics?.total_employees || 0,
        total_departments: statistics?.total_departments || 0,
        active_employees: statistics?.active_employees || 0,
        ...statistics
    };

    // Safe access to employees data
    const safeEmployees = {
        data: employees?.data || [],
        total: employees?.total || 0,
        current_page: employees?.current_page || 1,
        last_page: employees?.last_page || 1,
        per_page: employees?.per_page || 20,
        from: employees?.from || 0,
        to: employees?.to || 0,
        ...employees
    };

    return (
        <DashboardLayout
            user={auth?.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {title}
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">{subtitle}</p>
                    </div>
                    <Link
                        href={route('employees.create')}
                        className="inline-flex items-center px-4 py-2 bg-[#439454] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#367a41] active:bg-[#2d5d33] focus:outline-none focus:ring-2 focus:ring-[#439454] focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <Plus className="w-4 h-4 mr-2" />
                        Tambah Karyawan
                    </Link>
                </div>
            }
        >
            <Head title={title} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-[#439454]" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Karyawan
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {safeStatistics.total_employees}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Building className="h-8 w-8 text-blue-500" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Departemen
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {safeFilterOptions.departments.length}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UserCheck className="h-8 w-8 text-green-500" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Karyawan Aktif
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {safeStatistics.active_employees || safeStatistics.total_employees}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
                        <div className="p-6">
                            {/* Search Bar */}
                            <div className="flex flex-col sm:flex-row gap-4 mb-4">
                                <div className="flex-1">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                        <input
                                            type="text"
                                            placeholder="Cari nama, NIP, atau NIK..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-[#439454] focus:border-[#439454] sm:text-sm"
                                        />
                                    </div>
                                </div>
                                <button
                                    onClick={() => setShowFilters(!showFilters)}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#439454] focus:ring-offset-2"
                                >
                                    <Filter className="w-4 h-4 mr-2" />
                                    Filter
                                    {showFilters ? <ChevronUp className="w-4 h-4 ml-2" /> : <ChevronDown className="w-4 h-4 ml-2" />}
                                </button>
                                {(search || unitFilter !== 'all' || statusFilter !== 'all' || statusKerjaFilter !== 'all' || departmentFilter !== 'all') && (
                                    <button
                                        onClick={clearFilters}
                                        className="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                    >
                                        <X className="w-4 h-4 mr-2" />
                                        Clear
                                    </button>
                                )}
                            </div>

                            {/* Filter Options */}
                            {showFilters && (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Department
                                        </label>
                                        <select
                                            value={departmentFilter}
                                            onChange={(e) => setDepartmentFilter(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm focus:ring-[#439454] focus:border-[#439454] sm:text-sm"
                                        >
                                            <option value="all">Semua Department</option>
                                            {safeFilterOptions.departments.map((dept) => (
                                                <option key={dept} value={dept}>
                                                    {dept}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Unit Organisasi
                                        </label>
                                        <select
                                            value={unitFilter}
                                            onChange={(e) => setUnitFilter(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm focus:ring-[#439454] focus:border-[#439454] sm:text-sm"
                                        >
                                            <option value="all">Semua Unit</option>
                                            {safeFilterOptions.units.map((unit) => (
                                                <option key={unit} value={unit}>
                                                    {unit}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Status Pegawai
                                        </label>
                                        <select
                                            value={statusFilter}
                                            onChange={(e) => setStatusFilter(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm focus:ring-[#439454] focus:border-[#439454] sm:text-sm"
                                        >
                                            <option value="all">Semua Status</option>
                                            {safeFilterOptions.statusPegawai.map((status) => (
                                                <option key={status} value={status}>
                                                    {status}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Status Kerja
                                        </label>
                                        <select
                                            value={statusKerjaFilter}
                                            onChange={(e) => setStatusKerjaFilter(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm focus:ring-[#439454] focus:border-[#439454] sm:text-sm"
                                        >
                                            <option value="all">Semua Status</option>
                                            {safeFilterOptions.statusKerja.map((status) => (
                                                <option key={status} value={status}>
                                                    {status}
                                                </option>
                                            ))}
                                        </select>
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
                                            NIP
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            NIK
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            DEPARTMENT
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
                                    {safeEmployees.data.length > 0 ? (
                                        safeEmployees.data.map((employee, index) => {
                                            const rowNumber = (safeEmployees.current_page - 1) * safeEmployees.per_page + index + 1;

                                            return (
                                                <tr key={employee.id || index} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {employee.nama_lengkap}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {employee.nip}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {employee.nik || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            {employee.department}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {employee.unit_organisasi}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                            employee.status_kerja === 'Aktif'
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}>
                                                            {employee.status_kerja}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex items-center space-x-3">
                                                            <Link
                                                                href={route('employees.show', employee.id)}
                                                                className="text-[#439454] hover:text-[#367a41]"
                                                            >
                                                                <Eye className="w-4 h-4" />
                                                            </Link>
                                                            <Link
                                                                href={route('employees.edit', employee.id)}
                                                                className="text-yellow-600 hover:text-yellow-500"
                                                            >
                                                                <Edit className="w-4 h-4" />
                                                            </Link>
                                                            <button
                                                                onClick={() => handleDeleteEmployee(employee)}
                                                                className="text-red-600 hover:text-red-500"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan="8" className="px-6 py-12 text-center">
                                                <div className="text-gray-500">
                                                    <Users className="w-12 h-12 mx-auto text-gray-300 mb-4" />
                                                    <p className="text-sm">Tidak ada data karyawan</p>
                                                    <p className="text-xs text-gray-400 mt-1">
                                                        Klik "Tambah Karyawan" untuk menambah data
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {safeEmployees.last_page > 1 && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {safeEmployees.prev_page_url && (
                                        <Link
                                            href={safeEmployees.prev_page_url}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    )}
                                    {safeEmployees.next_page_url && (
                                        <Link
                                            href={safeEmployees.next_page_url}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{safeEmployees.from || 0}</span> to{' '}
                                            <span className="font-medium">{safeEmployees.to || 0}</span> of{' '}
                                            <span className="font-medium">{safeEmployees.total || 0}</span> results
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </DashboardLayout>
    );
}
