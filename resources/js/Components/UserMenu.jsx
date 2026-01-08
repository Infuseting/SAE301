import { usePage } from "@inertiajs/react";
import Dropdown from "@/Components/Dropdown";
import UserAvatar from "@/Components/UserAvatar";

export default function UserMenu({ user }) {
    const messages = usePage().props.translations?.messages || {};

    /**
     * Check if user has access to the admin panel.
     * Users with any of these conditions can access /admin:
     * - Has 'admin' role
     * - Has 'access-admin' permission (gestionnaire-raid, responsable-club, responsable-course)
     */
    const hasAdminAccess =
        user.roles?.some((role) => role.name === "admin" || role === "admin") ||
        user.permissions?.some((perm) => {
            const permName = perm.name || perm;
            return permName === "access-admin";
        });

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
                    <Dropdown.Link href={route("profile.index")}>
                        {messages.profile || "Profile"}
                    </Dropdown.Link>

                    {hasAdminAccess && (
                        <Dropdown.Link href={route("admin.dashboard")}>
                            Admin
                        </Dropdown.Link>
                    )}

                    <Dropdown.Link
                        href={route("logout")}
                        method="post"
                        as="button"
                    >
                        {messages.logout || "Log Out"}
                    </Dropdown.Link>
                </Dropdown.Content>
            </Dropdown>
        </div>
    );
}
