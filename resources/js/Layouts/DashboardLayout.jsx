import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    Home,
    Users,
    GraduationCap,
    FileCheck,
    Settings,
    LogOut,
    Menu,
    X,
    Award,
    Download,
    Upload,
    FileText,
    BookOpen
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

    // Navigation items - Cleaned up version (removed redundant menus)
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
            current: isCurrentRoute('/employees'),
            description: 'Kelola data karyawan lengkap'
        },
        {
            name: 'Training Records',
            href: '/training',
            icon: GraduationCap,
            current: isCurrentRoute('/training') && !isCurrentRoute('/training/dashboard') && !isCurrentRoute('/training/analytics'),
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
                            className="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden"
                            onClick={() => setSidebarOpen(true)}
                        >
                            <Menu className="h-6 w-6" />
                        </button>
                        <div className="flex-1 px-4 flex justify-between items-center">
                            <div className="flex-1 flex">
                                <h1 className="text-xl font-semibold text-gray-900">{title}</h1>
                            </div>
                            <div className="ml-4 flex items-center md:ml-6">
                                <div className="flex items-center space-x-4">
                                    <div className="flex items-center space-x-2">
                                        <div className="w-8 h-8 bg-gapura-green rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                            {auth.user.name.charAt(0).toUpperCase()}
                                        </div>
                                        <span className="text-sm font-medium text-gray-700 hidden sm:block">
                                            {auth.user.name}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <main className="flex-1 relative overflow-y-auto focus:outline-none">
                        <div className="py-6">
                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}

// Komponen sidebar content
function SidebarContent({ navigation, userNavigation, auth }) {
    return (
        <div className="flex-1 flex flex-col min-h-0 bg-white border-r border-gray-200">
            {/* Logo */}
            <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                <div className="flex items-center flex-shrink-0 px-4 mb-8">
                    <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-gapura-green rounded-lg flex items-center justify-center">
                            <span className="text-white font-bold text-lg">G</span>
                        </div>
                        <div>
                            <h2 className="text-lg font-bold text-gray-900">GAPURA ANGKASA</h2>
                            <p className="text-sm text-gray-500">Training System</p>
                        </div>
                    </div>
                </div>

                {/* Navigation */}
                <nav className="flex-1 px-2 space-y-1">
                    {navigation.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`group flex items-center px-2 py-3 text-sm font-medium rounded-md transition-colors duration-200 ${
                                    item.current
                                        ? 'bg-gapura-green text-white'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-gapura-green'
                                }`}
                            >
                                <Icon
                                    className={`mr-3 flex-shrink-0 h-5 w-5 ${
                                        item.current ? 'text-white' : 'text-gray-400 group-hover:text-gapura-green'
                                    }`}
                                />
                                <div className="flex-1">
                                    <div className="font-medium">{item.name}</div>
                                    {item.description && (
                                        <div className={`text-xs mt-0.5 ${
                                            item.current ? 'text-white/80' : 'text-gray-500'
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

            {/* User info */}
            <div className="flex-shrink-0 border-t border-gray-200 p-4">
                <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 bg-gapura-green rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        {auth.user.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-900 truncate">
                            {auth.user.name}
                        </p>
                        <p className="text-xs text-gray-500 truncate">
                            {auth.user.email}
                        </p>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        className="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <LogOut className="h-5 w-5" />
                    </Link>
                </div>
            </div>
        </div>
    );
}
