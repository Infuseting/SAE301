import { usePage } from '@inertiajs/react';
import Dropdown from '@/Components/Dropdown';

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
                        className="flex items-center justify-center w-10 h-10 rounded-full bg-blue-500 border border-black text-white font-bold focus:outline-none transition duration-150 ease-in-out hover:bg-blue-600"
                    >
                        {user.name.charAt(0).toUpperCase()}
                    </button>
                </Dropdown.Trigger>

                <Dropdown.Content>
                    <Dropdown.Link href={route('profile.edit')}>
                        {messages.profile || 'Profile'}
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
