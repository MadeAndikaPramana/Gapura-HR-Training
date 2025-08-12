import React, { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    Search,
    Plus,
    Filter,
    Eye,
    Edit,
    Trash2,
    Users,
    Award,
    Clock,
    AlertTriangle
} from "lucide-react";

export default function TrainingIndex({
    trainingRecords = { data: [] },
    filterOptions = {},
    statistics = {},
    filters = {},
    title = "Training Records Management",
    subtitle = "Kelola data pelatihan dan sertifikasi karyawan"
}) {
    const [searchQuery, setSearchQuery] = useState(filters.search || "");
    const [loading, setLoading] = useState(false);

    return (
        <DashboardLayout>
            <Head title={title} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                        <p className="text-sm text-gray-600 mt-1">{subtitle}</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href="/training/employees"
                            className="flex items-center gap-2 px-4 py-2 border-2 border-[#439454] text-[#439454] rounded-xl hover:bg-[#439454] hover:text-white transition-all duration-300 font-medium"
                        >
                            <Users className="w-4 h-4" />
                            Data Karyawan
                        </Link>

                        <Link
                            href="/training/create"
                            className="flex items-center gap-2 px-4 py-2 bg-[#439454] text-white rounded-xl hover:bg-[#358945] transition-all duration-300 shadow-lg font-medium"
                        >
                            <Plus className="w-4 h-4" />
                            Add Training
                        </Link>
                    </div>
                </div>

                {/* Statistics */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl border border-blue-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-blue-500 text-white rounded-xl">
                                <Users className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">{statistics.total_employees || 0}</h3>
                                <p className="text-sm text-gray-600">Total Karyawan</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-2xl border border-green-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-[#439454] text-white rounded-xl">
                                <Award className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">{statistics.active_certificates || 0}</h3>
                                <p className="text-sm text-gray-600">Active Certificates</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-2xl border border-orange-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-orange-500 text-white rounded-xl">
                                <Clock className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">{statistics.expiring_soon || 0}</h3>
                                <p className="text-sm text-gray-600">Expiring Soon</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-red-50 to-red-100 p-6 rounded-2xl border border-red-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-red-500 text-white rounded-xl">
                                <AlertTriangle className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">{statistics.expired || 0}</h3>
                                <p className="text-sm text-gray-600">Expired</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    {/* Search Header */}
                    <div className="p-6 border-b border-gray-100 bg-gray-50">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="relative flex-1 max-w-md">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <input
                                    type="text"
                                    placeholder="Cari training records..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Employee</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Training Type</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Certificate</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Valid Until</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {trainingRecords.data.map((record) => (
                                    <tr key={record.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900">{record.employee_name}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-900">{record.training_type}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="text-xs font-mono bg-gray-100 px-2 py-1 rounded">{record.certificate_number}</span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {record.valid_until}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                {record.status}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div className="flex items-center gap-2">
                                                <button className="text-[#439454] hover:text-[#358945]">
                                                    <Eye className="w-4 h-4" />
                                                </button>
                                                <button className="text-blue-600 hover:text-blue-800">
                                                    <Edit className="w-4 h-4" />
                                                </button>
                                                <button className="text-red-600 hover:text-red-800">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
