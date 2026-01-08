import { Link, usePage } from '@inertiajs/react';
import Dropdown from '@/Components/Dropdown';
import UserAvatar from '@/Components/UserAvatar';

/**
 * Reusable navigation menu component for both authenticated and guest users
 * @param {Object} user - The authenticated user object (null if guest)
 * @param {React.ReactNode} trigger - Custom trigger element (button/avatar)
 */
export default function NavigationDropdownMenu({ user, trigger }) {
    const messages = usePage().props.translations?.messages || {};

    const hasAdminAccess = user?.roles?.some(role => (role.name === 'admin' || role === 'admin')) ||
        user?.permissions?.some(perm => ['view users', 'edit users', 'delete users', 'view logs'].includes(perm.name || perm));

    return (
        <div className="relative">
            <Dropdown>
                <Dropdown.Trigger>
                    {trigger}
                </Dropdown.Trigger>

                <Dropdown.Content>
                    {user ? (
                        <>
                            <Dropdown.Link href={route('profile.index')}>
                                {messages.profile || 'Profile'}
                            </Dropdown.Link>

                            <Dropdown.Link href={route('raids.index')}>
                                {messages.raids || 'Raids'}
                            </Dropdown.Link>

                            {hasAdminAccess && (
                                <Dropdown.Link href={route('admin.dashboard')}>
                                    Admin
                                </Dropdown.Link>
                            )}

                            <Dropdown.Link href={route('logout')} method="post" as="button">
                                {messages.logout || 'Log Out'}
                            </Dropdown.Link>
                        </>
                    ) : (
                        <>
                            <Dropdown.Link href={route('raids.index')}>
                                {messages.raids || 'Raids'}
                            </Dropdown.Link>

                            <Dropdown.Link href={route('login')}>
                                {messages.login}
                            </Dropdown.Link>

                            <Link
                                href={route('register')}
                                className="block w-full px-4 py-2 text-center text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition duration-150 ease-in-out"
                            >
                                {messages.register}
                            </Link>
                        </>
                    )}
                </Dropdown.Content>
            </Dropdown>
        </div>
    );
}
