import { useState, useRef, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import { MdAdminPanelSettings } from "react-icons/md";
import { FaUsersCog, FaClipboardList, FaRoute } from "react-icons/fa";

/**
 * ManagementButton component - Dropdown navigation for admin/management pages
 * Provides access to admin dashboard, clubs management, raids management, and races management
 */
export default function ManagementButton() {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);
    const messages = usePage().props.translations?.messages || {};
    const user = usePage().props.auth?.user;

    /**
     * Check if user has access to the admin panel.
     * Users with any of these conditions can access /admin:
     * - Has 'admin' role
     * - Has 'access-admin' permission (gestionnaire-raid, responsable-club, responsable-course)
     */
    const hasAdminAccess =
        user?.roles?.some((role) => role.name === "admin" || role === "admin") ||
        user?.permissions?.some((perm) => {
            const permName = perm.name || perm;
            return permName === "access-admin";
        });

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target)
            ) {
                setIsOpen(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    // Don't render if user doesn't have admin access
    if (!hasAdminAccess) {
        return null;
    }

    return (
        <div className="relative text-center" ref={dropdownRef}>
            {/* Dropdown Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="inline-flex items-center px-3 py-2 border border-transparent text-md leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
            >
                <MdAdminPanelSettings className="h-5 w-5 mr-1" />
                {messages.management || "Gestion"}
                <svg
                    className={`ml-2 h-4 w-4 transition-transform ${
                        isOpen ? "rotate-180" : ""
                    }`}
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                    />
                </svg>
            </button>

            {/* Dropdown Menu */}
            {isOpen && (
                <div className="absolute left-1/2 transform -translate-x-1/2 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                    <div className="py-1" role="menu">
                        <Link
                            href={route("admin.dashboard")}
                            className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition"
                            role="menuitem"
                            onClick={() => setIsOpen(false)}
                        >
                            <MdAdminPanelSettings className="h-5 w-5 mr-3 text-gray-400" />
                            {messages.admin_dashboard || "Tableau de bord"}
                        </Link>

                        <Link
                            href={route("admin.clubs.index")}
                            className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition"
                            role="menuitem"
                            onClick={() => setIsOpen(false)}
                        >
                            <FaUsersCog className="h-5 w-5 mr-3 text-gray-400" />
                            {messages.manage_clubs || "Gérer les clubs"}
                        </Link>

                        <Link
                            href={route("admin.raids.index")}
                            className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition"
                            role="menuitem"
                            onClick={() => setIsOpen(false)}
                        >
                            <FaClipboardList className="h-5 w-5 mr-3 text-gray-400" />
                            {messages.manage_raids || "Gérer les raids"}
                        </Link>

                        <Link
                            href={route("admin.races.index")}
                            className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition"
                            role="menuitem"
                            onClick={() => setIsOpen(false)}
                        >
                            <FaRoute className="h-5 w-5 mr-3 text-gray-400" />
                            {messages.manage_races || "Gérer les courses"}
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
