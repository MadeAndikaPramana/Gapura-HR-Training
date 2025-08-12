import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    BarChart3,
    TrendingUp,
    Download,
    Calendar,
    Users,
    Award,
    AlertCircle,
    Filter,
    RefreshCw
} from 'lucide-react';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    LineChart,
    Line,
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer,
    Area,
    AreaChart
} from 'recharts';

export default function TrainingAnalytics({
    analytics = {},
    title = "Training Analytics",
    subtitle = "Advanced analytics and reporting for training data"
}) {
    const [selectedPeriod, setSelectedPeriod] = useState('30');
    const [selectedDepartment, setSelectedDepartment] = useState('all');

    // Sample data for development
    const monthlyTrends = [
        { month: 'Jan', trainings: 45, certifications: 38, compliance: 85 },
        { month: 'Feb', trainings: 52, certifications: 45, compliance: 87 },
        { month: 'Mar', trainings: 48, certifications: 42, compliance: 89 },
        { month: 'Apr', trainings: 61, certifications: 55, compliance: 90 },
        { month: 'May', trainings: 55, certifications: 48, compliance: 87 },
        { month: 'Jun', trainings: 67, certifications: 58, compliance: 92 },
    ];

    const trainingTypes = [
        { name: 'Aviation Safety', value: 35, color: '#439454' },
        { name: 'Security Training', value: 25, color: '#358945' },
        { name: 'Medical Certificate', value: 20, color: '#4ade80' },
        { name: 'Technical Training', value: 15, color: '#86efac' },
        { name: 'Others', value: 5, color: '#dcfce7' },
    ];

    const departmentComparison = [
        { department: 'Operations', q1: 85, q2: 87, q3: 90, q4: 92 },
        { department: 'Ground Support', q1: 82, q2: 84, q3: 86, q4: 88 },
        { department: 'Security', q1: 90, q2: 92, q3: 95, q4: 96 },
        { department: 'Maintenance', q1: 78, q2: 81, q3: 85, q4: 87 },
        { department: 'Customer Service', q1: 88, q2: 89, q3: 91, q4: 93 },
    ];

    const exportData = () => {
        console.log('Exporting analytics data...');
        // Implementation for data export
    };

    return (
        <DashboardLayout title={title}>
            <Head title="Training Analytics" />

            <div className="space-y-6">
                {/* Header */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Training Analytics</h1>
                            <p className="text-gray-600 mt-1">{subtitle}</p>
                        </div>
                        <div className="mt-4 lg:mt-0 flex flex-col sm:flex-row gap-3">
                            <div className="flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-gray-500" />
                                <select
                                    className="text-sm border-gray-300 rounded-lg focus:ring-gapura-green focus:border-gapura-green"
                                    value={selectedPeriod}
                                    onChange={(e) => setSelectedPeriod(e.target.value)}
                                >
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 3 months</option>
                                    <option value="180">Last 6 months</option>
                                    <option value="365">Last year</option>
                                </select>
                            </div>
                            <div className="flex items-center gap-2">
                                <Filter className="h-4 w-4 text-gray-500" />
                                <select
                                    className="text-sm border-gray-300 rounded-lg focus:ring-gapura-green focus:border-gapura-green"
                                    value={selectedDepartment}
                                    onChange={(e) => setSelectedDepartment(e.target.value)}
                                >
                                    <option value="all">All Departments</option>
                                    <option value="operations">Operations</option>
                                    <option value="security">Security</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <button
                                onClick={exportData}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-gapura-green text-white rounded-lg hover:bg-gapura-green-dark transition-colors duration-200"
                            >
                                <Download className="h-4 w-4" />
                                Export Report
                            </button>
                        </div>
                    </div>
                </div>

                {/* Key Metrics */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-blue-100 rounded-xl">
                                <BarChart3 className="h-6 w-6 text-blue-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Total Analytics</p>
                                <p className="text-2xl font-bold text-gray-900">2,847</p>
                                <p className="text-xs text-green-600">+12% this month</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-green-100 rounded-xl">
                                <TrendingUp className="h-6 w-6 text-green-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Completion Rate</p>
                                <p className="text-2xl font-bold text-gray-900">94.2%</p>
                                <p className="text-xs text-green-600">+3.2% this month</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-purple-100 rounded-xl">
                                <Users className="h-6 w-6 text-purple-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Active Employees</p>
                                <p className="text-2xl font-bold text-gray-900">1,234</p>
                                <p className="text-xs text-blue-600">+8 this week</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center">
                            <div className="p-3 bg-orange-100 rounded-xl">
                                <Award className="h-6 w-6 text-orange-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Certifications</p>
                                <p className="text-2xl font-bold text-gray-900">847</p>
                                <p className="text-xs text-green-600">+23 this week</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charts Row 1 */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Monthly Trends */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center justify-between mb-6">
                            <h3 className="text-lg font-semibold text-gray-900">Monthly Training Trends</h3>
                            <RefreshCw className="h-5 w-5 text-gray-400 cursor-pointer hover:text-gapura-green" />
                        </div>
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={monthlyTrends}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                    <XAxis dataKey="month" fontSize={12} />
                                    <YAxis fontSize={12} />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'white',
                                            border: '1px solid #e5e7eb',
                                            borderRadius: '8px',
                                            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                                        }}
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="trainings"
                                        stroke="#439454"
                                        fill="#439454"
                                        fillOpacity={0.1}
                                        name="Trainings"
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="certifications"
                                        stroke="#358945"
                                        fill="#358945"
                                        fillOpacity={0.1}
                                        name="Certifications"
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Training Types Distribution */}
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center justify-between mb-6">
                            <h3 className="text-lg font-semibold text-gray-900">Training Types Distribution</h3>
                            <div className="text-sm text-gray-500">Total: 2,847</div>
                        </div>
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={trainingTypes}
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                        label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                    >
                                        {trainingTypes.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>

                {/* Department Comparison */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div className="flex items-center justify-between mb-6">
                        <h3 className="text-lg font-semibold text-gray-900">Department Compliance Comparison</h3>
                        <div className="text-sm text-gray-500">Quarterly Performance</div>
                    </div>
                    <div className="h-80">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={departmentComparison}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis dataKey="department" fontSize={12} />
                                <YAxis fontSize={12} />
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: 'white',
                                        border: '1px solid #e5e7eb',
                                        borderRadius: '8px',
                                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                                    }}
                                />
                                <Bar dataKey="q1" fill="#dcfce7" name="Q1" />
                                <Bar dataKey="q2" fill="#86efac" name="Q2" />
                                <Bar dataKey="q3" fill="#4ade80" name="Q3" />
                                <Bar dataKey="q4" fill="#439454" name="Q4" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Action Items */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Recommended Actions</h3>
                        <div className="space-y-4">
                            <div className="flex items-start space-x-3 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                <AlertCircle className="h-5 w-5 text-yellow-600 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-yellow-800">Low Compliance in Maintenance</p>
                                    <p className="text-xs text-yellow-600 mt-1">Consider scheduling additional training sessions</p>
                                </div>
                            </div>
                            <div className="flex items-start space-x-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <Award className="h-5 w-5 text-blue-600 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-blue-800">Certificate Renewals Due</p>
                                    <p className="text-xs text-blue-600 mt-1">23 certificates expiring in next 30 days</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Export Options</h3>
                        <div className="space-y-3">
                            <button className="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div className="font-medium text-gray-900">Compliance Report</div>
                                <div className="text-sm text-gray-500">Department-wise compliance analysis</div>
                            </button>
                            <button className="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div className="font-medium text-gray-900">Training Summary</div>
                                <div className="text-sm text-gray-500">Monthly training activities summary</div>
                            </button>
                            <button className="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div className="font-medium text-gray-900">Employee Records</div>
                                <div className="text-sm text-gray-500">Individual employee training records</div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
