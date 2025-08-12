import React from "react";
import { Link } from "@inertiajs/react";
import {
    X,
    User,
    Award,
    Calendar,
    FileText,
    Clock,
    Building2,
    Mail,
    Phone,
    MapPin,
    CheckCircle,
    XCircle,
    AlertTriangle,
    Edit,
    Download,
    Eye,
    Trash2,
} from "lucide-react";

export default function TrainingDetailModal({ training, isOpen, onClose }) {
    if (!isOpen || !training) return null;

    // Calculate status
    const getStatus = () => {
        const today = new Date();
        const expiryDate = new Date(training.expiry_date);
        const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

        if (daysUntilExpiry < 0) {
            return {
                text: "Expired",
                icon: XCircle,
                class: "bg-red-100 text-red-800 border-red-200",
                textClass: "text-red-600"
            };
        } else if (daysUntilExpiry <= 30) {
            return {
                text: `Due in ${daysUntilExpiry} days`,
                icon: AlertTriangle,
                class: "bg-yellow-100 text-yellow-800 border-yellow-200",
                textClass: "text-yellow-600"
            };
        } else {
            return {
                text: `Valid for ${daysUntilExpiry} days`,
                icon: CheckCircle,
                class: "bg-green-100 text-green-800 border-green-200",
                textClass: "text-green-600"
            };
        }
    };

    const status = getStatus();
    const StatusIcon = status.icon;

    // Format date
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const formatDateShort = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-screen items-center justify-center p-4">
                {/* Backdrop */}
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                    onClick={onClose}
                />

                {/* Modal */}
                <div className="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl">
                    {/* Header */}
                    <div className="flex items-center justify-between p-6 border-b border-gray-200">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-[#439454] rounded-xl">
                                <Award className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h3 className="text-xl font-semibold text-gray-900">
                                    Training Record Details
                                </h3>
                                <p className="text-sm text-gray-500">
                                    {training.training_type?.name || 'Training Information'}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            {/* Status Badge */}
                            <span className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium border ${status.class}`}>
                                <StatusIcon className="h-4 w-4" />
                                {status.text}
                            </span>

                            <button
                                onClick={onClose}
                                className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-6">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Employee Information */}
                            <div className="lg:col-span-2 space-y-6">
                                {/* Employee Details */}
                                <div className="bg-gray-50 rounded-xl p-6">
                                    <div className="flex items-center gap-3 mb-4">
                                        <User className="h-5 w-5 text-[#439454]" />
                                        <h4 className="text-lg font-semibold text-gray-900">Employee Information</h4>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Full Name
                                            </label>
                                            <p className="text-sm font-semibold text-gray-900">
                                                {training.employee?.nama_lengkap || 'N/A'}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Employee ID (NIP)
                                            </label>
                                            <p className="text-sm font-semibold text-gray-900">
                                                {training.employee?.nip || 'N/A'}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Department
                                            </label>
                                            <p className="text-sm text-gray-700">
                                                {training.employee?.unit_organisasi || 'N/A'}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Position
                                            </label>
                                            <p className="text-sm text-gray-700">
                                                {training.employee?.jabatan || 'N/A'}
                                            </p>
                                        </div>

                                        {training.employee?.email && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">
                                                    Email
                                                </label>
                                                <p className="text-sm text-gray-700 flex items-center gap-2">
                                                    <Mail className="h-4 w-4 text-gray-400" />
                                                    {training.employee.email}
                                                </p>
                                            </div>
                                        )}

                                        {training.employee?.handphone && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">
                                                    Phone
                                                </label>
                                                <p className="text-sm text-gray-700 flex items-center gap-2">
                                                    <Phone className="h-4 w-4 text-gray-400" />
                                                    {training.employee.handphone}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Training Details */}
                                <div className="bg-white border-2 border-gray-200 rounded-xl p-6">
                                    <div className="flex items-center gap-3 mb-4">
                                        <Award className="h-5 w-5 text-[#439454]" />
                                        <h4 className="text-lg font-semibold text-gray-900">Training Details</h4>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Training Type
                                            </label>
                                            <p className="text-sm font-semibold text-gray-900">
                                                {training.training_type?.name || 'N/A'}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Category
                                            </label>
                                            <p className="text-sm text-gray-700">
                                                {training.training_type?.category || 'N/A'}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Certificate Number
                                            </label>
                                            <p className="text-sm font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                                {training.certificate_number || 'N/A'}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Training Provider
                                            </label>
                                            <p className="text-sm text-gray-700">
                                                {training.training_provider || 'N/A'}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Notes */}
                                {training.notes && (
                                    <div className="bg-blue-50 border border-blue-200 rounded-xl p-6">
                                        <div className="flex items-center gap-3 mb-3">
                                            <FileText className="h-5 w-5 text-blue-600" />
                                            <h4 className="text-lg font-semibold text-blue-900">Notes</h4>
                                        </div>
                                        <p className="text-sm text-blue-800 leading-relaxed">
                                            {training.notes}
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Certificate Information & Actions */}
                            <div className="space-y-6">
                                {/* Certificate Dates */}
                                <div className="bg-white border-2 border-gray-200 rounded-xl p-6">
                                    <div className="flex items-center gap-3 mb-4">
                                        <Calendar className="h-5 w-5 text-[#439454]" />
                                        <h4 className="text-lg font-semibold text-gray-900">Certificate Timeline</h4>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Issue Date
                                            </label>
                                            <p className="text-sm font-semibold text-gray-900">
                                                {formatDate(training.issue_date)}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {formatDateShort(training.issue_date)}
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-500 mb-1">
                                                Expiry Date
                                            </label>
                                            <p className={`text-sm font-semibold ${status.textClass}`}>
                                                {formatDate(training.expiry_date)}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {formatDateShort(training.expiry_date)}
                                            </p>
                                        </div>

                                        {training.validity_period && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-500 mb-1">
                                                    Validity Period
                                                </label>
                                                <p className="text-sm text-gray-700 flex items-center gap-2">
                                                    <Clock className="h-4 w-4 text-gray-400" />
                                                    {training.validity_period} months
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Quick Actions */}
                                <div className="bg-gray-50 rounded-xl p-6">
                                    <h4 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h4>

                                    <div className="space-y-3">
                                        <Link
                                            href={route('training.edit', training.id)}
                                            className="flex items-center gap-3 w-full px-4 py-3 text-left text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-all duration-200"
                                        >
                                            <Edit className="h-4 w-4" />
                                            Edit Training Record
                                        </Link>

                                        {training.certificate_file && (
                                            <button className="flex items-center gap-3 w-full px-4 py-3 text-left text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-all duration-200">
                                                <Download className="h-4 w-4" />
                                                Download Certificate
                                            </button>
                                        )}

                                        <button className="flex items-center gap-3 w-full px-4 py-3 text-left text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-all duration-200">
                                            <Eye className="h-4 w-4" />
                                            View Full History
                                        </button>

                                        <button className="flex items-center gap-3 w-full px-4 py-3 text-left text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-all duration-200">
                                            <Trash2 className="h-4 w-4" />
                                            Delete Record
                                        </button>
                                    </div>
                                </div>

                                {/* Record Metadata */}
                                <div className="bg-white border border-gray-200 rounded-xl p-6">
                                    <h4 className="text-lg font-semibold text-gray-900 mb-4">Record Information</h4>

                                    <div className="space-y-3 text-xs text-gray-500">
                                        <div className="flex justify-between">
                                            <span>Created:</span>
                                            <span>{formatDateShort(training.created_at)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Last Updated:</span>
                                            <span>{formatDateShort(training.updated_at)}</span>
                                        </div>
                                        {training.created_by && (
                                            <div className="flex justify-between">
                                                <span>Created By:</span>
                                                <span>{training.created_by}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between">
                                            <span>Record ID:</span>
                                            <span className="font-mono">#{training.id}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                        <button
                            onClick={onClose}
                            className="px-6 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors duration-200"
                        >
                            Close
                        </button>
                        <Link
                            href={route('training.edit', training.id)}
                            className="inline-flex items-center gap-2 px-6 py-2 bg-[#439454] text-white text-sm font-medium rounded-lg hover:bg-[#358945] focus:ring-2 focus:ring-[#439454] transition-all duration-200"
                        >
                            <Edit className="h-4 w-4" />
                            Edit Record
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
