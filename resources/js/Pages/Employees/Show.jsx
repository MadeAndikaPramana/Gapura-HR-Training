// ============================================================================
// EMPLOYEE SHOW/DETAIL PAGE - KONSISTEN DENGAN UI FIRMAN HR GAPURA
// ============================================================================

import React, { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    ArrowLeft,
    Edit,
    Trash2,
    Phone,
    Mail,
    MapPin,
    Calendar,
    Badge,
    Building2,
    User,
    Clock,
    Award,
    AlertTriangle,
    CheckCircle,
    Download,
    Eye,
    FileText,
} from "lucide-react";

export default function EmployeeShow({
    employee,
    title = "Detail Karyawan",
    auth,
}) {
    const [activeTab, setActiveTab] = useState('overview');

    const handleDeleteEmployee = () => {
        if (confirm(`Yakin ingin menghapus karyawan ${employee.nama_lengkap}?`)) {
            router.delete(route('employees.destroy', employee.id), {
                onSuccess: () => {
                    router.visit(route('employees.index'));
                },
                onError: () => {
                    alert('Terjadi kesalahan saat menghapus karyawan');
                }
            });
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const calculateAge = (birthDate) => {
        if (!birthDate) return '-';
        const today = new Date();
        const birth = new Date(birthDate);
        const age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            return age - 1;
        }
        return age;
    };

    const TabButton = ({ id, label, icon: Icon, count = null }) => (
        <button
            onClick={() => setActiveTab(id)}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-300 ${
                activeTab === id
                    ? 'bg-[#439454] text-white shadow-lg'
                    : 'text-gray-600 hover:text-[#439454] hover:bg-gray-100'
            }`}
        >
            <Icon className="w-4 h-4" />
            {label}
            {count !== null && (
                <span className={`px-2 py-0.5 text-xs rounded-full ${
                    activeTab === id ? 'bg-white text-[#439454]' : 'bg-gray-200 text-gray-600'
                }`}>
                    {count}
                </span>
            )}
        </button>
    );

    const InfoCard = ({ title, children, icon: Icon }) => (
        <div className="bg-white rounded-xl border border-gray-100 p-6">
            <div className="flex items-center gap-2 mb-4">
                <Icon className="w-5 h-5 text-[#439454]" />
                <h3 className="font-semibold text-gray-900">{title}</h3>
            </div>
            {children}
        </div>
    );

    const InfoRow = ({ label, value, icon: Icon = null }) => (
        <div className="flex items-start gap-3 py-2">
            {Icon && <Icon className="w-4 h-4 text-gray-400 mt-0.5" />}
            <div className="flex-1">
                <div className="text-sm text-gray-500">{label}</div>
                <div className="text-sm font-medium text-gray-900">
                    {value || '-'}
                </div>
            </div>
        </div>
    );

    return (
        <DashboardLayout>
            <Head title={title} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('employees.index')}
                            className="p-2 text-gray-500 hover:text-[#439454] hover:bg-gray-100 rounded-lg transition-all duration-200"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                            <p className="text-sm text-gray-600 mt-1">
                                Informasi lengkap karyawan {employee.nama_lengkap}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={route('employees.edit', employee.id)}
                            className="flex items-center gap-2 px-4 py-2 bg-[#439454] text-white rounded-xl hover:bg-[#358945] transition-all duration-300 shadow-lg hover:shadow-gapura font-medium"
                        >
                            <Edit className="w-4 h-4" />
                            Edit Karyawan
                        </Link>

                        <button
                            onClick={handleDeleteEmployee}
                            className="flex items-center gap-2 px-4 py-2 border-2 border-red-500 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all duration-300 font-medium"
                        >
                            <Trash2 className="w-4 h-4" />
                            Hapus
                        </button>
                    </div>
                </div>

                {/* Employee Header Card */}
                <div className="bg-gradient-to-br from-[#439454] to-[#358945] rounded-2xl p-6 text-white shadow-gapura-lg">
                    <div className="flex items-start gap-6">
                        {/* Avatar */}
                        <div className="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center text-2xl font-bold">
                            {employee.nama_lengkap?.charAt(0).toUpperCase()}
                        </div>

                        {/* Basic Info */}
                        <div className="flex-1">
                            <h2 className="text-2xl font-bold mb-1">{employee.nama_lengkap}</h2>
                            <p className="text-green-100 mb-4">{employee.jabatan || 'Karyawan'}</p>

                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <div className="text-green-100 text-sm">NIP</div>
                                    <div className="font-medium">{employee.nip}</div>
                                </div>
                                <div>
                                    <div className="text-green-100 text-sm">NIK</div>
                                    <div className="font-medium">{employee.nik || '-'}</div>
                                </div>
                                <div>
                                    <div className="text-green-100 text-sm">Department</div>
                                    <div className="font-medium">{employee.department}</div>
                                </div>
                                <div>
                                    <div className="text-green-100 text-sm">Status</div>
                                    <div className="flex items-center gap-2">
                                        <span className={`w-2 h-2 rounded-full ${
                                            employee.is_active ? 'bg-green-300' : 'bg-red-300'
                                        }`}></span>
                                        <span className="font-medium">
                                            {employee.is_active ? 'Aktif' : 'Tidak Aktif'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Tab Navigation */}
                <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100">
                        <div className="flex items-center gap-2 overflow-x-auto">
                            <TabButton id="overview" label="Overview" icon={Eye} />
                            <TabButton id="personal" label="Data Personal" icon={User} />
                            <TabButton id="work" label="Data Pekerjaan" icon={Building2} />
                            <TabButton id="contact" label="Kontak" icon={Phone} />
                            <TabButton id="training" label="Training Records" icon={Award} count={0} />
                        </div>
                    </div>

                    <div className="p-6">
                        {/* Overview Tab */}
                        {activeTab === 'overview' && (
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Quick Stats */}
                                <div className="space-y-4">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Information</h3>

                                    <div className="bg-gray-50 rounded-xl p-4 space-y-3">
                                        <InfoRow
                                            label="Nama Lengkap"
                                            value={employee.nama_lengkap}
                                            icon={User}
                                        />
                                        <InfoRow
                                            label="Department"
                                            value={employee.department}
                                            icon={Building2}
                                        />
                                        <InfoRow
                                            label="Unit Organisasi"
                                            value={employee.unit_organisasi}
                                            icon={Building2}
                                        />
                                        <InfoRow
                                            label="Status Pegawai"
                                            value={employee.status_pegawai}
                                            icon={Badge}
                                        />
                                        {employee.handphone && (
                                            <InfoRow
                                                label="Handphone"
                                                value={employee.handphone}
                                                icon={Phone}
                                            />
                                        )}
                                        {employee.email && (
                                            <InfoRow
                                                label="Email"
                                                value={employee.email}
                                                icon={Mail}
                                            />
                                        )}
                                    </div>
                                </div>

                                {/* Training Status Preview */}
                                <div className="space-y-4">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Training Status</h3>

                                    <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                                        <div className="flex items-center gap-2 mb-2">
                                            <Clock className="w-5 h-5 text-yellow-600" />
                                            <span className="font-medium text-yellow-800">
                                                Training Records (Phase 2)
                                            </span>
                                        </div>
                                        <p className="text-sm text-yellow-700">
                                            Training records dan sertifikasi akan tersedia pada Phase 2 implementasi sistem.
                                        </p>
                                    </div>

                                    {/* Placeholder for future training stats */}
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="bg-white border border-gray-200 rounded-lg p-4 text-center">
                                            <div className="text-2xl font-bold text-gray-400">-</div>
                                            <div className="text-sm text-gray-500">Total Training</div>
                                        </div>
                                        <div className="bg-white border border-gray-200 rounded-lg p-4 text-center">
                                            <div className="text-2xl font-bold text-gray-400">-</div>
                                            <div className="text-sm text-gray-500">Valid Certificates</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Personal Information Tab */}
                        {activeTab === 'personal' && (
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <InfoCard title="Identitas Personal" icon={User}>
                                    <div className="space-y-3">
                                        <InfoRow label="Nama Lengkap" value={employee.nama_lengkap} />
                                        <InfoRow label="NIP" value={employee.nip} />
                                        <InfoRow label="NIK" value={employee.nik} />
                                        <InfoRow
                                            label="Jenis Kelamin"
                                            value={employee.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}
                                        />
                                    </div>
                                </InfoCard>

                                <InfoCard title="Data Kelahiran" icon={Calendar}>
                                    <div className="space-y-3">
                                        <InfoRow label="Tempat Lahir" value={employee.tempat_lahir} />
                                        <InfoRow label="Tanggal Lahir" value={formatDate(employee.tanggal_lahir)} />
                                        <InfoRow
                                            label="Usia"
                                            value={employee.tanggal_lahir ? `${calculateAge(employee.tanggal_lahir)} tahun` : '-'}
                                        />
                                    </div>
                                </InfoCard>
                            </div>
                        )}

                        {/* Work Information Tab */}
                        {activeTab === 'work' && (
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <InfoCard title="Struktur Organisasi" icon={Building2}>
                                    <div className="space-y-3">
                                        <InfoRow label="Department" value={employee.department} />
                                        <InfoRow label="Unit Organisasi" value={employee.unit_organisasi} />
                                        <InfoRow label="Jabatan" value={employee.jabatan} />
                                        <InfoRow label="Status Pegawai" value={employee.status_pegawai} />
                                    </div>
                                </InfoCard>

                                <InfoCard title="Lokasi & Provider" icon={MapPin}>
                                    <div className="space-y-3">
                                        <InfoRow label="Lokasi Kerja" value={employee.lokasi_kerja} />
                                        <InfoRow label="Cabang" value={employee.cabang} />
                                        <InfoRow label="Provider/Vendor" value={employee.provider} />
                                        <InfoRow
                                            label="Status Kerja"
                                            value={employee.is_active ? 'Aktif' : 'Tidak Aktif'}
                                        />
                                    </div>
                                </InfoCard>
                            </div>
                        )}

                        {/* Contact Information Tab */}
                        {activeTab === 'contact' && (
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <InfoCard title="Kontak" icon={Phone}>
                                    <div className="space-y-3">
                                        <InfoRow
                                            label="Handphone"
                                            value={employee.handphone}
                                            icon={Phone}
                                        />
                                        <InfoRow
                                            label="Email"
                                            value={employee.email}
                                            icon={Mail}
                                        />
                                    </div>

                                    {(employee.handphone || employee.email) && (
                                        <div className="mt-4 pt-4 border-t border-gray-200">
                                            <div className="flex gap-2">
                                                {employee.handphone && (
                                                    <a
                                                        href={`tel:${employee.handphone}`}
                                                        className="flex items-center gap-2 px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm"
                                                    >
                                                        <Phone className="w-4 h-4" />
                                                        Call
                                                    </a>
                                                )}
                                                {employee.email && (
                                                    <a
                                                        href={`mailto:${employee.email}`}
                                                        className="flex items-center gap-2 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 text-sm"
                                                    >
                                                        <Mail className="w-4 h-4" />
                                                        Email
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </InfoCard>

                                <InfoCard title="Alamat" icon={MapPin}>
                                    <div className="space-y-3">
                                        <div>
                                            <div className="text-sm text-gray-500 mb-1">Alamat Lengkap</div>
                                            <div className="text-sm text-gray-900">
                                                {employee.alamat ? (
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        {employee.alamat}
                                                    </div>
                                                ) : (
                                                    '-'
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </InfoCard>
                            </div>
                        )}

                        {/* Training Records Tab */}
                        {activeTab === 'training' && (
                            <div className="text-center py-12">
                                <Award className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                    Training Records (Coming Soon)
                                </h3>
                                <p className="text-gray-500 mb-6">
                                    Fitur training records dan manajemen sertifikasi akan tersedia pada Phase 2.
                                </p>
                                <Link
                                    href={route('training.index')}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-[#439454] text-white rounded-lg hover:bg-[#358945] transition-colors duration-200"
                                >
                                    <FileText className="w-4 h-4" />
                                    Lihat Training Dashboard
                                </Link>
                            </div>
                        )}
                    </div>
                </div>

                {/* System Information */}
                <div className="bg-gray-50 rounded-xl p-4">
                    <div className="text-sm text-gray-500 space-y-1">
                        <div>Data terakhir diperbarui: {formatDate(employee.updated_at)}</div>
                        <div>ID Sistem: {employee.id}</div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
