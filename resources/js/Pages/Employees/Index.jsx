import React, { useState, useEffect } from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    Search,
    Plus,
    FileDown,
    FileUp,
    Eye,
    Edit,
    Trash2,
    Filter,
    Users,
    GraduationCap,
    AlertTriangle,
    Clock,
    ChevronDown,
    ChevronUp,
    MoreVertical,
    UserPlus,
    UserCheck,
    UserX,
    Building,
    Calendar,
    Mail,
    Phone,
    Badge,
    Award,
    AlertCircle,
    CheckCircle,
    XCircle,
} from "lucide-react";

export default function Index({
    employees,
    filters,
    departments,
    stats,
    title,
    subtitle,
    success,
    error,
}) {
    // State management
    const [searchQuery, setSearchQuery] = useState(filters.search || "");
    const [departmentFilter, setDepartmentFilter] = useState(
        filters.department || "all"
    );
    const [statusFilter, setStatusFilter] = useState(filters.status || "all");
    const [complianceFilter, setComplianceFilter] = useState(
        filters.compliance || "all"
    );
    const [showFilters, setShowFilters] = useState(false);
    const [selectedEmployees, setSelectedEmployees] = useState([]);
    const [bulkAction, setBulkAction] = useState("");
    const [loading, setLoading] = useState(false);

    // Debounced search
    const [searchTimeout, setSearchTimeout] = useState(null);

    // Sort state
    const [sortField, setSortField] = useState(filters.sort || "name");
    const [sortDirection, setSortDirection] = useState(
        filters.direction || "asc"
    );

    // Apply filters
    const applyFilters = () => {
        const params = {
            search: searchQuery,
            department: departmentFilter,
            status: statusFilter,
            compliance: complianceFilter,
            sort: sortField,
            direction: sortDirection,
        };

        // Remove empty filters
        Object.keys(params).forEach((key) => {
            if (!params[key] || params[key] === "all") {
                delete params[key];
            }
        });

        setLoading(true);
        router.get(route("employees.index"), params, {
            preserveState: true,
            onFinish: () => setLoading(false),
        });
    };

    // Debounced search
    useEffect(() => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        const timeout = setTimeout(() => {
            if (searchQuery !== filters.search) {
                applyFilters();
            }
        }, 500);

        setSearchTimeout(timeout);

        return () => clearTimeout(timeout);
    }, [searchQuery]);

    // Apply filters when other filters change
    useEffect(() => {
        if (
            departmentFilter !== filters.department ||
            statusFilter !== filters.status ||
            complianceFilter !== filters.compliance
        ) {
            applyFilters();
        }
    }, [departmentFilter, statusFilter, complianceFilter]);

    // Sort function
    const handleSort = (field) => {
        const direction =
            sortField === field && sortDirection === "asc" ? "desc" : "asc";
        setSortField(field);
        setSortDirection(direction);

        router.get(
            route("employees.index"),
            {
                ...filters,
                sort: field,
                direction: direction,
            },
            { preserveState: true }
        );
    };

    // Select/deselect employees
    const toggleEmployeeSelection = (employeeId) => {
        setSelectedEmployees((prev) =>
            prev.includes(employeeId)
                ? prev.filter((id) => id !== employeeId)
                : [...prev, employeeId]
        );
    };

    const selectAllEmployees = () => {
        if (selectedEmployees.length === employees.data.length) {
            setSelectedEmployees([]);
        } else {
            setSelectedEmployees(employees.data.map((emp) => emp.id));
        }
    };

    // Bulk operations
    const handleBulkAction = () => {
        if (!bulkAction || selectedEmployees.length === 0) return;

        router.post(
            route("employees.bulk-update"),
            {
                employee_ids: selectedEmployees,
                action: bulkAction,
            },
            {
                onSuccess: () => {
                    setSelectedEmployees([]);
                    setBulkAction("");
                },
            }
        );
    };

    // Get status badge
    const getStatusBadge = (employee) => {
        if (!employee.is_active) {
            return (
                <span className="badge-gapura-red">
                    <XCircle className="w-3 h-3 mr-1" />
                    Inactive
                </span>
            );
        }
        return (
            <span className="badge-gapura-green">
                <CheckCircle className="w-3 h-3 mr-1" />
                Active
            </span>
        );
    };

    // Get training compliance badge
    const getComplianceBadge = (stats) => {
        if (stats.expired_trainings > 0) {
            return (
                <span className="badge-gapura-red">
                    <AlertCircle className="w-3 h-3 mr-1" />
                    {stats.expired_trainings} Expired
                </span>
            );
        }
        if (stats.expiring_soon > 0) {
            return (
                <span className="badge-gapura-yellow">
                    <Clock className="w-3 h-3 mr-1" />
                    {stats.expiring_soon} Expiring
                </span>
            );
        }
        return (
            <span className="badge-gapura-green">
                <Award className="w-3 h-3 mr-1" />
                Compliant
            </span>
        );
    };

    return (
        <DashboardLayout>
            <Head title={title} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            {title}
                        </h1>
                        <p className="text-gray-600 mt-1">{subtitle}</p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => router.get(route("employees.export"))}
                            className="btn-gapura-secondary"
                        >
                            <FileDown className="w-4 h-4 mr-2" />
                            Export
                        </button>
                        <Link href={route("employees.create")} className="btn-gapura">
                            <Plus className="w-4 h-4 mr-2" />
                            Add Employee
                        </Link>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="card-gapura p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-blue-100 rounded-lg">
                                <Users className="w-8 h-8 text-blue-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">
                                    Total Employees
                                </p>
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats.total_employees}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="card-gapura p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-green-100 rounded-lg">
                                <UserCheck className="w-8 h-8 text-green-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">
                                    Active Employees
                                </p>
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats.active_employees}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="card-gapura p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-purple-100 rounded-lg">
                                <GraduationCap className="w-8 h-8 text-purple-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">
                                    Training Records
                                </p>
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats.total_trainings}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="card-gapura p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-yellow-100 rounded-lg">
                                <AlertTriangle className="w-8 h-8 text-yellow-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">
                                    Expiring Soon
                                </p>
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats.expiring_certificates}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters and Search */}
                <div className="card-gapura p-6">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                        {/* Search */}
                        <div className="relative flex-1 max-w-lg">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input
                                type="text"
                                placeholder="Search employees..."
                                className="input-gapura pl-10"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>

                        {/* Filter Toggle */}
                        <div className="flex items-center space-x-3">
                            <button
                                onClick={() => setShowFilters(!showFilters)}
                                className="btn-gapura-secondary"
                            >
                                <Filter className="w-4 h-4 mr-2" />
                                Filters
                                {showFilters ? (
                                    <ChevronUp className="w-4 h-4 ml-2" />
                                ) : (
                                    <ChevronDown className="w-4 h-4 ml-2" />
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Filter Options */}
                    {showFilters && (
                        <div className="mt-4 pt-4 border-t border-gray-200">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Department
                                    </label>
                                    <select
                                        className="input-gapura"
                                        value={departmentFilter}
                                        onChange={(e) =>
                                            setDepartmentFilter(e.target.value)
                                        }
                                    >
                                        <option value="all">All Departments</option>
                                        {departments.map((dept) => (
                                            <option key={dept} value={dept}>
                                                {dept}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        className="input-gapura"
                                        value={statusFilter}
                                        onChange={(e) => setStatusFilter(e.target.value)}
                                    >
                                        <option value="all">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Training Compliance
                                    </label>
                                    <select
                                        className="input-gapura"
                                        value={complianceFilter}
                                        onChange={(e) =>
                                            setComplianceFilter(e.target.value)
                                        }
                                    >
                                        <option value="all">All Compliance</option>
                                        <option value="compliant">Compliant</option>
                                        <option value="expiring">Expiring Soon</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Bulk Actions */}
                {selectedEmployees.length > 0 && (
                    <div className="card-gapura p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <span className="text-sm text-gray-600">
                                    {selectedEmployees.length} employee(s) selected
                                </span>
                                <select
                                    className="input-gapura"
                                    value={bulkAction}
                                    onChange={(e) => setBulkAction(e.target.value)}
                                >
                                    <option value="">Select Action</option>
                                    <option value="activate">Activate</option>
                                    <option value="deactivate">Deactivate</option>
                                    <option value="update_department">
                                        Change Department
                                    </option>
                                </select>
                            </div>
                            <button
                                onClick={handleBulkAction}
                                disabled={!bulkAction}
                                className="btn-gapura disabled:opacity-50"
                            >
                                Apply Action
                            </button>
                        </div>
                    </div>
                )}

                {/* Employees Table */}
                <div className="card-gapura overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="table-gapura">
                            <thead>
                                <tr>
                                    <th className="w-4">
                                        <input
                                            type="checkbox"
                                            checked={
                                                employees.data.length > 0 &&
                                                selectedEmployees.length ===
                                                    employees.data.length
                                            }
                                            onChange={selectAllEmployees}
                                            className="rounded border-gray-300 text-gapura-green focus:ring-gapura-green"
                                        />
                                    </th>
                                    <th>
                                        <button
                                            onClick={() => handleSort("name")}
                                            className="flex items-center space-x-1 hover:text-gapura-green"
                                        >
                                            <span>Name</span>
                                            {sortField === "name" &&
                                                (sortDirection === "asc" ? (
                                                    <ChevronUp className="w-4 h-4" />
                                                ) : (
                                                    <ChevronDown className="w-4 h-4" />
                                                ))}
                                        </button>
                                    </th>
                                    <th>NIK/NIP</th>
                                    <th>Contact</th>
                                    <th>
                                        <button
                                            onClick={() => handleSort("department")}
                                            className="flex items-center space-x-1 hover:text-gapura-green"
                                        >
                                            <span>Department</span>
                                            {sortField === "department" &&
                                                (sortDirection === "asc" ? (
                                                    <ChevronUp className="w-4 h-4" />
                                                ) : (
                                                    <ChevronDown className="w-4 h-4" />
                                                ))}
                                        </button>
                                    </th>
                                    <th>Training Status</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {employees.data.map((employee) => (
                                    <tr key={employee.id} className="hover:bg-gray-50">
                                        <td>
                                            <input
                                                type="checkbox"
                                                checked={selectedEmployees.includes(
                                                    employee.id
                                                )}
                                                onChange={() =>
                                                    toggleEmployeeSelection(employee.id)
                                                }
                                                className="rounded border-gray-300 text-gapura-green focus:ring-gapura-green"
                                            />
                                        </td>
                                        <td>
                                            <div className="flex items-center">
                                                <div className="flex-shrink-0 h-10 w-10">
                                                    <div className="h-10 w-10 rounded-full bg-gapura-green flex items-center justify-center">
                                                        <span className="text-sm font-medium text-white">
                                                            {employee.name
                                                                .charAt(0)
                                                                .toUpperCase()}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="ml-4">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {employee.name}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {employee.position}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div className="text-sm text-gray-900">
                                                NIK: {employee.nik}
                                            </div>
                                            <div className="text-sm text-gray-500">
                                                NIP: {employee.nip}
                                            </div>
                                        </td>
                                        <td>
                                            <div className="text-sm text-gray-900 flex items-center">
                                                <Mail className="w-4 h-4 mr-1" />
                                                {employee.email}
                                            </div>
                                            {employee.phone && (
                                                <div className="text-sm text-gray-500 flex items-center">
                                                    <Phone className="w-4 h-4 mr-1" />
                                                    {employee.phone}
                                                </div>
                                            )}
                                        </td>
                                        <td>
                                            <div className="text-sm text-gray-900 flex items-center">
                                                <Building className="w-4 h-4 mr-1" />
                                                {employee.department}
                                            </div>
                                        </td>
                                        <td>
                                            <div className="space-y-1">
                                                {getComplianceBadge(
                                                    employee.training_stats
                                                )}
                                                <div className="text-xs text-gray-500">
                                                    {employee.training_stats.total_trainings}{" "}
                                                    total trainings
                                                </div>
                                            </div>
                                        </td>
                                        <td>{getStatusBadge(employee)}</td>
                                        <td>
                                            <div className="flex items-center space-x-2">
                                                <Link
                                                    href={route("employees.show", employee)}
                                                    className="text-blue-600 hover:text-blue-900"
                                                    title="View Details"
                                                >
                                                    <Eye className="w-4 h-4" />
                                                </Link>
                                                <Link
                                                    href={route("employees.edit", employee)}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="Edit Employee"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                                <button
                                                    onClick={() => {
                                                        if (
                                                            confirm(
                                                                "Are you sure you want to delete this employee?"
                                                            )
                                                        ) {
                                                            router.delete(
                                                                route(
                                                                    "employees.destroy",
                                                                    employee
                                                                )
                                                            );
                                                        }
                                                    }}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Delete Employee"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {employees.links && (
                        <div className="px-6 py-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Showing {employees.from} to {employees.to} of{" "}
                                    {employees.total} results
                                </div>
                                <div className="flex items-center space-x-2">
                                    {employees.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => router.get(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1 text-sm rounded ${
                                                link.active
                                                    ? "bg-gapura-green text-white"
                                                    : "bg-white text-gray-700 border border-gray-300 hover:bg-gray-50"
                                            } disabled:opacity-50 disabled:cursor-not-allowed`}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Loading Overlay */}
                {loading && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-lg p-6 flex items-center space-x-3">
                            <div className="spinner-gapura w-6 h-6"></div>
                            <span className="text-gray-600">Loading...</span>
                        </div>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
