
import ProfileCompletionModal from '@/Components/ProfileCompletionModal';
import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import UserMenu from '@/Components/UserMenu';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const messages = usePage().props.translations?.messages || {};

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    const hasPermission = (permission) => {
        if (!user) return false;
        return user.roles?.some(role => (role.name === 'admin' || role === 'admin')) ||
            user.permissions?.some(perm => (perm.name === permission || perm === permission));
    }

    const canViewUsers = hasPermission('view users');
    const canViewLogs = hasPermission('view logs');
    const hasAdminAccess = user?.roles?.some(role => (role.name === 'admin' || role === 'admin')) ||
        user?.permissions?.some(perm => ['view users', 'edit users', 'delete users', 'view logs'].includes(perm.name || perm));

    return (
        <div className="min-h-screen bg-gray-100 ">
            {user && <ProfileCompletionModal />}
            <nav className="border-b border-gray-100 bg-white  ">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800  hover:text-[#9333ea]  transition-colors" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {hasAdminAccess && (
                                    <NavLink
                                        href={route('admin.dashboard')}
                                        active={route().current('admin.dashboard')}
                                    >
                                        {messages.dashboard || 'Dashboard'}
                                    </NavLink>
                                )}

                                {canViewUsers && (
                                    <NavLink
                                        href={route('admin.users.index')}
                                        active={route().current('admin.users.index')}
                                    >
                                        {messages.users || 'Users'}
                                    </NavLink>
                                )}

                                {canViewLogs && (
                                    <NavLink
                                        href={route('admin.logs.index')}
                                        active={route().current('admin.logs.index')}
                                    >
                                        {messages.logs || 'Logs'}
                                    </NavLink>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center">
                            <div className="relative ms-3">
                                <LanguageSwitcher />
                            </div>

                            {user ? (
                                <div className="hidden sm:flex sm:items-center">
                                    <div className="relative ms-3">
                                        <UserMenu user={user} />
                                    </div>
                                </div>
                            ) : (
                                <div className="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                                    <Link
                                        href={route('login')}
                                        className="text-sm text-gray-700 hover:text-gray-900 transition"
                                    >
                                        {messages.login || 'Se connecter'}
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="text-sm bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition"
                                    >
                                        {messages.register || "S'inscrire"}
                                    </Link>
                                </div>
                            )}

                            <div className="-me-2 flex items-center sm:hidden">
                                <button
                                    onClick={() =>
                                        setShowingNavigationDropdown(
                                            (previousState) => !previousState,
                                        )
                                    }
                                    className="inline-flex items-center justify-center rounded-md p-2 text-gray-400  transition duration-150 ease-in-out hover:bg-gray-100  hover:text-gray-500  focus:bg-gray-100  focus:text-gray-500  focus:outline-none"
                                >
                                    <svg
                                        className="h-6 w-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            className={
                                                !showingNavigationDropdown
                                                    ? 'inline-flex'
                                                    : 'hidden'
                                            }
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                        <path
                                            className={
                                                showingNavigationDropdown
                                                    ? 'inline-flex'
                                                    : 'hidden'
                                            }
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        {hasAdminAccess && (
                            <ResponsiveNavLink
                                href={route('admin.dashboard')}
                                active={route().current('admin.dashboard')}
                            >
                                {messages.dashboard || 'Dashboard'}
                            </ResponsiveNavLink>
                        )}

                        {canViewUsers && (
                            <ResponsiveNavLink
                                href={route('admin.users.index')}
                                active={route().current('admin.users.index')}
                            >
                                {messages.users || 'Users'}
                            </ResponsiveNavLink>
                        )}

                        {canViewLogs && (
                            <ResponsiveNavLink
                                href={route('admin.logs.index')}
                                active={route().current('admin.logs.index')}
                            >
                                {messages.logs || 'Logs'}
                            </ResponsiveNavLink>
                        )}
                    </div>

                    {user ? (
                        <div className="border-t border-gray-200  pb-1 pt-4">
                            <div className="px-4">
                                <div className="text-base font-medium text-gray-800 ">
                                    {user.name}
                                </div>
                                <div className="text-sm font-medium text-gray-500 ">
                                    {user.email}
                                </div>
                            </div>

                            <div className="mt-3 space-y-1">
                                <ResponsiveNavLink href={route('profile.edit')}>
                                    {messages.profile || 'Profile'}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    method="post"
                                    href={route('logout')}
                                    as="button"
                                >
                                    {messages.logout || 'Log Out'}
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    ) : (
                        <div className="border-t border-gray-200 pb-1 pt-4">
                            <div className="px-4 space-y-2">
                                <ResponsiveNavLink href={route('login')}>
                                    {messages.login || 'Se connecter'}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink href={route('register')}>
                                    {messages.register || "S'inscrire"}
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    )}
                </div>
            </nav>

            {header && (
                <header className="bg-white  shadow ">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
