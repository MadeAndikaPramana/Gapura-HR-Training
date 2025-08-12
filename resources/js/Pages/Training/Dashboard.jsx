import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    Users,
    GraduationCap,
    Award,
    AlertTriangle,
    TrendingUp,
    Calendar,
    FileCheck,
    CheckCircle,
    XCircle,
    Clock,
    BarChart3,
    ArrowUpRight,
    ArrowDownRight,
    Plus,
    Download,
    Bell,
    Shield
} from 'lucide-react';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer,
    LineChart,
    Line
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

    // Sample data fallbacks jika data kosong
    const defaultStats = {
        total_employees: 0,
        total_trainings: 0,
        valid_certificates: 0,
        expired_certificates: 0,
        expiring_soon: 0,
        compliance_rate: 0,
        completion_rate: 0,
        ...stats
    };

    // Default Quick Actions jika kosong
    const defaultQuickActions = quickActions.length > 0 ? quickActions : [
        {
            name: 'Add Training Record',
            description: 'Record new employee training',
            href: '/training/create',
            icon: 'Plus',
            color: 'bg-gapura-green'
        },
        {
            name: 'Upload Certificates',
            description: 'Bulk upload training certificates',
            href: '/training/upload',
            icon: 'Upload',
            color: 'bg-blue-500'
        },
        {
            name: 'Training Analytics',
            description: 'View detailed training reports',
            href: '/training/analytics',
            icon: 'TrendingUp',
            color: 'bg-purple-500'
        },
        {
            name: 'Compliance Report',
            description: 'Generate compliance reports',
            href: '/training/reports/compliance',
            icon: 'FileCheck',
            color: 'bg-orange-500'
        }
    ];

    // Sample chart data
    const complianceData = trainingTypeStats.length > 0 ? trainingTypeStats : [
        { name: 'Aviation Safety', total: 150, valid: 130, expired: 20 },
        { name: 'Security Training', total: 120, valid: 110, expired: 10 },
        { name: 'Medical Certificate', total: 180, valid: 160, expired: 20 },
        { name: 'Technical Training', total: 90, valid: 80, expired: 10 },
    ];

    const departmentData = departmentStats.length > 0 ? departmentStats : [
        { name: 'Operations', employees: 45, compliance: 92 },
        { name: 'Ground Support', employees: 38, compliance: 87 },
        { name: 'Security', employees: 32, compliance: 95 },
        { name: 'Maintenance', employees: 28, compliance: 89 },
    ];

    const getIcon = (iconName) => {
        const icons = {
            Plus, Upload: Download, TrendingUp, FileCheck, Award, Calendar, BarChart3, Shield
        };
        return icons[iconName] || Plus;
    };

    const formatNumber = (num) => {
        return new Intl.NumberFormat('id-ID').format(num || 0);
    };

    const formatPercentage = (num) => {
        return `${(num || 0).toFixed(1)}%`;
    };

    return (
        <DashboardLayout title={title}>
            <Head title="Training Dashboard" />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="bg-gradient-to-r from-gapura-green to-gapura-green-dark rounded-3xl shadow-2xl p-8 text-white">
                    <div className="max-w-4xl">
                        <h1 className="text-3xl font-bold mb-4">
                            Training Management Dashboard
                        </h1>
                        <p className="text-lg text-white/90 mb-6">
                            {subtitle}
                        </p>
                        <div className="flex flex-wrap gap-4">
                            <div className="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
                                <span className="text-sm font-medium">Active Employees</span>
                                <div className="text-2xl font-bold">{formatNumber(defaultStats.total_employees)}</div>
                            </div>
                            <div className="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
                                <span className="text-sm font-medium">Compliance Rate</span>
                                <div className="text-2xl font-bold">{formatPercentage(defaultStats.compliance_rate)}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Statistics Grid */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Total Trainings */}
                    <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="p-3 bg-blue-100 rounded-xl">
                                        <GraduationCap className="h-6 w-6 text-blue-600" />
                                    </div>
                                </div>
                                <div className="ml-4 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Total Training Records
                                        </dt>
                                        <dd className="text-2xl font-bold text-gray-900">
                                            {formatNumber(defaultStats.total_trainings)}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Valid Certificates */}
                    <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="p-3 bg-green-100 rounded-xl">
                                        <CheckCircle className="h-6 w-6 text-green-600" />
                                    </div>
                                </div>
                                <div className="ml-4 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Valid Certificates
                                        </dt>
                                        <dd className="text-2xl font-bold text-green-600">
                                            {formatNumber(defaultStats.valid_certificates)}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Expiring Soon */}
                    <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="p-3 bg-yellow-100 rounded-xl">
                                        <Clock className="h-6 w-6 text-yellow-600" />
                                    </div>
                                </div>
                                <div className="ml-4 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Expiring Soon (30 days)
                                        </dt>
                                        <dd className="text-2xl font-bold text-yellow-600">
                                            {formatNumber(defaultStats.expiring_soon)}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Expired */}
                    <div className="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                        <div className="p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="p-3 bg-red-100 rounded-xl">
                                        <XCircle className="h-6 w-6 text-red-600" />
                                    </div>
                                </div>
                                <div className="ml-4 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Expired Certificates
                                        </dt>
                                        <dd className="text-2xl font-bold text-red-600">
                                            {formatNumber(defaultStats.expired_certificates)}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charts and Analytics */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Training Compliance by Type */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center justify-between mb-6">
                            <h3 className="text-lg font-semibold text-gray-900">Training Compliance by Type</h3>
                            <div className="flex items-center space-x-2">
                                <select
                                    className="text-sm border-gray-300 rounded-lg focus:ring-gapura-green focus:border-gapura-green"
                                    value={selectedPeriod}
                                    onChange={(e) => setSelectedPeriod(e.target.value)}
                                >
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 3 months</option>
                                    <option value="365">Last year</option>
                                </select>
                            </div>
                        </div>
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={complianceData}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                    <XAxis dataKey="name" fontSize={12} />
                                    <YAxis fontSize={12} />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'white',
                                            border: '1px solid #e5e7eb',
                                            borderRadius: '8px',
                                            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                                        }}
                                    />
                                    <Bar dataKey="valid" fill="#439454" name="Valid" radius={[4, 4, 0, 0]} />
                                    <Bar dataKey="expired" fill="#ef4444" name="Expired" radius={[4, 4, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Department Compliance */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center justify-between mb-6">
                            <h3 className="text-lg font-semibold text-gray-900">Department Compliance</h3>
                            <Link
                                href="/training/analytics"
                                className="text-sm text-gapura-green hover:text-gapura-green-dark font-medium flex items-center gap-1"
                            >
                                View Details <ArrowUpRight className="h-4 w-4" />
                            </Link>
                        </div>
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
                                                className="bg-gapura-green h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${dept.compliance}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Quick Actions & Recent Activity */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Quick Actions */}
                    <div className="lg:col-span-2">
                        <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h3>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {defaultQuickActions.map((action, index) => {
                                    const Icon = getIcon(action.icon);
                                    return (
                                        <Link
                                            key={index}
                                            href={action.href}
                                            className="group relative rounded-xl border border-gray-200 p-6 hover:border-gapura-green transition-all duration-200 hover:shadow-lg"
                                        >
                                            <div className="flex items-center space-x-4">
                                                <div className={`flex-shrink-0 p-3 rounded-xl ${action.color}`}>
                                                    <Icon className="h-6 w-6 text-white" />
                                                </div>
                                                <div>
                                                    <h4 className="text-sm font-semibold text-gray-900 group-hover:text-gapura-green">
                                                        {action.name}
                                                    </h4>
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        {action.description}
                                                    </p>
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center justify-between mb-6">
                            <h3 className="text-lg font-semibold text-gray-900">Recent Activity</h3>
                            <Bell className="h-5 w-5 text-gray-400" />
                        </div>
                        <div className="space-y-4">
                            {recentTrainings.length > 0 ? (
                                recentTrainings.slice(0, 5).map((training, index) => (
                                    <div key={index} className="flex items-center space-x-3">
                                        <div className="flex-shrink-0 p-2 bg-gapura-green/10 rounded-lg">
                                            <Award className="h-4 w-4 text-gapura-green" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                {training.employee?.nama_lengkap}
                                            </p>
                                            <p className="text-xs text-gray-500 truncate">
                                                {training.training_type?.name}
                                            </p>
                                        </div>
                                        <div className="text-xs text-gray-400">
                                            {training.created_at ? new Date(training.created_at).toLocaleDateString('id-ID') : 'Today'}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-center py-8">
                                    <GraduationCap className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                                    <p className="text-sm text-gray-500">No recent training activity</p>
                                    <Link
                                        href="/training/create"
                                        className="text-sm text-gapura-green hover:text-gapura-green-dark mt-2 inline-block"
                                    >
                                        Add your first training record
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Expiring Certificates Alert */}
                {expiringCertificates.length > 0 && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                        <div className="flex items-center space-x-3 mb-4">
                            <AlertTriangle className="h-6 w-6 text-yellow-600" />
                            <h3 className="text-lg font-semibold text-yellow-800">
                                Certificates Expiring Soon
                            </h3>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {expiringCertificates.slice(0, 6).map((cert, index) => (
                                <div key={index} className="bg-white p-4 rounded-lg border border-yellow-200">
                                    <div className="font-medium text-gray-900">{cert.employee?.nama_lengkap}</div>
                                    <div className="text-sm text-gray-600">{cert.training_type?.name}</div>
                                    <div className="text-xs text-yellow-600 mt-2">
                                        Expires: {cert.expiry_date ? new Date(cert.expiry_date).toLocaleDateString('id-ID') : 'N/A'}
                                    </div>
                                </div>
                            ))}
                        </div>
                        {expiringCertificates.length > 6 && (
                            <div className="mt-4 text-center">
                                <Link
                                    href="/training/certificates/expiring"
                                    className="text-sm text-yellow-700 hover:text-yellow-800 font-medium"
                                >
                                    View all {expiringCertificates.length} expiring certificates
                                </Link>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
