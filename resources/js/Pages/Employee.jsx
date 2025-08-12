import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    Plus,
    Search,
    Edit3,
    Trash2,
    Save,
    X,
    Users,
    UserPlus,
    AlertCircle,
    Check
} from 'lucide-react';

export default function Employee({
    employees = { data: [] },
    pagination = {},
    success,
    error
}) {
    const [searchTerm, setSearchTerm] = useState('');
    const [showAddForm, setShowAddForm] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Form states
    const [formData, setFormData] = useState({
        nama_lengkap: '',
        nip: '',
        nik: ''
    });

    const [editData, setEditData] = useState({
        nama_lengkap: '',
        nip: '',
        nik: ''
    });

    // Search handler
    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('employees.index'), {
            search: searchTerm
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Add new employee
    const handleAdd = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('employees.store'), formData, {
            onSuccess: () => {
                setShowAddForm(false);
                setFormData({ nama_lengkap: '', nip: '', nik: '' });
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    // Start editing
    const startEdit = (employee) => {
        setEditingId(employee.id);
        setEditData({
            nama_lengkap: employee.nama_lengkap || '',
            nip: employee.nip || '',
            nik: employee.nik || ''
        });
    };

    // Save edit
    const saveEdit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.put(route('employees.update', editingId), editData, {
            onSuccess: () => {
                setEditingId(null);
                setEditData({ nama_lengkap: '', nip: '', nik: '' });
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    // Cancel edit
    const cancelEdit = () => {
        setEditingId(null);
        setEditData({ nama_lengkap: '', nip: '', nik: '' });
    };

    // Delete employee
    const handleDelete = (employee) => {
        if (confirm(`Hapus karyawan ${employee.nama_lengkap}?`)) {
            router.delete(route('employees.destroy', employee.id));
        }
    };

    // Filter employees by search
    const filteredEmployees = employees.data.filter(employee => {
        if (!searchTerm) return true;
        const search = searchTerm.toLowerCase();
        return (
            employee.nama_lengkap?.toLowerCase().includes(search) ||
            employee.nip?.toLowerCase().includes(search) ||
            employee.nik?.toLowerCase().includes(search)
        );
    });

    return (
        <DashboardLayout title="Employee">
            <Head title="Employee" />

            <div className="space-y-6">
                {/* Header */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-center space-x-3">
                            <div className="p-3 bg-gapura-green rounded-xl">
                                <Users className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Employee</h1>
                                <p className="text-gray-600">Kelola data karyawan GAPURA</p>
                            </div>
                        </div>
                        <div className="mt-4 lg:mt-0">
                            <button
                                onClick={() => setShowAddForm(true)}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-gapura-green text-white rounded-lg hover:bg-gapura-green-dark transition-colors duration-200"
                            >
                                <UserPlus className="h-4 w-4" />
                                Tambah Karyawan
                            </button>
                        </div>
                    </div>
                </div>

                {/* Success/Error Messages */}
                {success && (
                    <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div className="flex items-center space-x-2">
                            <Check className="h-5 w-5 text-green-600" />
                            <span className="text-green-800">{success}</span>
                        </div>
                    </div>
                )}

                {error && (
                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div className="flex items-center space-x-2">
                            <AlertCircle className="h-5 w-5 text-red-600" />
                            <span className="text-red-800">{error}</span>
                        </div>
                    </div>
                )}

                {/* Add Form Modal */}
                {showAddForm && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
                            <div className="p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Tambah Karyawan Baru</h3>
                                    <button
                                        onClick={() => setShowAddForm(false)}
                                        className="text-gray-400 hover:text-gray-600"
                                    >
                                        <X className="h-6 w-6" />
                                    </button>
                                </div>

                                <form onSubmit={handleAdd} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Nama Lengkap *
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.nama_lengkap}
                                            onChange={(e) => setFormData(prev => ({ ...prev, nama_lengkap: e.target.value }))}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-gapura-green focus:border-gapura-green"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            NIP *
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.nip}
                                            onChange={(e) => setFormData(prev => ({ ...prev, nip: e.target.value }))}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-gapura-green focus:border-gapura-green"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            NIK *
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.nik}
                                            onChange={(e) => setFormData(prev => ({ ...prev, nik: e.target.value }))}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-gapura-green focus:border-gapura-green"
                                            required
                                        />
                                    </div>

                                    <div className="flex space-x-3 pt-4">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="flex-1 bg-gapura-green text-white px-4 py-2 rounded-lg hover:bg-gapura-green-dark disabled:opacity-50"
                                        >
                                            {processing ? 'Menyimpan...' : 'Simpan'}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setShowAddForm(false)}
                                            className="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400"
                                        >
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                )}

                {/* Search Bar */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <form onSubmit={handleSearch} className="flex space-x-4">
                        <div className="flex-1 relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                            <input
                                type="text"
                                placeholder="Cari nama, NIP, atau NIK..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-gapura-green focus:border-gapura-green"
                            />
                        </div>
                        <button
                            type="submit"
                            className="px-6 py-2 bg-gapura-green text-white rounded-lg hover:bg-gapura-green-dark transition-colors duration-200"
                        >
                            Cari
                        </button>
                    </form>
                </div>

                {/* Employee List */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Daftar Karyawan ({filteredEmployees.length})
                        </h3>
                    </div>

                    {filteredEmployees.length === 0 ? (
                        <div className="text-center py-12">
                            <Users className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500">Belum ada data karyawan</p>
                            <button
                                onClick={() => setShowAddForm(true)}
                                className="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-gapura-green text-white rounded-lg hover:bg-gapura-green-dark"
                            >
                                <UserPlus className="h-4 w-4" />
                                Tambah Karyawan Pertama
                            </button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nama Lengkap
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            NIP
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            NIK
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredEmployees.map((employee) => (
                                        <tr key={employee.id} className="hover:bg-gray-50">
                                            {/* Nama */}
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {editingId === employee.id ? (
                                                    <input
                                                        type="text"
                                                        value={editData.nama_lengkap}
                                                        onChange={(e) => setEditData(prev => ({ ...prev, nama_lengkap: e.target.value }))}
                                                        className="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:ring-gapura-green focus:border-gapura-green"
                                                    />
                                                ) : (
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {employee.nama_lengkap || '-'}
                                                    </div>
                                                )}
                                            </td>

                                            {/* NIP */}
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {editingId === employee.id ? (
                                                    <input
                                                        type="text"
                                                        value={editData.nip}
                                                        onChange={(e) => setEditData(prev => ({ ...prev, nip: e.target.value }))}
                                                        className="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:ring-gapura-green focus:border-gapura-green"
                                                    />
                                                ) : (
                                                    <div className="text-sm text-gray-900">
                                                        {employee.nip || '-'}
                                                    </div>
                                                )}
                                            </td>

                                            {/* NIK */}
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {editingId === employee.id ? (
                                                    <input
                                                        type="text"
                                                        value={editData.nik}
                                                        onChange={(e) => setEditData(prev => ({ ...prev, nik: e.target.value }))}
                                                        className="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:ring-gapura-green focus:border-gapura-green"
                                                    />
                                                ) : (
                                                    <div className="text-sm text-gray-900">
                                                        {employee.nik || '-'}
                                                    </div>
                                                )}
                                            </td>

                                            {/* Actions */}
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                {editingId === employee.id ? (
                                                    <div className="flex space-x-2">
                                                        <button
                                                            onClick={saveEdit}
                                                            disabled={processing}
                                                            className="text-green-600 hover:text-green-900 disabled:opacity-50"
                                                            title="Simpan"
                                                        >
                                                            <Save className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={cancelEdit}
                                                            className="text-gray-600 hover:text-gray-900"
                                                            title="Batal"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="flex space-x-2">
                                                        <button
                                                            onClick={() => startEdit(employee)}
                                                            className="text-blue-600 hover:text-blue-900"
                                                            title="Edit"
                                                        >
                                                            <Edit3 className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleDelete(employee)}
                                                            className="text-red-600 hover:text-red-900"
                                                            title="Hapus"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Pagination */}
                    {pagination && pagination.total > 0 && (
                        <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div className="flex items-center justify-between">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {pagination.prev_page_url && (
                                        <button
                                            onClick={() => router.get(pagination.prev_page_url)}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </button>
                                    )}
                                    {pagination.next_page_url && (
                                        <button
                                            onClick={() => router.get(pagination.next_page_url)}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Next
                                        </button>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{pagination.from}</span> to{' '}
                                            <span className="font-medium">{pagination.to}</span> of{' '}
                                            <span className="font-medium">{pagination.total}</span> results
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
