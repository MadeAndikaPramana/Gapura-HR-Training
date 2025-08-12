import React, { useState, useEffect } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    Save,
    X,
    User,
    Award,
    Calendar,
    FileText,
    Upload,
    Search,
    AlertCircle,
    CheckCircle,
    Clock,
    Building2,
    Loader2,
} from "lucide-react";

export default function Create({
    employees = [],
    trainingTypes = [],
    organizations = [],
    success = null,
    error = null,
    message = null,
    auth,
}) {
    const [activeSection, setActiveSection] = useState("basic");
    const [searchEmployees, setSearchEmployees] = useState("");
    const [filteredEmployees, setFilteredEmployees] = useState(employees);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        // Basic Information
        employee_id: "",
        training_type_id: "",

        // Certificate Information
        certificate_number: "",
        training_provider: "",
        issue_date: "",
        expiry_date: "",
        validity_period: "",

        // Training Details
        training_location: "",
        training_duration: "",
        instructor_name: "",
        completion_status: "completed",

        // Documents
        certificate_file: null,
        supporting_documents: [],

        // Notes & Compliance
        notes: "",
        compliance_requirements: "",
        renewal_required: true,
        notification_before_expiry: 30,

        // Metadata
        batch_id: "",
        training_cost: "",
        internal_external: "external",
    });

    // Form sections
    const sections = [
        {
            id: "basic",
            name: "Basic Information",
            icon: User,
            fields: ["employee_id", "training_type_id"],
        },
        {
            id: "certificate",
            name: "Certificate Details",
            icon: Award,
            fields: ["certificate_number", "training_provider", "issue_date", "expiry_date"],
        },
        {
            id: "training",
            name: "Training Information",
            icon: Calendar,
            fields: ["training_location", "training_duration", "instructor_name"],
        },
        {
            id: "documents",
            name: "Documents",
            icon: Upload,
            fields: ["certificate_file", "supporting_documents"],
        },
        {
            id: "notes",
            name: "Notes & Compliance",
            icon: FileText,
            fields: ["notes", "compliance_requirements", "renewal_required"],
        },
    ];

    // Filter employees based on search
    useEffect(() => {
        if (!searchEmployees) {
            setFilteredEmployees(employees);
        } else {
            const filtered = employees.filter(employee =>
                employee.nama_lengkap?.toLowerCase().includes(searchEmployees.toLowerCase()) ||
                employee.nip?.toLowerCase().includes(searchEmployees.toLowerCase()) ||
                employee.unit_organisasi?.toLowerCase().includes(searchEmployees.toLowerCase())
            );
            setFilteredEmployees(filtered);
        }
    }, [searchEmployees, employees]);

    // Auto-calculate expiry date when issue date and validity period change
    useEffect(() => {
        if (data.issue_date && data.validity_period) {
            const issueDate = new Date(data.issue_date);
            const expiryDate = new Date(issueDate);
            expiryDate.setMonth(expiryDate.getMonth() + parseInt(data.validity_period));

            setData(prev => ({
                ...prev,
                expiry_date: expiryDate.toISOString().split('T')[0]
            }));
        }
    }, [data.issue_date, data.validity_period]);

    // Handle form submission
    const handleSubmit = (e) => {
        e.preventDefault();
        setIsSubmitting(true);

        post(route('training.store'), {
            onSuccess: () => {
                setIsSubmitting(false);
                // Success handled by redirect
            },
            onError: () => {
                setIsSubmitting(false);
            },
        });
    };

    // Handle cancel
    const handleCancel = () => {
        if (confirm('Are you sure you want to cancel? All changes will be lost.')) {
            history.back();
        }
    };

    // Handle file upload
    const handleFileUpload = (e, field) => {
        const file = e.target.files[0];
        if (file) {
            setData(field, file);
        }
    };

    // Check if section has errors
    const sectionHasErrors = (section) => {
        return section.fields.some(field => errors[field]);
    };

    // Get section completion status
    const getSectionStatus = (section) => {
        const sectionErrors = section.fields.filter(field => errors[field]);
        const filledFields = section.fields.filter(field => {
            const value = data[field];
            return value !== "" && value !== null && value !== undefined;
        });

        if (sectionErrors.length > 0) {
            return { icon: AlertCircle, class: "text-red-500" };
        } else if (filledFields.length === section.fields.length) {
            return { icon: CheckCircle, class: "text-green-500" };
        } else {
            return { icon: Clock, class: "text-gray-400" };
        }
    };

    // Render section content
    const renderSection = () => {
        switch (activeSection) {
            case "basic":
                return (
                    <div className="space-y-6">
                        {/* Employee Selection */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Employee <span className="text-red-500">*</span>
                            </label>

                            {/* Employee Search */}
                            <div className="mb-3">
                                <div className="relative">
                                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                    <input
                                        type="text"
                                        value={searchEmployees}
                                        onChange={(e) => setSearchEmployees(e.target.value)}
                                        placeholder="Search employees by name, NIP, or department..."
                                        className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    />
                                </div>
                            </div>

                            <select
                                value={data.employee_id}
                                onChange={(e) => setData("employee_id", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                required
                            >
                                <option value="">Select Employee</option>
                                {filteredEmployees.map((employee) => (
                                    <option key={employee.id} value={employee.id}>
                                        {employee.nama_lengkap} ({employee.nip}) - {employee.unit_organisasi}
                                    </option>
                                ))}
                            </select>
                            {errors.employee_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.employee_id}</p>
                            )}
                        </div>

                        {/* Training Type */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Training Type <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.training_type_id}
                                onChange={(e) => {
                                    const selectedType = trainingTypes.find(t => t.id == e.target.value);
                                    setData(prev => ({
                                        ...prev,
                                        training_type_id: e.target.value,
                                        validity_period: selectedType?.validity_period || ""
                                    }));
                                }}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                required
                            >
                                <option value="">Select Training Type</option>
                                {trainingTypes.map((type) => (
                                    <option key={type.id} value={type.id}>
                                        {type.name} - {type.category}
                                    </option>
                                ))}
                            </select>
                            {errors.training_type_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.training_type_id}</p>
                            )}
                        </div>

                        {/* Internal/External */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Training Type
                            </label>
                            <div className="flex gap-4">
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        value="internal"
                                        checked={data.internal_external === "internal"}
                                        onChange={(e) => setData("internal_external", e.target.value)}
                                        className="mr-2"
                                    />
                                    Internal Training
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        value="external"
                                        checked={data.internal_external === "external"}
                                        onChange={(e) => setData("internal_external", e.target.value)}
                                        className="mr-2"
                                    />
                                    External Training
                                </label>
                            </div>
                        </div>
                    </div>
                );

            case "certificate":
                return (
                    <div className="space-y-6">
                        {/* Certificate Number */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Certificate Number <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.certificate_number}
                                onChange={(e) => setData("certificate_number", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="Enter certificate number"
                                required
                            />
                            {errors.certificate_number && (
                                <p className="mt-1 text-sm text-red-600">{errors.certificate_number}</p>
                            )}
                        </div>

                        {/* Training Provider */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Training Provider <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.training_provider}
                                onChange={(e) => setData("training_provider", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="Enter training provider name"
                                required
                            />
                            {errors.training_provider && (
                                <p className="mt-1 text-sm text-red-600">{errors.training_provider}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Issue Date */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Issue Date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    value={data.issue_date}
                                    onChange={(e) => setData("issue_date", e.target.value)}
                                    className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    required
                                />
                                {errors.issue_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.issue_date}</p>
                                )}
                            </div>

                            {/* Validity Period */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Validity Period (Months)
                                </label>
                                <input
                                    type="number"
                                    value={data.validity_period}
                                    onChange={(e) => setData("validity_period", e.target.value)}
                                    className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    placeholder="e.g., 24"
                                    min="1"
                                />
                                {errors.validity_period && (
                                    <p className="mt-1 text-sm text-red-600">{errors.validity_period}</p>
                                )}
                            </div>
                        </div>

                        {/* Expiry Date */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Expiry Date <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                value={data.expiry_date}
                                onChange={(e) => setData("expiry_date", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                required
                            />
                            {errors.expiry_date && (
                                <p className="mt-1 text-sm text-red-600">{errors.expiry_date}</p>
                            )}
                            {data.expiry_date && (
                                <p className="mt-1 text-sm text-gray-500">
                                    Certificate will expire on {new Date(data.expiry_date).toLocaleDateString('id-ID', {
                                        weekday: 'long',
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric'
                                    })}
                                </p>
                            )}
                        </div>
                    </div>
                );

            case "training":
                return (
                    <div className="space-y-6">
                        {/* Training Location */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Training Location
                            </label>
                            <input
                                type="text"
                                value={data.training_location}
                                onChange={(e) => setData("training_location", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="Enter training location"
                            />
                            {errors.training_location && (
                                <p className="mt-1 text-sm text-red-600">{errors.training_location}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Training Duration */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Training Duration
                                </label>
                                <input
                                    type="text"
                                    value={data.training_duration}
                                    onChange={(e) => setData("training_duration", e.target.value)}
                                    className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    placeholder="e.g., 3 days, 40 hours"
                                />
                                {errors.training_duration && (
                                    <p className="mt-1 text-sm text-red-600">{errors.training_duration}</p>
                                )}
                            </div>

                            {/* Training Cost */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Training Cost (IDR)
                                </label>
                                <input
                                    type="number"
                                    value={data.training_cost}
                                    onChange={(e) => setData("training_cost", e.target.value)}
                                    className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                    placeholder="Enter cost amount"
                                />
                                {errors.training_cost && (
                                    <p className="mt-1 text-sm text-red-600">{errors.training_cost}</p>
                                )}
                            </div>
                        </div>

                        {/* Instructor Name */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Instructor/Trainer Name
                            </label>
                            <input
                                type="text"
                                value={data.instructor_name}
                                onChange={(e) => setData("instructor_name", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="Enter instructor name"
                            />
                            {errors.instructor_name && (
                                <p className="mt-1 text-sm text-red-600">{errors.instructor_name}</p>
                            )}
                        </div>

                        {/* Completion Status */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Completion Status
                            </label>
                            <select
                                value={data.completion_status}
                                onChange={(e) => setData("completion_status", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                            >
                                <option value="completed">Completed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            {errors.completion_status && (
                                <p className="mt-1 text-sm text-red-600">{errors.completion_status}</p>
                            )}
                        </div>

                        {/* Batch ID */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Batch ID/Group
                            </label>
                            <input
                                type="text"
                                value={data.batch_id}
                                onChange={(e) => setData("batch_id", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="Enter batch or group identifier"
                            />
                            {errors.batch_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.batch_id}</p>
                            )}
                        </div>
                    </div>
                );

            case "documents":
                return (
                    <div className="space-y-6">
                        {/* Certificate File Upload */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Certificate File
                            </label>
                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
                                <div className="text-center">
                                    <Upload className="mx-auto h-12 w-12 text-gray-400" />
                                    <div className="mt-4">
                                        <label className="cursor-pointer">
                                            <span className="mt-2 block text-sm font-medium text-gray-900">
                                                Upload Certificate File
                                            </span>
                                            <input
                                                type="file"
                                                className="sr-only"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                                onChange={(e) => handleFileUpload(e, "certificate_file")}
                                            />
                                        </label>
                                        <p className="mt-2 text-sm text-gray-500">
                                            PDF, JPG, PNG up to 10MB
                                        </p>
                                    </div>
                                </div>
                            </div>
                            {data.certificate_file && (
                                <p className="mt-2 text-sm text-green-600">
                                    ✓ File selected: {data.certificate_file.name}
                                </p>
                            )}
                            {errors.certificate_file && (
                                <p className="mt-1 text-sm text-red-600">{errors.certificate_file}</p>
                            )}
                        </div>

                        {/* Supporting Documents */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Supporting Documents (Optional)
                            </label>
                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
                                <div className="text-center">
                                    <FileText className="mx-auto h-12 w-12 text-gray-400" />
                                    <div className="mt-4">
                                        <label className="cursor-pointer">
                                            <span className="mt-2 block text-sm font-medium text-gray-900">
                                                Upload Supporting Documents
                                            </span>
                                            <input
                                                type="file"
                                                className="sr-only"
                                                multiple
                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                onChange={(e) => setData("supporting_documents", Array.from(e.target.files))}
                                            />
                                        </label>
                                        <p className="mt-2 text-sm text-gray-500">
                                            Multiple files allowed: PDF, JPG, PNG, DOC, DOCX
                                        </p>
                                    </div>
                                </div>
                            </div>
                            {data.supporting_documents?.length > 0 && (
                                <div className="mt-2">
                                    <p className="text-sm text-green-600 mb-2">
                                        ✓ {data.supporting_documents.length} file(s) selected:
                                    </p>
                                    <ul className="text-sm text-gray-600">
                                        {data.supporting_documents.map((file, index) => (
                                            <li key={index}>• {file.name}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            {errors.supporting_documents && (
                                <p className="mt-1 text-sm text-red-600">{errors.supporting_documents}</p>
                            )}
                        </div>
                    </div>
                );

            case "notes":
                return (
                    <div className="space-y-6">
                        {/* Notes */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea
                                value={data.notes}
                                onChange={(e) => setData("notes", e.target.value)}
                                rows={4}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="Enter additional notes or comments..."
                            />
                            {errors.notes && (
                                <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                            )}
                        </div>

                        {/* Compliance Requirements */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Compliance Requirements
                            </label>
                            <textarea
                                value={data.compliance_requirements}
                                onChange={(e) => setData("compliance_requirements", e.target.value)}
                                rows={3}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="Enter specific compliance requirements..."
                            />
                            {errors.compliance_requirements && (
                                <p className="mt-1 text-sm text-red-600">{errors.compliance_requirements}</p>
                            )}
                        </div>

                        {/* Renewal Required */}
                        <div>
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.renewal_required}
                                    onChange={(e) => setData("renewal_required", e.target.checked)}
                                    className="mr-2 rounded border-gray-300 text-[#439454] focus:ring-[#439454]"
                                />
                                <span className="text-sm font-medium text-gray-700">
                                    Renewal Required
                                </span>
                            </label>
                            <p className="mt-1 text-sm text-gray-500">
                                Check if this certificate requires renewal before expiry
                            </p>
                        </div>

                        {/* Notification Before Expiry */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Notification Before Expiry (Days)
                            </label>
                            <input
                                type="number"
                                value={data.notification_before_expiry}
                                onChange={(e) => setData("notification_before_expiry", e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#439454] focus:border-[#439454]"
                                placeholder="30"
                                min="1"
                                max="365"
                            />
                            {errors.notification_before_expiry && (
                                <p className="mt-1 text-sm text-red-600">{errors.notification_before_expiry}</p>
                            )}
                            <p className="mt-1 text-sm text-gray-500">
                                System will send notifications this many days before expiry
                            </p>
                        </div>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <DashboardLayout title="Add Training Record">
            <Head title="Add Training Record - GAPURA ANGKASA Training System" />

            <div className="max-w-6xl mx-auto space-y-6">
                {/* Page Header */}
                <div className="md:flex md:items-center md:justify-between">
                    <div className="min-w-0 flex-1">
                        <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                            Add Training Record
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Create a new training record for an employee
                        </p>
                    </div>
                </div>

                {/* Section Navigation */}
                <div className="flex flex-wrap gap-2">
                    {sections.map((section) => {
                        const status = getSectionStatus(section);
                        const StatusIcon = status.icon;

                        return (
                            <button
                                key={section.id}
                                onClick={() => setActiveSection(section.id)}
                                className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 ${
                                    activeSection === section.id
                                        ? "bg-[#439454] text-white shadow-lg"
                                        : "text-gray-600 hover:bg-white hover:text-[#439454]"
                                }`}
                            >
                                <section.icon className="w-4 h-4" />
                                {section.name}
                                <StatusIcon className={`w-4 h-4 ${status.class}`} />
                            </button>
                        );
                    })}
                </div>

                {/* Form */}
                <form
                    onSubmit={handleSubmit}
                    className="bg-white border-2 border-gray-200 shadow-xl rounded-2xl"
                >
                    <div className="p-6 space-y-6">{renderSection()}</div>

                    {/* Form Actions */}
                    <div className="flex flex-col gap-4 p-6 border-t border-gray-200 sm:flex-row sm:items-center sm:justify-end bg-gray-50 rounded-b-2xl">
                        <button
                            type="button"
                            onClick={handleCancel}
                            disabled={processing || isSubmitting}
                            className="flex items-center justify-center gap-2 px-6 py-3 text-gray-600 transition-all duration-300 border-2 border-gray-300 rounded-xl hover:border-red-400 hover:text-red-600 focus:ring-4 focus:ring-red-400/20 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <X className="w-4 h-4" />
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing || isSubmitting}
                            className="flex items-center justify-center gap-2 px-6 py-3 text-white transition-all duration-300 bg-[#439454] border-2 border-[#439454] rounded-xl hover:bg-[#439454]/90 focus:ring-4 focus:ring-[#439454]/20 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing || isSubmitting ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                    Saving...
                                </>
                            ) : (
                                <>
                                    <Save className="w-4 h-4" />
                                    Save Training Record
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
