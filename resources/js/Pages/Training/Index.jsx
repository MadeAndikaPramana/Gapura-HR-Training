import React, { useState, useEffect, useMemo } from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import TrainingDetailModal from "@/Components/TrainingDetailModal";
import {
    Search,
    Plus,
    FileDown,
    FileUp,
    Eye,
    Edit,
    Trash2,
    X,
    Filter,
    Award,
    AlertTriangle,
    Clock,
    Calendar,
    ChevronDown,
    ChevronUp,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Star,
    BookOpen,
    CheckCircle,
    XCircle,
    AlertCircle,
} from "lucide-react";

export default function Index({
    trainingRecords = { data: [] },
    pagination = {},
    filters = {},
    filterOptions = {},
    statistics = {},
    notifications = {},
    newTraining = null,
    success = null,
    error = null,
    message = null,
    notification = null,
    alerts = [],
    title = "Training Records Management",
    subtitle = "Kelola data pelatihan dan sertifikasi karyawan PT Gapura Angkasa",
    auth,
}) {
    // State management
    const [searchQuery, setSearchQuery] = useState(filters.search || "");
    const [trainingTypeFilter, setTrainingTypeFilter] = useState(
        filters.training_type || "all"
    );
    const [statusFilter, setStatusFilter] = useState(
        filters.status || "all"
    );
    const [employeeFilter, setEmployeeFilter] = useState(
        filters.employee || "all"
    );
    const [expiryFilter, setExpiryFilter] = useState(
        filters.expiry || "all"
    );
    const [departmentFilter, setDepartmentFilter] = useState(
        filters.department || "all"
    );
    const [perPage, setPerPage] = useState(pagination.per_page || 20);
    const [showFilters, setShowFilters] = useState(false);
    const [showTrainingModal, setShowTrainingModal] = useState(false);
    const [selectedTraining, setSelectedTraining] = useState(null);
    const [loading, setLoading] = useState(false);
    const [isNavigating, setIsNavigating] = useState(false);

    // Debounced search
    const [searchTimeout, setSearchTimeout] = useState(null);

    const [sortField, setSortField] = useState(filters.sort || "expiry_date");
    const [sortDirection, setSortDirection] = useState(filters.direction || "asc");

    // Training statistics calculation
    const trainingStats = useMemo(() => {
        const data = trainingRecords.data || [];
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const validCerts = data.filter(record => {
            const expiryDate = new Date(record.expiry_date);
            return expiryDate > today;
        }).length;

        const expiredCerts = data.filter(record => {
            const expiryDate = new Date(record.expiry_date);
            return expiryDate <= today;
        }).length;

        const dueSoon = data.filter(record => {
            const expiryDate = new Date(record.expiry_date);
            const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
            return daysUntilExpiry > 0 && daysUntilExpiry <= 30;
        }).length;

        return {
            total: statistics.total || data.length,
            valid: validCerts,
            expired: expiredCerts,
            dueSoon: dueSoon,
            uniqueTrainingTypes: statistics.uniqueTrainingTypes || 0,
            complianceRate: data.length > 0 ? ((validCerts / data.length) * 100).toFixed(1) : 0,
        };
    }, [statistics, trainingRecords.data]);

    // Handle search with debounce
    const handleSearch = (value) => {
        setSearchQuery(value);

        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        const timeout = setTimeout(() => {
            applyFilters({ search: value });
        }, 500);

        setSearchTimeout(timeout);
    };

    // Apply filters and navigate
    const applyFilters = (newFilters = {}) => {
        const params = {
            search: searchQuery,
            training_type: trainingTypeFilter,
            status: statusFilter,
            employee: employeeFilter,
            expiry: expiryFilter,
            department: departmentFilter,
            per_page: perPage,
            sort: sortField,
            direction: sortDirection,
            ...newFilters,
        };

        // Remove empty filters
        Object.keys(params).forEach(key => {
            if (!params[key] || params[key] === 'all') {
                delete params[key];
            }
        });

        setLoading(true);
        router.get(route('training.index'), params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setLoading(false),
        });
    };

    // Handle sort
    const handleSort = (field) => {
        const newDirection = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(newDirection);
        applyFilters({ sort: field, direction: newDirection });
    };

    // Reset filters
    const resetFilters = () => {
        setSearchQuery("");
        setTrainingTypeFilter("all");
        setStatusFilter("all");
        setEmployeeFilter("all");
        setExpiryFilter("all");
        setDepartmentFilter("all");
        setSortField("expiry_date");
        setSortDirection("asc");

        router.get(route('training.index'), { per_page: perPage });
    };

    // Handle per page change
    const handlePerPageChange = (newPerPage) => {
        setPerPage(newPerPage);
        applyFilters({ per_page: newPerPage });
    };

    // Handle training detail view
    const handleViewTraining = (training) => {
        setSelectedTraining(training);
        setShowTrainingModal(true);
    };

    // Handle training edit
    const handleEditTraining = (trainingId) => {
        setIsNavigating(true);
        router.get(route('training.edit', trainingId));
    };

    // Handle training delete
    const handleDeleteTraining = (training) => {
        if (confirm(`Yakin ingin menghapus training record untuk ${training.employee?.nama_lengkap}?`)) {
            router.delete(route('training.destroy', training.id), {
                preserveScroll: true,
                onSuccess: () => {
                    // Success handled by flash messages
                },
            });
        }
    };

    // Get status badge style
    const getStatusBadge = (training) => {
        const today = new Date();
        const expiryDate = new Date(training.expiry_date);
        const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

        if (daysUntilExpiry < 0) {
            return {
                text: "Expired",
                icon: XCircle,
                class: "bg-red-100 text-red-800"
            };
        } else if (daysUntilExpiry <= 30) {
            return {
                text: "Due Soon",
                icon: AlertTriangle,
                class: "bg-yellow-100 text-yellow-800"
            };
        } else {
            return {
                text: "Valid",
                icon: CheckCircle,
                class: "bg-green-100 text-green-800"
            };
        }
    };

    // Format date
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    return (
        <DashboardLayout title={title}>
            <Head title="Training Records - GAPURA ANGKASA Training System">
                <style>{`
                    .profile-clickable {
                        cursor: pointer;
                        transition: all 0.3s ease;
                        border-radius: 6px;
                        padding: 2px 4px;
                    }

                    .profile-clickable:hover {
                        background-color: rgba(67, 148, 84, 0.1);
                        color: #439454;
                        transform: scale(1.02);
                    }

                    .text-2xs {
                        font-size: 0.625rem;
                        line-height: 0.75rem;
                    }

                    select option {
                        background-color: white;
                        color: #374151;
                        padding: 8px 12px;
                    }

                    select option:hover {
                        background-color: #439454 !important;
                        color: white !important;
                    }
                `}</style>
            </Head>

            {/* Page Header */}
            <div className="mb-8">
                <div className="md:flex md:items-center md:justify-between">
                    <div className="min-w-0 flex-1">
                        <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                            {title}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {subtitle}
                        </p>
                    </div>
                    <div className="mt-4 flex md:ml-4 md:mt-0">
                        <Link
                            href={route('training.create')}
                            className="inline-flex items-center gap-2 rounded-xl bg-[#439454] px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-[#358945] focus:ring-4 focus:ring-[#439454]/20 transition-all duration-300"
                        >
                            <Plus className="h-4 w-4" />
                            Add Training Record
                        </Link>
                    </div>
                </div>
            </div>

            {/* Statistics Cards */}
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                {/* Total Records */}
                <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                    <div className="p-5">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <BookOpen className="h-6 w-6 text-blue-600" />
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">
                                        Total Records
                                    </dt>
                                    <dd className="text-lg font-semibold text-gray-900">
                                        {trainingStats.total.toLocaleString()}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Valid Certificates */}
                <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                    <div className="p-5">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <CheckCircle className="h-6 w-6 text-green-600" />
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">
                                        Valid Certificates
                                    </dt>
                                    <dd className="text-lg font-semibold text-green-600">
                                        {trainingStats.valid.toLocaleString()}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Due Soon */}
                <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                    <div className="p-5">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <AlertTriangle className="h-6 w-6 text-yellow-600" />
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">
                                        Due Soon (30 days)
                                    </dt>
                                    <dd className="text-lg font-semibold text-yellow-600">
                                        {trainingStats.dueSoon.toLocaleString()}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Expired */}
                <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                    <div className="p-5">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <XCircle className="h-6 w-6 text-red-600" />
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">
                                        Expired
                                    </dt>
                                    <dd className="text-lg font-semibold text-red-600">
                                        {trainingStats.expired.toLocaleString()}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Search and Filters */}
            <div className="bg-white shadow-lg rounded-xl border border-gray-100 mb-6">
                <div className="p-6">
                    {/* Search Bar */}
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="flex-1 max-w-lg">
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Search className="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-[#439454] focus:border-[#439454] transition-all duration-300"
                                    placeholder="Search training records, employee names, certificate numbers..."
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <button
                                onClick={() => setShowFilters(!showFilters)}
                                className="inline-flex items-center gap-2 px-4 py-3 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-[#439454] transition-all duration-300"
                            >
                                <Filter className="h-4 w-4" />
                                Filters
                                {showFilters ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                            </button>

                            <button
                                onClick={resetFilters}
                                className="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 transition-all duration-300"
                            >
                                <X className="h-4 w-4" />
                                Reset
                            </button>
                        </div>
                    </div>

                    {/* Filter Options */}
                    {showFilters && (
                        <div className="mt-6 pt-6 border-t border-gray-200">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {/* Training Type Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Training Type
                                    </label>
                                    <select
                                        value={trainingTypeFilter}
                                        onChange={(e) => setTrainingTypeFilter(e.target.value)}
                                        className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    >
                                        <option value="all">All Training Types</option>
                                        {filterOptions.trainingTypes?.map((type) => (
                                            <option key={type.id} value={type.id}>
                                                {type.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        value={statusFilter}
                                        onChange={(e) => setStatusFilter(e.target.value)}
                                        className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    >
                                        <option value="all">All Status</option>
                                        <option value="valid">Valid</option>
                                        <option value="due_soon">Due Soon</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>

                                {/* Expiry Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Expiry Period
                                    </label>
                                    <select
                                        value={expiryFilter}
                                        onChange={(e) => setExpiryFilter(e.target.value)}
                                        className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    >
                                        <option value="all">All Periods</option>
                                        <option value="this_month">This Month</option>
                                        <option value="next_30_days">Next 30 Days</option>
                                        <option value="next_90_days">Next 90 Days</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                            </div>

                            <div className="mt-4 flex justify-end">
                                <button
                                    onClick={() => applyFilters()}
                                    className="inline-flex items-center gap-2 px-6 py-2 bg-[#439454] text-white rounded-lg hover:bg-[#358945] focus:ring-2 focus:ring-[#439454] transition-all duration-300"
                                >
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Training Records Table */}
            <div className="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th
                                    onClick={() => handleSort('employee_name')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                >
                                    <div className="flex items-center gap-1">
                                        Employee
                                        {sortField === 'employee_name' && (
                                            sortDirection === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                                        )}
                                    </div>
                                </th>
                                <th
                                    onClick={() => handleSort('training_type')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                >
                                    <div className="flex items-center gap-1">
                                        Training Type
                                        {sortField === 'training_type' && (
                                            sortDirection === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                                        )}
                                    </div>
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Certificate
                                </th>
                                <th
                                    onClick={() => handleSort('issue_date')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                >
                                    <div className="flex items-center gap-1">
                                        Issue Date
                                        {sortField === 'issue_date' && (
                                            sortDirection === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                                        )}
                                    </div>
                                </th>
                                <th
                                    onClick={() => handleSort('expiry_date')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                >
                                    <div className="flex items-center gap-1">
                                        Expiry Date
                                        {sortField === 'expiry_date' && (
                                            sortDirection === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                                        )}
                                    </div>
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {trainingRecords.data?.length > 0 ? (
                                trainingRecords.data.map((training) => {
                                    const statusBadge = getStatusBadge(training);
                                    const StatusIcon = statusBadge.icon;

                                    return (
                                        <tr key={training.id} className="hover:bg-gray-50 transition-colors duration-200">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div>
                                                        <div
                                                            className="text-sm font-medium text-gray-900 profile-clickable"
                                                            onClick={() => handleViewTraining(training)}
                                                        >
                                                            {training.employee?.nama_lengkap || 'N/A'}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {training.employee?.nip || 'N/A'}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {training.training_type?.name || 'N/A'}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {training.training_type?.category || 'N/A'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">
                                                    {training.certificate_number || 'N/A'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {formatDate(training.issue_date)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {formatDate(training.expiry_date)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${statusBadge.class}`}>
                                                    <StatusIcon className="h-3 w-3" />
                                                    {statusBadge.text}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div className="flex items-center justify-center gap-2">
                                                    <button
                                                        onClick={() => handleViewTraining(training)}
                                                        className="text-[#439454] hover:text-[#358945] transition-colors duration-200"
                                                        title="View Details"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleEditTraining(training.id)}
                                                        className="text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                                        title="Edit"
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeleteTraining(training)}
                                                        className="text-red-600 hover:text-red-800 transition-colors duration-200"
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan="7" className="px-6 py-12 text-center">
                                        <div className="flex flex-col items-center">
                                            <BookOpen className="h-12 w-12 text-gray-400 mb-4" />
                                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                                No training records found
                                            </h3>
                                            <p className="text-gray-500 mb-6">
                                                Get started by adding your first training record.
                                            </p>
                                            <Link
                                                href={route('training.create')}
                                                className="inline-flex items-center gap-2 px-4 py-2 bg-[#439454] text-white rounded-lg hover:bg-[#358945] transition-all duration-300"
                                            >
                                                <Plus className="h-4 w-4" />
                                                Add Training Record
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {trainingRecords.data?.length > 0 && (
                    <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing {pagination.from || 0} to {pagination.to || 0} of {pagination.total || 0} results
                                </div>

                                <div className="flex items-center gap-2">
                                    <label className="text-sm text-gray-700">Per page:</label>
                                    <select
                                        value={perPage}
                                        onChange={(e) => handlePerPageChange(parseInt(e.target.value))}
                                        className="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-[#439454]"
                                    >
                                        <option value={10}>10</option>
                                        <option value={20}>20</option>
                                        <option value={50}>50</option>
                                        <option value={100}>100</option>
                                    </select>
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                {/* First Page */}
                                <Link
                                    href={pagination.first_page_url}
                                    className={`p-2 rounded-lg ${!pagination.first_page_url ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'}`}
                                    preserveState
                                    preserveScroll
                                >
                                    <ChevronsLeft className="h-4 w-4" />
                                </Link>

                                {/* Previous Page */}
                                <Link
                                    href={pagination.prev_page_url}
                                    className={`p-2 rounded-lg ${!pagination.prev_page_url ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'}`}
                                    preserveState
                                    preserveScroll
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Link>

                                {/* Page Numbers */}
                                <span className="px-3 py-2 text-sm text-gray-700">
                                    Page {pagination.current_page || 1} of {pagination.last_page || 1}
                                </span>

                                {/* Next Page */}
                                <Link
                                    href={pagination.next_page_url}
                                    className={`p-2 rounded-lg ${!pagination.next_page_url ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'}`}
                                    preserveState
                                    preserveScroll
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Link>

                                {/* Last Page */}
                                <Link
                                    href={pagination.last_page_url}
                                    className={`p-2 rounded-lg ${!pagination.last_page_url ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'}`}
                                    preserveState
                                    preserveScroll
                                >
                                    <ChevronsRight className="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Training Detail Modal */}
            {showTrainingModal && selectedTraining && (
                <TrainingDetailModal
                    training={selectedTraining}
                    isOpen={showTrainingModal}
                    onClose={() => setShowTrainingModal(false)}
                />
            )}
        </DashboardLayout>
    );
}
