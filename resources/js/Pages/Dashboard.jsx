import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    Users,
    GraduationCap,
    AlertTriangle,
    CheckCircle,
    Calendar,
    TrendingUp,
    FileCheck,
    Award
} from 'lucide-react';

export default function Dashboard({ stats = {} }) {
    // Default stats if not provided
    const defaultStats = {
        totalEmployees: 0,
        totalTrainings: 0,
        expiredCertificates: 0,
        expiringSoon: 0,
        backgroundChecksValid: 0,
        complianceRate: 0,
        ...stats
    };

    const statCards = [
        {
            name: 'Total Employees',
            value: defaultStats.totalEmployees,
            icon: Users,
            color: 'bg-blue-500',
            bgColor: 'bg-blue-50',
            textColor: 'text-blue-700'
        },
        {
            name: 'Active Training Records',
            value: defaultStats.totalTrainings,
            icon: GraduationCap,
            color: 'bg-gapura-green',
            bgColor: 'bg-green-50',
            textColor: 'text-green-700'
        },
        {
            name: 'Expired Certificates',
            value: defaultStats.expiredCertificates,
            icon: AlertTriangle,
            color: 'bg-red-500',
            bgColor: 'bg-red-50',
            textColor: 'text-red-700'
        },
        {
            name: 'Expiring Soon',
            value: defaultStats.expiringSoon,
            icon: Calendar,
            color: 'bg-yellow-500',
            bgColor: 'bg-yellow-50',
            textColor: 'text-yellow-700'
        },
        {
            name: 'Valid Background Checks',
            value: defaultStats.backgroundChecksValid,
            icon: FileCheck,
            color: 'bg-indigo-500',
            bgColor: 'bg-indigo-50',
            textColor: 'text-indigo-700'
        },
        {
            name: 'Compliance Rate',
            value: `${defaultStats.complianceRate}%`,
            icon: CheckCircle,
            color: 'bg-green-500',
            bgColor: 'bg-green-50',
            textColor: 'text-green-700'
        }
    ];

    const quickActions = [
        {
            name: 'Add Training Record',
            description: 'Register new training for employee',
            href: '/training/create',
            icon: GraduationCap,
            color: 'bg-gapura-green'
        },
        {
            name: 'Import Excel Data',
            description: 'Bulk import training records from Excel',
            href: '/import-export',
            icon: TrendingUp,
            color: 'bg-blue-500'
        },
        {
            name: 'Generate Reports',
            description: 'Export training compliance reports',
            href: '/reports',
            icon: FileCheck,
            color: 'bg-purple-500'
        },
        {
            name: 'Certificate Management',
            description: 'View and manage certificates',
            href: '/certificates',
            icon: Award,
            color: 'bg-orange-500'
        }
    ];

    return (
        <DashboardLayout title="Training Dashboard">
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Welcome Section */}
                <div className="bg-gradient-to-r from-gapura-green to-gapura-green-dark rounded-3xl shadow-2xl p-8 text-white">
                    <div className="max-w-3xl">
                        <h2 className="text-3xl font-bold mb-4">
                            Welcome to GAPURA Training System
                        </h2>
                        <p className="text-lg text-white/90 mb-6">
                            Manage aviation training records, certificates, and compliance tracking
                            for GAPURA ANGKASA employees efficiently and securely.
                        </p>
                        <div className="flex items-center space-x-6">
                            <div className="flex items-center">
                                <div className="w-3 h-3 mr-2 bg-white rounded-full animate-pulse"></div>
                                <span className="text-white/90">System Online</span>
                            </div>
                            <div className="flex items-center">
                                <div className="w-3 h-3 mr-2 bg-white rounded-full"></div>
                                <span className="text-white/90">Data Synchronized</span>
                            </div>
                            <div className="flex items-center">
                                <div className="w-3 h-3 mr-2 bg-white rounded-full"></div>
                                <span className="text-white/90">Security Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {statCards.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <div key={stat.name} className="card-gapura hover:shadow-xl transition-shadow duration-300">
                                <div className="p-6">
                                    <div className="flex items-center">
                                        <div className={`flex-shrink-0 p-3 rounded-xl ${stat.bgColor}`}>
                                            <Icon className={`h-6 w-6 ${stat.textColor}`} />
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">{stat.name}</p>
                                            <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Quick Actions */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="card-gapura">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                            <div className="space-y-3">
                                {quickActions.map((action) => {
                                    const Icon = action.icon;
                                    return (
                                        <a
                                            key={action.name}
                                            href={action.href}
                                            className="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors duration-200 group"
                                        >
                                            <div className={`flex-shrink-0 p-2 rounded-lg ${action.color}`}>
                                                <Icon className="h-5 w-5 text-white" />
                                            </div>
                                            <div className="ml-3">
                                                <p className="text-sm font-medium text-gray-900 group-hover:text-gapura-green">
                                                    {action.name}
                                                </p>
                                                <p className="text-xs text-gray-500">{action.description}</p>
                                            </div>
                                        </a>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="card-gapura">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                            <div className="space-y-3">
                                <div className="flex items-center p-3 rounded-lg bg-gray-50">
                                    <div className="flex-shrink-0 p-2 rounded-lg bg-green-100">
                                        <CheckCircle className="h-5 w-5 text-green-600" />
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm font-medium text-gray-900">
                                            Training system initialized
                                        </p>
                                        <p className="text-xs text-gray-500">Ready for data import</p>
                                    </div>
                                </div>

                                <div className="flex items-center p-3 rounded-lg hover:bg-gray-50">
                                    <div className="flex-shrink-0 p-2 rounded-lg bg-blue-100">
                                        <GraduationCap className="h-5 w-5 text-blue-600" />
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm font-medium text-gray-900">
                                            Training types configured
                                        </p>
                                        <p className="text-xs text-gray-500">5 training types available</p>
                                    </div>
                                </div>

                                <div className="flex items-center p-3 rounded-lg hover:bg-gray-50">
                                    <div className="flex-shrink-0 p-2 rounded-lg bg-orange-100">
                                        <Award className="h-5 w-5 text-orange-600" />
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm font-medium text-gray-900">
                                            Certificate tracking enabled
                                        </p>
                                        <p className="text-xs text-gray-500">Auto-expiry monitoring active</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Training Types Overview */}
                <div className="card-gapura">
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Training Types</h3>
                        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                            {[
                                { name: 'PAX & Baggage Handling', duration: '36 months', color: 'bg-blue-100 text-blue-800' },
                                { name: 'Safety Training (SMS)', duration: '36 months', color: 'bg-green-100 text-green-800' },
                                { name: 'Human Factor', duration: '36 months', color: 'bg-purple-100 text-purple-800' },
                                { name: 'Dangerous Goods', duration: '24 months', color: 'bg-orange-100 text-orange-800' },
                                { name: 'Aviation Security', duration: '12 months', color: 'bg-red-100 text-red-800' },
                            ].map((training) => (
                                <div key={training.name} className="text-center p-4 rounded-lg bg-gray-50">
                                    <div className={`inline-flex px-2 py-1 rounded-full text-xs font-medium ${training.color} mb-2`}>
                                        {training.duration}
                                    </div>
                                    <p className="text-sm font-medium text-gray-900">{training.name}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
