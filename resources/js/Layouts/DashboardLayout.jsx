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
    Upload
} from 'lucide-react';

export default function DashboardLayout({ children, title = "Dashboard" }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const navigation = [
        {
            name: 'Dashboard',
            href: '/dashboard',
            icon: Home,
            current: route().current('dashboard')
        },
        {
            name: 'Training Records',
            href: '/training',
            icon: GraduationCap,
            current: route().current('training.*')
        },
        {
            name: 'Employees',
            href: '/employees',
            icon: Users,
            current: route().current('employees.*')
        },
        {
            name: 'Certificates',
            href: '/certificates',
            icon: Award,
            current: route().current('certificates.*')
        },
        {
            name: 'Background Checks',
            href: '/background-checks',
            icon: FileCheck,
            current: route().current('background-checks.*')
        },
        {
            name: 'Schedules',
            href: '/schedules',
            icon: Calendar,
            current: route().current('schedules.*')
        },
        {
            name: 'Analytics',
            href: '/analytics',
            icon: BarChart3,
            current: route().current('analytics.*')
        },
        {
            name: 'Import/Export',
            href: '/import-export',
            icon: Upload,
            current: route().current('import-export.*')
        },
    ];

    const userNavigation = [
        { name: 'Settings', href: '/settings', icon: Settings },
        { name: 'Sign out', href: '/logout', icon: LogOut },
    ];

    return (
        <div className="h-screen flex overflow-hidden bg-gray-100">
            {/* Mobile sidebar overlay */}
            {sidebarOpen && (
                <div className="fixed inset-0 flex z-40 md:hidden">
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
                        <SidebarContent navigation={navigation} userNavigation={userNavigation} auth={auth} />
                    </div>
                </div>
            )}

            {/* Desktop sidebar */}
            <div className="hidden md:flex md:flex-shrink-0">
                <div className="flex flex-col w-64">
                    <SidebarContent navigation={navigation} userNavigation={userNavigation} auth={auth} />
                </div>
            </div>

            {/* Main content area */}
            <div className="flex flex-col w-0 flex-1 overflow-hidden">
                {/* Top header */}
                <div className="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
                    <button
                        type="button"
                        className="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gapura-green md:hidden"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <Menu className="h-6 w-6" />
                    </button>

                    <div className="flex-1 px-4 flex justify-between items-center">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">{title}</h1>
                        </div>

                        <div className="ml-4 flex items-center md:ml-6">
                            <div className="flex items-center space-x-4">
                                <span className="text-sm text-gray-700">
                                    Welcome, <span className="font-medium">{auth.user.name}</span>
                                </span>

                                <div className="h-8 w-8 rounded-full bg-gapura-green flex items-center justify-center">
                                    <span className="text-sm font-medium text-white">
                                        {auth.user.name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Page content */}
                <main className="flex-1 relative overflow-y-auto focus:outline-none">
                    <div className="py-6">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                            {children}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}

// Sidebar content component
function SidebarContent({ navigation, userNavigation, auth }) {
    return (
        <div className="flex flex-col h-0 flex-1 border-r border-gray-200 bg-white">
            {/* Logo and brand */}
            <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                <div className="flex items-center flex-shrink-0 px-4">
                    <div className="flex items-center">
                        <div className="flex-shrink-0">
                            <div className="h-10 w-10 rounded-lg bg-gapura-green flex items-center justify-center">
                                <GraduationCap className="h-6 w-6 text-white" />
                            </div>
                        </div>
                        <div className="ml-3">
                            <p className="text-sm font-medium text-gray-900">GAPURA ANGKASA</p>
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
                                        ? 'bg-gapura-green text-white'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-gapura-green'
                                } group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200`}
                            >
                                <Icon
                                    className={`${
                                        item.current ? 'text-white' : 'text-gray-400 group-hover:text-gapura-green'
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
                <div className="flex items-center">
                    <div className="flex-shrink-0">
                        <div className="h-8 w-8 rounded-full bg-gapura-green flex items-center justify-center">
                            <span className="text-sm font-medium text-white">
                                {auth.user.name.charAt(0).toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3">
                        <p className="text-sm font-medium text-gray-900">{auth.user.name}</p>
                        <p className="text-xs text-gray-500">{auth.user.email}</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
