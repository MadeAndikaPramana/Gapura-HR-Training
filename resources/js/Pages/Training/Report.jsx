import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    FileCheck,
    Download,
    Calendar,
    Users,
    Building,
    Award,
    Clock,
    AlertTriangle,
    Search,
    Filter,
    Eye,
    ExternalLink
} from 'lucide-react';

export default function TrainingReports({
    title = "Training Reports",
    subtitle = "Generate and export comprehensive training reports"
}) {
    const [selectedReportType, setSelectedReportType] = useState('compliance');
    const [selectedPeriod, setSelectedPeriod] = useState('monthly');
    const [selectedDepartment, setSelectedDepartment] = useState('all');

    // Available report types
    const reportTypes = [
        {
            id: 'compliance',
            name: 'Compliance Report',
            description: 'Department-wise training compliance analysis',
            icon: FileCheck,
            color: 'bg-green-500',
            route: '/training/reports/compliance'
        },
        {
            id: 'expiry',
            name: 'Certificate Expiry Report',
            description: 'Upcoming certificate expirations and renewals',
            icon: Clock,
            color: 'bg-yellow-500',
            route: '/training/reports/expiry'
        },
        {
            id: 'employee',
            name: 'Employee Training Records',
            description: 'Individual employee training history and status',
            icon: Users,
            color: 'bg-blue-500',
            route: '/training/reports/employee'
        },
        {
            id: 'department',
            name: 'Department Summary',
            description: 'Department-wise training statistics and performance',
            icon: Building,
            color: 'bg-purple-500',
            route: '/training/reports/department'
        },
        {
            id: 'training-type',
            name: 'Training Type Analysis',
            description: 'Analysis by training categories and requirements',
            icon: Award,
            color: 'bg-orange-500',
            route: '/training/reports/training-type'
        },
        {
            id: 'violations',
            name: 'Compliance Violations',
            description: 'Missing or expired mandatory trainings',
            icon: AlertTriangle,
            color: 'bg-red-500',
            route: '/training/reports/violations'
        }
    ];

    // Recent reports
    const recentReports = [
        {
            name: 'Monthly Compliance Report - July 2024',
            type: 'Compliance',
            generated: '2024-07-28',
            size: '2.3 MB',
            format: 'PDF'
        },
        {
            name: 'Certificate Expiry Alert - Q3 2024',
            type: 'Expiry',
            generated: '2024-07-25',
            size: '1.8 MB',
            format: 'Excel'
        },
        {
            name: 'Operations Department Summary',
            type: 'Department',
            generated: '2024-07-22',
            size: '1.2 MB',
            format: 'PDF'
        },
        {
            name: 'Aviation Safety Training Analysis',
            type: 'Training Type',
            generated: '2024-07-20',
            size: '3.1 MB',
            format: 'PDF'
        }
    ];

    const generateReport = (reportType) => {
        console.log('Generating report:', reportType);
        // Implementation for report generation
    };

    const downloadReport = (reportName) => {
        console.log('Downloading report:', reportName);
        // Implementation for report download
    };

    return (
        <DashboardLayout title={title}>
            <Head title="Training Reports" />

            <div className="space-y-6">
                {/* Header */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Training Reports</h1>
                            <p className="text-gray-600 mt-1">{subtitle}</p>
                        </div>
                        <div className="mt-4 lg:mt-0">
                            <Link
                                href="/training/analytics"
                                className="inline-flex items-center gap-2 px-4 py-2 bg-gapura-green text-white rounded-lg hover:bg-gapura-green-dark transition-colors duration-200"
                            >
                                <Eye className="h-4 w-4" />
                                View Analytics
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Report Generator */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-6">Generate New Report</h2>

                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
                        {/* Report Type */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                            <select
                                className="w-full border-gray-300 rounded-lg focus:ring-gapura-green focus:border-gapura-green"
                                value={selectedReportType}
                                onChange={(e) => setSelectedReportType(e.target.value)}
                            >
                                {reportTypes.map((report) => (
                                    <option key={report.id} value={report.id}>{report.name}</option>
                                ))}
                            </select>
                        </div>

                        {/* Period */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Period</label>
                            <select
                                className="w-full border-gray-300 rounded-lg focus:ring-gapura-green focus:border-gapura-green"
                                value={selectedPeriod}
                                onChange={(e) => setSelectedPeriod(e.target.value)}
                            >
                                <option value="weekly">This Week</option>
                                <option value="monthly">This Month</option>
                                <option value="quarterly">This Quarter</option>
                                <option value="yearly">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>

                        {/* Department */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select
                                className="w-full border-gray-300 rounded-lg focus:ring-gapura-green focus:border-gapura-green"
                                value={selectedDepartment}
                                onChange={(e) => setSelectedDepartment(e.target.value)}
                            >
                                <option value="all">All Departments</option>
                                <option value="operations">Operations</option>
                                <option value="security">Security</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="ground-support">Ground Support</option>
                                <option value="customer-service">Customer Service</option>
                            </select>
                        </div>

                        {/* Generate Button */}
                        <div className="flex items-end">
                            <button
                                onClick={() => generateReport(selectedReportType)}
                                className="w-full px-4 py-2 bg-gapura-green text-white rounded-lg hover:bg-gapura-green-dark transition-colors duration-200 flex items-center justify-center gap-2"
                            >
                                <Download className="h-4 w-4" />
                                Generate Report
                            </button>
                        </div>
                    </div>

                    {/* Selected Report Description */}
                    {selectedReportType && (
                        <div className="p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div className="flex items-center gap-3">
                                {(() => {
                                    const report = reportTypes.find(r => r.id === selectedReportType);
                                    const Icon = report?.icon || FileCheck;
                                    return (
                                        <>
                                            <div className={`p-2 ${report?.color || 'bg-gray-500'} rounded-lg`}>
                                                <Icon className="h-5 w-5 text-white" />
                                            </div>
                                            <div>
                                                <h4 className="font-medium text-gray-900">{report?.name}</h4>
                                                <p className="text-sm text-gray-600">{report?.description}</p>
                                            </div>
                                        </>
                                    );
                                })()}
                            </div>
                        </div>
                    )}
                </div>

                {/* Available Report Types */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-6">Available Report Types</h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {reportTypes.map((report) => {
                            const Icon = report.icon;
                            return (
                                <div
                                    key={report.id}
                                    className="group border border-gray-200 rounded-lg p-4 hover:border-gapura-green transition-all duration-200 cursor-pointer"
                                    onClick={() => setSelectedReportType(report.id)}
                                >
                                    <div className="flex items-center space-x-3">
                                        <div className={`p-3 ${report.color} rounded-xl group-hover:scale-110 transition-transform duration-200`}>
                                            <Icon className="h-6 w-6 text-white" />
                                        </div>
                                        <div className="flex-1">
                                            <h3 className="font-medium text-gray-900 group-hover:text-gapura-green">
                                                {report.name}
                                            </h3>
                                            <p className="text-sm text-gray-600 mt-1">
                                                {report.description}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-3 flex justify-between items-center">
                                        <Link
                                            href={report.route}
                                            className="text-sm text-gapura-green hover:text-gapura-green-dark flex items-center gap-1"
                                        >
                                            View Details <ExternalLink className="h-3 w-3" />
                                        </Link>
                                        <button
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                generateReport(report.id);
                                            }}
                                            className="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
                                        >
                                            <Download className="h-3 w-3" />
                                            Generate
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Recent Reports */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-lg font-semibold text-gray-900">Recent Reports</h2>
                        <div className="flex items-center gap-2">
                            <div className="relative">
                                <Search className="h-4 w-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
                                <input
                                    type="text"
                                    placeholder="Search reports..."
                                    className="pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-gapura-green focus:border-gapura-green"
                                />
                            </div>
                            <button className="p-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg">
                                <Filter className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div className="overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Report Name
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Generated
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Size
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {recentReports.map((report, index) => (
                                    <tr key={index} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">{report.name}</div>
                                                <div className="text-xs text-gray-500">{report.format}</div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gapura-green/10 text-gapura-green">
                                                {report.type}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {new Date(report.generated).toLocaleDateString('id-ID')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {report.size}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button
                                                onClick={() => downloadReport(report.name)}
                                                className="text-gapura-green hover:text-gapura-green-dark flex items-center gap-1"
                                            >
                                                <Download className="h-4 w-4" />
                                                Download
                                            </button>
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
