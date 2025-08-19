import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { TrashIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';

/**
 * DeleteButton Component
 * Komponen untuk menghapus karyawan dengan konfirmasi modal
 *
 * Props:
 * - employee: object karyawan yang akan dihapus
 * - className: custom CSS class (optional)
 * - showText: tampilkan text "Hapus" atau tidak (optional)
 * - size: ukuran icon 'sm' atau 'md' (optional)
 */
export default function DeleteButton({
    employee,
    className = "text-red-600 hover:text-red-900",
    showText = false,
    size = "sm"
}) {
    const [isDeleting, setIsDeleting] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const handleDelete = () => {
        setShowConfirm(true);
    };

    const confirmDelete = () => {
        setIsDeleting(true);
        setShowConfirm(false);

        // FIXED: Menggunakan router.delete dengan URL yang benar
        router.delete(`/employees/${employee.id}`, {
            preserveScroll: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onError: (errors) => {
                console.error('Delete error:', errors);
                setIsDeleting(false);
                alert('Terjadi kesalahan saat menghapus karyawan. Silakan coba lagi.');
            },
            onSuccess: () => {
                // Success akan ditangani oleh flash message di controller
                setIsDeleting(false);
            }
        });
    };

    const cancelDelete = () => {
        setShowConfirm(false);
    };

    const iconSize = size === 'sm' ? 'h-4 w-4' : 'h-5 w-5';

    return (
        <>
            {/* Delete Button */}
            <button
                onClick={handleDelete}
                disabled={isDeleting}
                className={`${className} ${isDeleting ? 'opacity-50 cursor-not-allowed' : ''} inline-flex items-center transition-colors duration-200`}
                title="Hapus Karyawan"
            >
                {isDeleting ? (
                    // Loading spinner
                    <div className={`${iconSize} animate-spin`}>
                        <svg className="w-full h-full" viewBox="0 0 24 24">
                            <circle
                                className="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                strokeWidth="4"
                                fill="none"
                            />
                            <path
                                className="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                    </div>
                ) : (
                    <TrashIcon className={iconSize} />
                )}
                {showText && (
                    <span className="ml-2">
                        {isDeleting ? 'Menghapus...' : 'Hapus'}
                    </span>
                )}
            </button>

            {/* Confirmation Modal */}
            {showConfirm && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        {/* Background overlay */}
                        <div
                            className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                            onClick={cancelDelete}
                        ></div>

                        {/* Centering element */}
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                        {/* Modal panel */}
                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <ExclamationTriangleIcon className="h-6 w-6 text-red-600" />
                                    </div>
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Konfirmasi Hapus Karyawan
                                        </h3>
                                        <div className="mt-2">
                                            <p className="text-sm text-gray-500">
                                                Apakah Anda yakin ingin menghapus karyawan <strong>{employee.nama_lengkap}</strong>?
                                            </p>
                                            <div className="mt-2 p-3 bg-gray-50 rounded-md">
                                                <div className="text-sm text-gray-600">
                                                    <div><strong>NIP:</strong> {employee.nip}</div>
                                                    <div><strong>Jabatan:</strong> {employee.jabatan}</div>
                                                    <div><strong>Unit:</strong> {employee.unit_organisasi}</div>
                                                </div>
                                            </div>
                                            <div className="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                                <p className="text-xs text-yellow-800">
                                                    <strong>Catatan:</strong> Data karyawan akan dinonaktifkan (soft delete) dan masih dapat dipulihkan jika diperlukan.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={confirmDelete}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200"
                                >
                                    <TrashIcon className="h-4 w-4 mr-2" />
                                    Ya, Hapus
                                </button>
                                <button
                                    type="button"
                                    onClick={cancelDelete}
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
