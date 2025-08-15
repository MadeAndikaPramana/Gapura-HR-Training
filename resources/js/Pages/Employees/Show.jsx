import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Edit, Trash2, User, Mail, Phone, MapPin, Briefcase, Calendar, Award } from 'lucide-react';

export default function Show({
    auth,
    employee,
    title = "Detail Karyawan",
    subtitle = "Informasi lengkap karyawan"
}) {
    const handleDelete = () => {
        if (confirm(`Apakah Anda yakin ingin menghapus data ${employee.nama_lengkap}?`)) {
            router.delete(route('employees.destroy', employee.id));
        }
    };

    const InfoRow = ({ label, value, icon: Icon }) => (
        <div className="flex items-start space-x-3">
            {Icon && <Icon className="w-5 h-5 text-gray-400 mt-0.5" />}
            <div className="flex-1">
                <dt className="text-sm font-medium text-gray-500">{label}</dt>
                <dd className="text-sm text-gray-900 mt-1">{value || '-'}</dd>
            </div>
        </div>
    );

    const StatusBadge = ({ status, type = 'default' }) => {
        const baseClasses = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";

        const variants = {
            active: "bg-green-100 text-green-800",
            inactive: "bg-red-100 text-red-800",
            warning: "bg-yellow-100 text-yellow-800",
            default: "bg-gray-100 text-gray-800"
        };

        let variant = 'default';
        if (status === 'Aktif' || status === 'active') variant = 'active';
        else if (status === 'Non-Aktif' || status === 'inactive') variant = 'inactive';
        else if (status === 'Cuti') variant = 'warning';

        return (
            <span className={`${baseClasses} ${variants[variant]}`}>
                {status}
            </span>
        );
    };

    return (
        <DashboardLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {title}
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            {subtitle} - {employee.nama_lengkap}
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Link
                            href={route('employees.edit', employee.id)}
                            className="inline-flex items-center px-4 py-2 bg-[#439454] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#367a41] focus:outline-none focus:ring-2 focus:ring-[#439454] focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <Edit className="w-4 h-4 mr-2" />
                            Edit
                        </Link>
                        <button
                            onClick={handleDelete}
                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <Trash2 className="w-4 h-4 mr-2" />
                            Hapus
                        </button>
                        <Link
                            href={route('employees.index')}
                            className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Kembali
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`${employee.nama_lengkap} - Detail Karyawan`} />

            <div className="py-12">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8">

                    {/* Employee Header Card */}
                    <div className="bg-gradient-to-r from-[#439454] to-[#367a41] rounded-lg p-6 mb-6 text-white">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                    <User className="w-10 h-10" />
                                </div>
                            </div>
                            <div className="ml-6">
                                <h1 className="text-2xl font-bold">{employee.nama_lengkap}</h1>
                                <div className="mt-2 space-y-1">
                                    <p className="text-lg opacity-90">
                                        {employee.jabatan || employee.unit_organisasi}
                                    </p>
                                    <div className="flex items-center space-x-4 text-sm opacity-75">
                                        <span>NIP: {employee.nip}</span>
                                        <span>•</span>
                                        <span>NIK: {employee.nik}</span>
                                        <span>•</span>
                                        <span>Department: {employee.department}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="ml-auto">
                                <div className="text-right">
                                    <div className="mb-2">
                                        <StatusBadge status={employee.status_kerja} />
                                    </div>
                                    <div>
                                        <span className="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                            {employee.status_pegawai}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {/* Personal Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <User className="w-5 h-5 mr-2 text-[#439454]" />
                                    Informasi Personal
                                </h3>
                                <dl className="space-y-4">
                                    <InfoRow
                                        label="Nama Lengkap"
                                        value={employee.nama_lengkap}
                                    />
                                    <InfoRow
                                        label="Jenis Kelamin"
                                        value={employee.jenis_kelamin === 'L' ? 'Laki-laki' : employee.jenis_kelamin === 'P' ? 'Perempuan' : employee.jenis_kelamin}
                                    />
                                    <InfoRow
                                        label="Tempat, Tanggal Lahir"
                                        value={employee.tempat_lahir && employee.tanggal_lahir ?
                                            `${employee.tempat_lahir}, ${new Date(employee.tanggal_lahir).toLocaleDateString('id-ID')}` :
                                            employee.tempat_lahir || employee.tanggal_lahir}
                                        icon={Calendar}
                                    />
                                    <InfoRow
                                        label="Usia"
                                        value={employee.usia ? `${employee.usia} tahun` : ''}
                                    />
                                    <InfoRow
                                        label="Email"
                                        value={employee.email}
                                        icon={Mail}
                                    />
                                    <InfoRow
                                        label="Handphone"
                                        value={employee.handphone}
                                        icon={Phone}
                                    />
                                    <InfoRow
                                        label="Alamat"
                                        value={employee.alamat}
                                        icon={MapPin}
                                    />
                                </dl>
                            </div>
                        </div>

                        {/* Work Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <Briefcase className="w-5 h-5 mr-2 text-[#439454]" />
                                    Informasi Pekerjaan
                                </h3>
                                <dl className="space-y-4">
                                    <InfoRow
                                        label="NIP"
                                        value={employee.nip}
                                    />
                                    <InfoRow
                                        label="NIK"
                                        value={employee.nik}
                                    />
                                    <InfoRow
                                        label="Department MPGA"
                                        value={employee.department}
                                    />
                                    <InfoRow
                                        label="Unit Organisasi"
                                        value={employee.unit_organisasi}
                                    />
                                    <InfoRow
                                        label="Unit Kerja"
                                        value={employee.unit_kerja}
                                    />
                                    <InfoRow
                                        label="Jabatan"
                                        value={employee.jabatan}
                                    />
                                    <InfoRow
                                        label="Kelompok Jabatan"
                                        value={employee.kelompok_jabatan}
                                    />
                                    <div className="flex items-start space-x-3">
                                        <div className="flex-1">
                                            <dt className="text-sm font-medium text-gray-500">Status Pegawai</dt>
                                            <dd className="text-sm text-gray-900 mt-1">
                                                <StatusBadge status={employee.status_pegawai} />
                                            </dd>
                                        </div>
                                    </div>
                                    <div className="flex items-start space-x-3">
                                        <div className="flex-1">
                                            <dt className="text-sm font-medium text-gray-500">Status Kerja</dt>
                                            <dd className="text-sm text-gray-900 mt-1">
                                                <StatusBadge status={employee.status_kerja} />
                                            </dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* Additional Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <MapPin className="w-5 h-5 mr-2 text-[#439454]" />
                                    Informasi Tambahan
                                </h3>
                                <dl className="space-y-4">
                                    <InfoRow
                                        label="Lokasi Kerja"
                                        value={employee.lokasi_kerja}
                                    />
                                    <InfoRow
                                        label="Cabang"
                                        value={employee.cabang}
                                    />
                                    <InfoRow
                                        label="Provider"
                                        value={employee.provider}
                                    />
                                    <InfoRow
                                        label="Kode Organisasi"
                                        value={employee.kode_organisasi}
                                    />
                                    <InfoRow
                                        label="Nama Organisasi"
                                        value={employee.nama_organisasi}
                                    />
                                </dl>
                            </div>
                        </div>

                        {/* Training Records Preview */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <Award className="w-5 h-5 mr-2 text-[#439454]" />
                                    Training & Sertifikasi
                                </h3>

                                {/* Training Records akan ditampilkan di sini saat Phase 2 */}
                                <div className="text-center py-8 text-gray-500">
                                    <Award className="w-12 h-12 mx-auto text-gray-300 mb-4" />
                                    <p className="text-sm">Data training dan sertifikasi</p>
                                    <p className="text-xs">akan tersedia di Phase 2</p>
                                    <Link
                                        href="#"
                                        className="inline-flex items-center mt-3 text-sm text-[#439454] hover:text-[#367a41]"
                                    >
                                        Lihat Training Records →
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </div>

                    {/* System Information */}
                    <div className="mt-6 bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">
                                Informasi Sistem
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Dibuat</dt>
                                    <dd className="text-sm text-gray-900 mt-1">
                                        {employee.created_at ? new Date(employee.created_at).toLocaleString('id-ID') : '-'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Terakhir Diupdate</dt>
                                    <dd className="text-sm text-gray-900 mt-1">
                                        {employee.updated_at ? new Date(employee.updated_at).toLocaleString('id-ID') : '-'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Status Sistem</dt>
                                    <dd className="text-sm text-gray-900 mt-1">
                                        <StatusBadge status={employee.status} />
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </DashboardLayout>
    );
}
