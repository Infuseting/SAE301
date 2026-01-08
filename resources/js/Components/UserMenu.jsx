import { usePage } from '@inertiajs/react';
import Dropdown from '@/Components/Dropdown';
import UserAvatar from '@/Components/UserAvatar';

export default function UserMenu({ user }) {
    const messages = usePage().props.translations?.messages || {};

    // Logic to determine if user has admin access. 
    // Adjust this check based on your specific Spatie permission implementation.
    // Common patterns: user.roles array (of objects or strings), user.permissions array, or user.is_admin boolean.
    // Converting to generic check:
    const hasAdminAccess = user.roles?.some(role => (role.name === 'admin' || role === 'admin')) ||
        user.permissions?.some(perm => ['view users', 'edit users', 'delete users', 'view logs'].includes(perm.name || perm));

    return (
        <div className="relative">
            <Dropdown>
                <Dropdown.Trigger>
                    <button
                        type="button"
                        className="flex items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
                    >
                        <UserAvatar
                            user={user}
                            className="h-10 w-10 border-2 border-transparent hover:border-gray-300"
                        />
                    </button>
                </Dropdown.Trigger>

                <Dropdown.Content>
                    <Dropdown.Link href={route('profile.index')}>
                        {messages.profile || 'Profile'}
                    </Dropdown.Link>

                    <Dropdown.Link href={route('my-leaderboard.index')}>
                        {messages.my_rankings || 'Mes Classements'}
                    </Dropdown.Link>

                    <Dropdown.Link href={route('leaderboard.index')}>
                        {messages.general_leaderboard || 'Classement Général'}
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
                </Dropdown.Content>
            </Dropdown>
        </div>
    );
}
