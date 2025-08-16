// ============================================================================
// DASHBOARD LAYOUT - COMPLETE FIXED VERSION
// ============================================================================
// Fixes: userNavigation error, route helpers, navigation consistency
// Style: Firman HR Gapura consistent

import React, { useState, Fragment } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
    Home,
    Users,
    GraduationCap,
    BookOpen,
    Award,
    FileText,
    Upload,
    Settings,
    LogOut,
    Menu,
    X,
    Bell,
    Search,
    User,
    ChevronDown
} from 'lucide-react';

export default function DashboardLayout({ children, header, auth }) {
    const { url } = usePage();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);

    // Helper function to check current route
    const isCurrentRoute = (route) => {
        return url.startsWith(route);
    };

    // Navigation items - FIXED: All variables defined inside component
    const navigation = [
        {
            name: 'Dashboard',
            href: '/dashboard',
            icon: Home,
            current: isCurrentRoute('/dashboard') && url === '/dashboard',
            description: 'Overview sistem training'
        },
        {
            name: 'Data Karyawan',
            href: '/employees',
            icon: Users,
            current: isCurrentRoute('/employees'),
            description: 'Kelola data karyawan lengkap'
        },
        {
            name: 'Training Records',
            href: '/training',
            icon: GraduationCap,
            current: isCurrentRoute('/training') && !isCurrentRoute('/training/dashboard') && !isCurrentRoute('/training/types'),
            description: 'Data pelatihan dan sertifikasi'
        },
        {
            name: 'Training Types',
            href: '/training-types',
            icon: BookOpen,
            current: isCurrentRoute('/training-types'),
            description: 'Master jenis pelatihan'
        },
        {
            name: 'Certificates',
            href: '/certificates',
            icon: Award,
            current: isCurrentRoute('/certificates'),
            description: 'Manajemen sertifikat'
        },
        {
            name: 'Reports',
            href: '/training/reports',
            icon: FileText,
            current: isCurrentRoute('/training/reports'),
            description: 'Laporan dan analisis'
        },
        {
            name: 'Import/Export',
            href: '/import-export',
            icon: Upload,
            current: isCurrentRoute('/import-export'),
            description: 'Import/Export data'
        }
    ];

    // User navigation - FIXED: Defined inside component
    const userNavigation = [
        {
            name: 'Your Profile',
            href: '/profile',
            icon: User,
            action: () => router.visit('/profile')
        },
        {
            name: 'Settings',
            href: '/settings',
            icon: Settings,
            action: () => router.visit('/settings')
        },
        {
            name: 'Sign out',
            href: '/logout',
            icon: LogOut,
            action: () => router.post('/logout')
        },
    ];

    const handleNavigation = (href) => {
        router.visit(href);
    };

    const handleUserAction = (item) => {
        setUserDropdownOpen(false);
        if (item.action) {
            item.action();
        } else {
            router.visit(item.href);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Mobile sidebar overlay */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)} />
                </div>
            )}

            {/* Mobile Sidebar */}
            <div className={`fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl transform transition-transform duration-300 ease-in-out lg:hidden ${
                sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            }`}>
                <div className="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-[#439454] to-[#358945]">
                    <div className="flex items-center">
                        <div className="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                            <span className="text-[#439454] font-bold text-lg">G</span>
                        </div>
                        <span className="ml-2 text-white font-semibold">GAPURA Training</span>
                    </div>
                    <button
                        onClick={() => setSidebarOpen(false)}
                        className="text-white hover:text-gray-200"
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <nav className="mt-6 px-3">
                    {navigation.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`group flex items-center px-3 py-3 text-sm font-medium rounded-xl mb-1 transition-all duration-200 ${
                                    item.current
                                        ? 'bg-[#439454] text-white shadow-lg'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-[#439454]'
                                }`}
                                onClick={() => setSidebarOpen(false)}
                            >
                                <Icon className={`mr-3 h-5 w-5 transition-colors ${
                                    item.current ? 'text-white' : 'text-gray-400 group-hover:text-[#439454]'
                                }`} />
                                <div>
                                    <div>{item.name}</div>
                                    {item.description && (
                                        <div className={`text-xs ${
                                            item.current ? 'text-green-100' : 'text-gray-500'
                                        }`}>
                                            {item.description}
                                        </div>
                                    )}
                                </div>
                            </Link>
                        );
                    })}
                </nav>
            </div>

            {/* Desktop Sidebar */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-72 lg:flex-col">
                <div className="flex flex-col flex-grow bg-white border-r border-gray-200 shadow-sm">
                    {/* Logo */}
                    <div className="flex items-center h-16 px-6 bg-gradient-to-r from-[#439454] to-[#358945]">
                        <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center">
                            <span className="text-[#439454] font-bold text-xl">G</span>
                        </div>
                        <div className="ml-3">
                            <div className="text-white font-bold text-lg">GAPURA</div>
                            <div className="text-green-100 text-sm">Training System</div>
                        </div>
                    </div>

                    {/* Navigation */}
                    <nav className="mt-6 flex-1 px-4 space-y-2">
                        {navigation.map((item) => {
                            const Icon = item.icon;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 ${
                                        item.current
                                            ? 'bg-gradient-to-r from-[#439454] to-[#358945] text-white shadow-lg'
                                            : 'text-gray-700 hover:bg-gray-100 hover:text-[#439454]'
                                    }`}
                                >
                                    <Icon className={`mr-3 h-5 w-5 transition-colors ${
                                        item.current ? 'text-white' : 'text-gray-400 group-hover:text-[#439454]'
                                    }`} />
                                    <div className="flex-1">
                                        <div className="font-medium">{item.name}</div>
                                        {item.description && (
                                            <div className={`text-xs mt-0.5 ${
                                                item.current ? 'text-green-100' : 'text-gray-500 group-hover:text-[#439454]'
                                            }`}>
                                                {item.description}
                                            </div>
                                        )}
                                    </div>
                                </Link>
                            );
                        })}
                    </nav>

                    {/* User info at bottom */}
                    <div className="flex-shrink-0 p-4 border-t border-gray-200">
                        <div className="flex items-center">
                            <div className="w-10 h-10 bg-gradient-to-br from-[#439454] to-[#358945] rounded-full flex items-center justify-center text-white font-semibold">
                                {auth?.user?.name?.charAt(0).toUpperCase() || 'U'}
                            </div>
                            <div className="ml-3 flex-1">
                                <div className="text-sm font-medium text-gray-900">
                                    {auth?.user?.name || 'User'}
                                </div>
                                <div className="text-xs text-gray-500">
                                    {auth?.user?.email || 'user@gapura.com'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main content */}
            <div className="lg:pl-72">
                {/* Top bar */}
                <div className="sticky top-0 z-10 bg-white border-b border-gray-200 shadow-sm">
                    <div className="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        {/* Mobile menu button */}
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="lg:hidden p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
                        >
                            <Menu className="w-6 h-6" />
                        </button>

                        {/* Page title */}
                        <div className="flex-1 lg:flex-none">
                            {header && (
                                <div className="text-lg font-semibold text-gray-900">
                                    {header}
                                </div>
                            )}
                        </div>

                        {/* Right side items */}
                        <div className="flex items-center space-x-4">
                            {/* Search button */}
                            <button className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                                <Search className="w-5 h-5" />
                            </button>

                            {/* Notifications button */}
                            <button className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg relative">
                                <Bell className="w-5 h-5" />
                                <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>

                            {/* User menu */}
                            <div className="relative">
                                <button
                                    onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                                    className="flex items-center space-x-2 p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
                                >
                                    <div className="w-8 h-8 bg-gradient-to-br from-[#439454] to-[#358945] rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                        {auth?.user?.name?.charAt(0).toUpperCase() || 'U'}
                                    </div>
                                    <ChevronDown className="w-4 h-4" />
                                </button>

                                {/* User dropdown */}
                                {userDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                                        <div className="px-4 py-2 border-b border-gray-100">
                                            <div className="text-sm font-medium text-gray-900">
                                                {auth?.user?.name || 'User'}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {auth?.user?.email || 'user@gapura.com'}
                                            </div>
                                        </div>

                                        {userNavigation.map((item) => {
                                            const Icon = item.icon;
                                            return (
                                                <button
                                                    key={item.name}
                                                    onClick={() => handleUserAction(item)}
                                                    className="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                                                >
                                                    <Icon className="w-4 h-4 mr-3 text-gray-400" />
                                                    {item.name}
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Page content */}
                <main className="flex-1">
                    {children}
                </main>
            </div>

            {/* Click outside to close dropdown */}
            {userDropdownOpen && (
                <div
                    className="fixed inset-0 z-40"
                    onClick={() => setUserDropdownOpen(false)}
                />
            )}
        </div>
    );
}
