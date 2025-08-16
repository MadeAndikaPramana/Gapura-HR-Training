import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';

// Simple, safe imports - tested icons only
import {
    UsersIcon,
    AcademicCapIcon,
    TrophyIcon,
    ExclamationTriangleIcon,
    ArrowTrendingUpIcon,
    CalendarIcon,
    DocumentCheckIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ChartBarIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    BellIcon,
    ShieldCheckIcon
} from '@heroicons/react/24/outline';

import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer
} from 'recharts';

export default function TrainingDashboard({
    stats = {},
    recentTrainings = [],
    expiringCertificates = [],
    trainingTypeStats = [],
    departmentStats = [],
    quickActions = [],
    title = "Training Dashboard",
    subtitle = "Overview of training records and compliance status"
}) {
    const [selectedPeriod, setSelectedPeriod] = useState('30');

    // Sample data fallbacks
    const defaultStats = {
        total_employees: 150,
        total_trainings: 320,
        valid_certificates: 280,
        expired_certificates: 15,
        expiring_soon: 25,
        compliance_rate: 87,
        completion_rate: 92,
        ...stats
    };

    // Sample chart data
    const complianceData = trainingTypeStats.length > 0 ? trainingTypeStats : [
        { name: 'Aviation Safety', valid: 130, expired: 20 },
        { name: 'Security Training', valid: 110, expired: 10 },
        { name: 'Medical Certificate', valid: 160, expired: 20 },
        { name: 'Technical Training', valid: 80, expired: 10 },
    ];

    const departmentData = departmentStats.length > 0 ? departmentStats : [
        { name: 'Operations', employees: 45, compliance: 92 },
        { name: 'Ground Support', employees: 38, compliance: 88 },
        { name: 'Security', employees: 25, compliance: 96 },
        { name: 'Maintenance', employees: 30, compliance: 85 },
        { name: 'Customer Service', employees: 22, compliance: 90 },
    ];

    return (
        <DashboardLayout title={title}>
            <Head title="Training Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                            <p className="text-gray-600 mt-1">{subtitle}</p>
                        </div>
                        <div className="flex items-center gap-3 mt-4 md:mt-0">
                            <select
                                value={selectedPeriod}
                                onChange={(e) => setSelectedPeriod(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                            </select>
                            <button className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <ArrowDownTrayIcon className="w-4 h-4" />
                                Export Report
                            </button>
                        </div>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {/* Total Employees */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-blue-100 rounded-lg">
                                <UsersIcon className="w-8 h-8 text-blue-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">Total Employees</p>
                                <p className="text-2xl font-bold text-gray-900">{defaultStats.total_employees}</p>
                            </div>
                        </div>
                    </div>

                    {/* Total Trainings */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-green-100 rounded-lg">
                                <AcademicCapIcon className="w-8 h-8 text-green-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">Total Trainings</p>
                                <p className="text-2xl font-bold text-gray-900">{defaultStats.total_trainings}</p>
                            </div>
                        </div>
                    </div>

                    {/* Valid Certificates */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-emerald-100 rounded-lg">
                                <CheckCircleIcon className="w-8 h-8 text-emerald-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">Valid Certificates</p>
                                <p className="text-2xl font-bold text-gray-900">{defaultStats.valid_certificates}</p>
                            </div>
                        </div>
                    </div>

                    {/* Expiring Soon */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-red-100 rounded-lg">
                                <ExclamationTriangleIcon className="w-8 h-8 text-red-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">Expiring Soon</p>
                                <p className="text-2xl font-bold text-gray-900">{defaultStats.expiring_soon}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Training Compliance Chart */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-6">Training Compliance by Type</h3>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={complianceData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" />
                                <YAxis />
                                <Tooltip />
                                <Bar dataKey="valid" fill="#10b981" name="Valid" />
                                <Bar dataKey="expired" fill="#ef4444" name="Expired" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Department Compliance */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-6">Department Compliance</h3>
                        <div className="space-y-4">
                            {departmentData.map((dept, index) => (
                                <div key={index} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <div className="font-medium text-gray-900">{dept.name}</div>
                                        <div className="text-sm text-gray-500">{dept.employees} employees</div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="text-right">
                                            <div className="font-semibold text-gray-900">{dept.compliance}%</div>
                                            <div className="text-xs text-gray-500">compliance</div>
                                        </div>
                                        <div className="w-16 bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-green-500 h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${dept.compliance}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Quick Actions */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {/* Add Training Record */}
                        <Link
                            href="/training/create"
                            className="group flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all duration-200"
                        >
                            <div className="p-3 bg-green-500 text-white rounded-lg group-hover:scale-110 transition-transform duration-200">
                                <PlusIcon className="w-5 h-5" />
                            </div>
                            <div>
                                <h4 className="font-medium text-gray-900 group-hover:text-green-600">
                                    Add Training Record
                                </h4>
                                <p className="text-sm text-gray-600">
                                    Record new employee training
                                </p>
                            </div>
                        </Link>

                        {/* Upload Certificates */}
                        <Link
                            href="/training/upload"
                            className="group flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-200"
                        >
                            <div className="p-3 bg-blue-500 text-white rounded-lg group-hover:scale-110 transition-transform duration-200">
                                <ArrowDownTrayIcon className="w-5 h-5" />
                            </div>
                            <div>
                                <h4 className="font-medium text-gray-900 group-hover:text-blue-600">
                                    Upload Certificates
                                </h4>
                                <p className="text-sm text-gray-600">
                                    Bulk upload training certificates
                                </p>
                            </div>
                        </Link>

                        {/* Training Analytics */}
                        <Link
                            href="/training/analytics"
                            className="group flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all duration-200"
                        >
                            <div className="p-3 bg-purple-500 text-white rounded-lg group-hover:scale-110 transition-transform duration-200">
                                <ChartBarIcon className="w-5 h-5" />
                            </div>
                            <div>
                                <h4 className="font-medium text-gray-900 group-hover:text-purple-600">
                                    Training Analytics
                                </h4>
                                <p className="text-sm text-gray-600">
                                    View detailed training reports
                                </p>
                            </div>
                        </Link>

                        {/* Compliance Report */}
                        <Link
                            href="/training/reports/compliance"
                            className="group flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all duration-200"
                        >
                            <div className="p-3 bg-orange-500 text-white rounded-lg group-hover:scale-110 transition-transform duration-200">
                                <DocumentCheckIcon className="w-5 h-5" />
                            </div>
                            <div>
                                <h4 className="font-medium text-gray-900 group-hover:text-orange-600">
                                    Compliance Report
                                </h4>
                                <p className="text-sm text-gray-600">
                                    Generate compliance reports
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>

                {/* Recent Notifications */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-6">Recent Notifications</h3>
                    <div className="space-y-4">
                        <div className="flex items-start gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <BellIcon className="w-5 h-5 text-yellow-600 mt-0.5" />
                            <div>
                                <p className="text-sm font-medium text-yellow-800">Certificates Expiring</p>
                                <p className="text-xs text-yellow-600">5 certificates expire in 30 days</p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <AcademicCapIcon className="w-5 h-5 text-blue-600 mt-0.5" />
                            <div>
                                <p className="text-sm font-medium text-blue-800">New Training Added</p>
                                <p className="text-xs text-blue-600">Aviation Safety training updated</p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <CheckCircleIcon className="w-5 h-5 text-green-600 mt-0.5" />
                            <div>
                                <p className="text-sm font-medium text-green-800">Compliance Achieved</p>
                                <p className="text-xs text-green-600">Operations dept reached 95%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
