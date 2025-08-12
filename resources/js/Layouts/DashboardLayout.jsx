import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    Home,
    Users,
    GraduationCap,
    FileCheck,
    BarChart3,
    Settings,
    LogOut,
    Menu,
    X,
    Award,
    Calendar,
    Download,
    Upload,
    BookOpen,
    Shield,
    FileText,
    TrendingUp
} from 'lucide-react';

export default function DashboardLayout({ children, title = "Dashboard" }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    // Helper function untuk check current route
    const isCurrentRoute = (path) => {
        if (typeof window !== 'undefined') {
            return window.location.pathname === path || window.location.pathname.startsWith(path + '/');
        }
        return false;
    };

    // Navigation items - Fixed version
    const navigation = [
        {
            name: 'Dashboard',
            href: '/dashboard',
            icon: Home,
            current: isCurrentRoute('/dashboard')
        },
        {
            name: 'Employees',
            href: '/employees',
            icon: Users,
            current: isCurrentRoute('/employees')
        },
        {
            name: 'Training Records',
            href: '/training',
            icon: GraduationCap,
            current: isCurrentRoute('/training') && !isCurrentRoute('/training/dashboard') && !isCurrentRoute('/training/analytics')
        },
        {
            name: 'Data Karyawan Training',
            href: '/training/employees',
            icon: Users,
            current: isCurrentRoute('/training/employees')
        },
        {
            name: 'Training Dashboard',
            href: '/training/dashboard',
            icon: BarChart3,
            current: isCurrentRoute('/training/dashboard')
        },
        {
            name: 'Training Types',
            href: '/training-types',
            icon: BookOpen,
            current: isCurrentRoute('/training-types')
        },
        {
            name: 'Certificates',
            href: '/certificates',
            icon: Award,
            current: isCurrentRoute('/certificates')
        },
        {
            name: 'Background Checks',
            href: '/background-checks',
            icon: Shield,
            current: isCurrentRoute('/background-checks')
        },
        {
            name: 'Schedules',
            href: '/schedules',
            icon: Calendar,
            current: isCurrentRoute('/schedules')
        },
        {
            name: 'Analytics',
            href: '/training/analytics',
            icon: TrendingUp,
            current: isCurrentRoute('/training/analytics')
        },
        {
            name: 'Reports',
            href: '/training/reports',
            icon: FileText,
            current: isCurrentRoute('/training/reports')
        },
        {
            name: 'Import/Export',
            href: '/import-export',
            icon: Upload,
            current: isCurrentRoute('/import-export')
        }
    ];

    const userNavigation = [
        { name: 'Your profile', href: '/profile' },
        { name: 'Settings', href: '/settings' },
        { name: 'Sign out', href: '/logout', method: 'post' },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="flex h-screen">
                {/* Mobile sidebar */}
                <div className={`relative z-40 md:hidden ${sidebarOpen ? '' : 'hidden'}`}>
                    <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)} />
                    <div className="relative flex-1 flex flex-col max-w-xs w-full bg-white">
                        <div className="absolute top-0 right-0 -mr-12 pt-2">
                            <button
                                type="button"
                                className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                                onClick={() => setSidebarOpen(false)}
                            >
                                <X className="h-6 w-6 text-white" />
                            </button>
                        </div>
                        <SidebarContent
                            navigation={navigation}
                            userNavigation={userNavigation}
                            auth={auth}
                        />
                    </div>
                </div>

                {/* Desktop sidebar */}
                <div className="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
                    <SidebarContent
                        navigation={navigation}
                        userNavigation={userNavigation}
                        auth={auth}
                    />
                </div>

                {/* Main content */}
                <div className="flex flex-col w-0 flex-1 md:ml-64">
                    <div className="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white shadow border-b border-gray-200">
                        <button
                            type="button"
                            className="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#439454] md:hidden"
                            onClick={() => setSidebarOpen(true)}
                        >
                            <Menu className="h-6 w-6" />
                        </button>
                        <div className="flex-1 px-4 flex justify-between items-center">
                            <div>
                                <h1 className="text-xl font-semibold text-gray-900">{title}</h1>
                            </div>
                            <div className="ml-4 flex items-center md:ml-6">
                                {/* Profile dropdown */}
                                <div className="ml-3 relative">
                                    <div className="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#439454]">
                                        <div className="h-8 w-8 rounded-full bg-[#439454] flex items-center justify-center">
                                            <span className="text-sm font-medium text-white">
                                                {auth.user.name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                        <span className="ml-3 text-gray-700 text-sm font-medium">{auth.user.name}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <main className="flex-1 relative overflow-y-auto focus:outline-none">
                        <div className="py-6">
                            <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                                {children}
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}

// Sidebar content component - Fixed version
function SidebarContent({ navigation, userNavigation, auth }) {
    return (
        <div className="flex flex-col h-0 flex-1 border-r border-gray-200 bg-white">
            {/* Logo and brand */}
            <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                <div className="flex items-center flex-shrink-0 px-4">
                    <div className="flex items-center">
                        <div className="flex-shrink-0">
                            <div className="h-10 w-10 rounded-lg bg-[#439454] flex items-center justify-center">
                                <GraduationCap className="h-6 w-6 text-white" />
                            </div>
                        </div>
                        <div className="ml-3">
                            <p className="text-sm font-bold text-gray-900">GAPURA ANGKASA</p>
                            <p className="text-xs text-gray-500">Training System</p>
                        </div>
                    </div>
                </div>

                {/* Navigation */}
                <nav className="mt-8 flex-1 px-2 space-y-1">
                    {navigation.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`${
                                    item.current
                                        ? 'bg-[#439454] text-white'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-[#439454]'
                                } group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200`}
                            >
                                <Icon
                                    className={`${
                                        item.current ? 'text-white' : 'text-gray-400 group-hover:text-[#439454]'
                                    } mr-3 flex-shrink-0 h-5 w-5`}
                                />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>
            </div>

            {/* User info and logout */}
            <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
                <div className="flex items-center w-full">
                    <div className="flex-shrink-0">
                        <div className="h-8 w-8 rounded-full bg-[#439454] flex items-center justify-center">
                            <span className="text-sm font-medium text-white">
                                {auth.user.name.charAt(0).toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3 flex-1">
                        <p className="text-sm font-medium text-gray-900">{auth.user.name}</p>
                        <p className="text-xs text-gray-500">{auth.user.email}</p>
                    </div>
                    <div className="ml-3">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 transition-colors duration-200"
                        >
                            <LogOut className="h-5 w-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
