// ============================================================================
// EMPLOYEE EDIT COMPONENT - WORKING VERSION
// ============================================================================
// Fixed: Form population, update submission, route handling

import React, { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    ArrowLeft,
    Save,
    User,
    Building2,
    Phone,
    Mail,
    MapPin,
    Calendar,
    Badge,
    AlertCircle,
} from "lucide-react";

export default function EmployeeEdit({
    employee,
    departments = [],
    units = [],
    statusOptions = [],
    title = "Edit Karyawan",
    auth,
    errors = {}
}) {
    // LOCAL STATE for form inputs - POPULATED with existing data
    const [formData, setFormData] = useState({
        nip: employee?.nip || '',
        nama_lengkap: employee?.nama_lengkap || '',
        department: employee?.department || '',
        unit_organisasi: employee?.unit_organisasi || '',
        jenis_kelamin: employee?.jenis_kelamin || 'L',
        tempat_lahir: employee?.tempat_lahir || '',
        tanggal_lahir: employee?.tanggal_lahir || '',
        jabatan: employee?.jabatan || '',
        status_pegawai: employee?.status_pegawai || 'PEGAWAI TETAP',
        lokasi_kerja: employee?.lokasi_kerja || '',
        cabang: employee?.cabang || '',
        provider: employee?.provider || '',
        handphone: employee?.handphone || '',
        email: employee?.email || '',
        alamat: employee?.alamat || '',
    });

    const [activeTab, setActiveTab] = useState('personal');
    const [processing, setProcessing] = useState(false);

    // Handle input changes
    const handleChange = (name, value) => {
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    // FIXED: Edit form submission with PUT method
    const handleSubmit = (e) => {
        e.preventDefault();

        console.log('Edit form submitted with data:', formData); // Debug log
        console.log('Employee ID:', employee.id); // Debug log

        setProcessing(true);

        // PUT method for update
        router.put(`/employees/${employee.id}`, formData, {
            onSuccess: () => {
                console.log('Employee updated successfully');
            },
            onError: (errors) => {
                console.error('Update errors:', errors);
            },
            onFinish: () => {
                setProcessing(false);
            }
        });
    };

    // Default options if not passed from controller
    const departmentOptions = departments.length > 0 ? departments : [
        'DEDICATED', 'LOADING', 'RAMP', 'LOCO', 'ULD',
        'LOST & FOUND', 'CARGO', 'ARRIVAL', 'GSE OPERATOR',
        'FLOP', 'AVSEC', 'PORTER'
    ];

    const employeeStatusOptions = statusOptions.length > 0 ? statusOptions : [
        'PEGAWAI TETAP', 'PKWT', 'TAD PAKET SDM', 'TAD PAKET PEKERJAAN'
    ];

    return (
        <DashboardLayout auth={auth} header={title}>
            <Head title={title} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href="/employees"
                            className="p-2 text-gray-500 hover:text-[#439454] hover:bg-gray-100 rounded-lg transition-all duration-200"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                            <p className="text-sm text-gray-600 mt-1">
                                Perbarui informasi karyawan {employee?.nama_lengkap}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Debug Info - Remove in production */}
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 className="font-semibold text-yellow-800">Debug Info (Edit Mode):</h3>
                    <p className="text-sm text-yellow-700">Processing: {processing ? 'true' : 'false'}</p>
                    <p className="text-sm text-yellow-700">Employee ID: {employee?.id}</p>
                    <p className="text-sm text-yellow-700">Current NIP: {formData.nip}</p>
                    <p className="text-sm text-yellow-700">Current Nama: {formData.nama_lengkap}</p>
                    <p className="text-sm text-yellow-700">Department: {formData.department}</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Tab Navigation */}
                    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center gap-2 mb-6">
                            <button
                                type="button"
                                onClick={() => setActiveTab('personal')}
                                className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-300 ${
                                    activeTab === 'personal'
                                        ? 'bg-[#439454] text-white shadow-lg'
                                        : 'text-gray-600 hover:text-[#439454] hover:bg-gray-100'
                                }`}
                            >
                                <User className="w-4 h-4" />
                                Data Personal
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('work')}
                                className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-300 ${
                                    activeTab === 'work'
                                        ? 'bg-[#439454] text-white shadow-lg'
                                        : 'text-gray-600 hover:text-[#439454] hover:bg-gray-100'
                                }`}
                            >
                                <Building2 className="w-4 h-4" />
                                Data Pekerjaan
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('contact')}
                                className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-300 ${
                                    activeTab === 'contact'
                                        ? 'bg-[#439454] text-white shadow-lg'
                                        : 'text-gray-600 hover:text-[#439454] hover:bg-gray-100'
                                }`}
                            >
                                <Phone className="w-4 h-4" />
                                Kontak & Alamat
                            </button>
                        </div>

                        {/* Personal Information Tab */}
                        {activeTab === 'personal' && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* NIP */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            NIP <span className="text-red-500 ml-1">*</span>
                                        </label>
                                        <div className="relative">
                                            <Badge className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="text"
                                                value={formData.nip}
                                                onChange={(e) => handleChange('nip', e.target.value)}
                                                placeholder="Masukkan NIP karyawan"
                                                className={`w-full pl-10 pr-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300 ${
                                                    errors.nip ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                                required
                                            />
                                        </div>
                                        {errors.nip && (
                                            <div className="flex items-center gap-2 text-sm text-red-600">
                                                <AlertCircle className="w-4 h-4" />
                                                <span>{errors.nip}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Nama Lengkap */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Nama Lengkap <span className="text-red-500 ml-1">*</span>
                                        </label>
                                        <div className="relative">
                                            <User className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="text"
                                                value={formData.nama_lengkap}
                                                onChange={(e) => handleChange('nama_lengkap', e.target.value)}
                                                placeholder="Masukkan nama lengkap"
                                                className={`w-full pl-10 pr-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300 ${
                                                    errors.nama_lengkap ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                                required
                                            />
                                        </div>
                                        {errors.nama_lengkap && (
                                            <div className="flex items-center gap-2 text-sm text-red-600">
                                                <AlertCircle className="w-4 h-4" />
                                                <span>{errors.nama_lengkap}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Jenis Kelamin */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Jenis Kelamin <span className="text-red-500 ml-1">*</span>
                                        </label>
                                        <select
                                            value={formData.jenis_kelamin}
                                            onChange={(e) => handleChange('jenis_kelamin', e.target.value)}
                                            className="w-full pl-3 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            required
                                        >
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>

                                    {/* Tempat Lahir */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Tempat Lahir
                                        </label>
                                        <div className="relative">
                                            <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="text"
                                                value={formData.tempat_lahir}
                                                onChange={(e) => handleChange('tempat_lahir', e.target.value)}
                                                placeholder="Masukkan tempat lahir"
                                                className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            />
                                        </div>
                                    </div>

                                    {/* Tanggal Lahir */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Tanggal Lahir
                                        </label>
                                        <div className="relative">
                                            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="date"
                                                value={formData.tanggal_lahir}
                                                onChange={(e) => handleChange('tanggal_lahir', e.target.value)}
                                                className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Work Information Tab */}
                        {activeTab === 'work' && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Department */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Department <span className="text-red-500 ml-1">*</span>
                                        </label>
                                        <select
                                            value={formData.department}
                                            onChange={(e) => handleChange('department', e.target.value)}
                                            className={`w-full pl-3 pr-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300 ${
                                                errors.department ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            required
                                        >
                                            <option value="">Pilih Department</option>
                                            {departmentOptions.map((dept) => (
                                                <option key={dept} value={dept}>{dept}</option>
                                            ))}
                                        </select>
                                        {errors.department && (
                                            <div className="flex items-center gap-2 text-sm text-red-600">
                                                <AlertCircle className="w-4 h-4" />
                                                <span>{errors.department}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Unit Organisasi */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Unit Organisasi <span className="text-red-500 ml-1">*</span>
                                        </label>
                                        <div className="relative">
                                            <Building2 className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="text"
                                                value={formData.unit_organisasi}
                                                onChange={(e) => handleChange('unit_organisasi', e.target.value)}
                                                placeholder="Masukkan unit organisasi"
                                                className={`w-full pl-10 pr-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300 ${
                                                    errors.unit_organisasi ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                                required
                                            />
                                        </div>
                                        {errors.unit_organisasi && (
                                            <div className="flex items-center gap-2 text-sm text-red-600">
                                                <AlertCircle className="w-4 h-4" />
                                                <span>{errors.unit_organisasi}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Jabatan */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Jabatan
                                        </label>
                                        <div className="relative">
                                            <Badge className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="text"
                                                value={formData.jabatan}
                                                onChange={(e) => handleChange('jabatan', e.target.value)}
                                                placeholder="Masukkan jabatan"
                                                className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            />
                                        </div>
                                    </div>

                                    {/* Status Pegawai */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Status Pegawai <span className="text-red-500 ml-1">*</span>
                                        </label>
                                        <select
                                            value={formData.status_pegawai}
                                            onChange={(e) => handleChange('status_pegawai', e.target.value)}
                                            className="w-full pl-3 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            required
                                        >
                                            {employeeStatusOptions.map((status) => (
                                                <option key={status} value={status}>{status}</option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Lokasi Kerja */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Lokasi Kerja
                                        </label>
                                        <div className="relative">
                                            <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="text"
                                                value={formData.lokasi_kerja}
                                                onChange={(e) => handleChange('lokasi_kerja', e.target.value)}
                                                placeholder="Masukkan lokasi kerja"
                                                className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            />
                                        </div>
                                    </div>

                                    {/* Cabang */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Cabang
                                        </label>
                                        <div className="relative">
                                            <Building2 className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="text"
                                                value={formData.cabang}
                                                onChange={(e) => handleChange('cabang', e.target.value)}
                                                placeholder="Masukkan cabang"
                                                className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            />
                                        </div>
                                    </div>

                                    {/* Provider */}
                                    <div className="md:col-span-2 space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Provider/Vendor
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.provider}
                                            onChange={(e) => handleChange('provider', e.target.value)}
                                            placeholder="Masukkan provider/vendor (jika ada)"
                                            className="w-full pl-3 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Contact Information Tab */}
                        {activeTab === 'contact' && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Handphone */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Nomor Handphone
                                        </label>
                                        <div className="relative">
                                            <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="tel"
                                                value={formData.handphone}
                                                onChange={(e) => handleChange('handphone', e.target.value)}
                                                placeholder="Masukkan nomor handphone"
                                                className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            />
                                        </div>
                                    </div>

                                    {/* Email */}
                                    <div className="space-y-2">
                                        <label className="block text-sm font-medium text-gray-700">
                                            Email
                                        </label>
                                        <div className="relative">
                                            <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                            <input
                                                type="email"
                                                value={formData.email}
                                                onChange={(e) => handleChange('email', e.target.value)}
                                                placeholder="Masukkan alamat email"
                                                className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Alamat */}
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium text-gray-700">
                                        Alamat
                                    </label>
                                    <div className="relative">
                                        <MapPin className="absolute left-3 top-3 text-gray-400 w-4 h-4" />
                                        <textarea
                                            value={formData.alamat}
                                            onChange={(e) => handleChange('alamat', e.target.value)}
                                            placeholder="Masukkan alamat lengkap"
                                            rows="4"
                                            className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#439454] focus:border-transparent transition-all duration-300 resize-none"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Action Buttons */}
                    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <div className="flex items-center justify-between">
                            <Link
                                href="/employees"
                                className="flex items-center gap-2 px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-xl hover:border-gray-400 transition-all duration-300 font-medium"
                            >
                                Batal
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="flex items-center gap-2 px-6 py-2.5 bg-[#439454] text-white rounded-xl hover:bg-[#358945] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:shadow-xl font-medium"
                            >
                                {processing ? (
                                    <>
                                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                        Memperbarui...
                                    </>
                                ) : (
                                    <>
                                        <Save className="w-4 h-4" />
                                        Perbarui Karyawan
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
