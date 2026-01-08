import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, usePage } from "@inertiajs/react";
import { RiRunLine } from "react-icons/ri";
import { FaRegCompass } from "react-icons/fa6";

export default function Dashboard({
    stats,
    myresponsibleRaids,
    myresponsibleRaces,
}) {
    const { auth } = usePage().props;
    const user = auth?.user;

    /**
     * Helper function to check if user has a specific permission.
     * @param {string} permissionName - The permission name to check
     * @returns {boolean}
     */
    const hasPermission = (permissionName) => {
        return user?.permissions?.some((perm) => {
            const name = perm.name || perm;
            return name === permissionName;
        });
    };

    // Check if user has permission to approve clubs
    const canApproveClubs =
        hasPermission("accept-club") ||
        user?.roles?.some((role) => role.name === "admin" || role === "admin");

    // Check if user is admin
    const isAdmin = user?.roles?.some((role) => role.name === "admin");

    // Check if user can access specific admin pages
    const canAccessRaces = isAdmin || hasPermission("access-admin-races");
    const canAccessRaids = isAdmin || hasPermission("access-admin-raids");
    const canAccessClubs = isAdmin || hasPermission("access-admin-clubs");

    const raids = myresponsibleRaids || [];
    const races = myresponsibleRaces || [];

    return (
        <AuthenticatedLayout>
            <Head title="Admin Dashboard" />

            <div className="px-4 sm:px-6 lg:px-8 py-12 space-y-8">
                {/* Section Admin Pure - Seulement pour les admins */}
                {isAdmin && (
                    <div>
                        <div className="mb-6">
                            <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                <span className="inline-block w-1 h-8 bg-blue-600 rounded"></span>
                                Administration Système
                            </h1>
                            <p className="text-gray-600 mt-1">
                                Gestion globale de la plateforme
                            </p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            {/* Utilisateurs */}
                            <div className="bg-white border border-gray-200 p-6 shadow sm:rounded-lg">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-800">
                                            Utilisateurs
                                        </h2>
                                        <p className="text-3xl font-bold text-gray-900 mt-2">
                                            {stats.users}
                                        </p>
                                    </div>
                                    <svg
                                        className="w-12 h-12 text-blue-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                        />
                                    </svg>
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href={route("admin.users.index")}
                                        className="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700"
                                    >
                                        Gérer les utilisateurs
                                        <svg
                                            className="w-4 h-4 ml-1"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </Link>
                                </div>
                            </div>

                            {/* Logs */}
                            <div className="bg-white border border-gray-200 p-6 shadow sm:rounded-lg">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-800">
                                            Logs
                                        </h2>
                                        <p className="text-3xl font-bold text-gray-900 mt-2">
                                            {stats.logs}
                                        </p>
                                    </div>
                                    <svg
                                        className="w-12 h-12 text-blue-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href={route("admin.logs.index")}
                                        className="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700"
                                    >
                                        Voir les logs
                                        <svg
                                            className="w-4 h-4 ml-1"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </Link>
                                </div>
                            </div>

                            {/* Club Approval */}
                            {canApproveClubs && (
                                <div className="bg-white border border-emerald-200 p-6 shadow sm:rounded-lg">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-800">
                                            Validation des Clubs
                                        </h2>
                                        <p className="text-sm text-gray-500">
                                            Approuver ou rejeter les clubs
                                        </p>
                                        {stats.pendingClubs > 0 && (
                                            <div className="mt-2">
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    {stats.pendingClubs} en
                                                    attente
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                    <div className="mt-4">
                                        <Link
                                            href={route("admin.clubs.pending")}
                                            className="inline-block rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-sm transition"
                                        >
                                            Gérer les clubs
                                        </Link>
                                    </div>
                                </div>
                            )}

                            {/* Leaderboard */}
                            <div className="bg-white border border-gray-200 p-6 shadow sm:rounded-lg">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-800">
                                            Leaderboard
                                        </h2>
                                        <p className="text-sm text-gray-500 mt-1">
                                            Gérer les résultats
                                        </p>
                                    </div>
                                    <svg
                                        className="w-12 h-12 text-blue-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                        />
                                    </svg>
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href={route("admin.leaderboard.index")}
                                        className="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700"
                                    >
                                        Gérer le leaderboard
                                        <svg
                                            className="w-4 h-4 ml-1"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Section Responsable - Pour les admins et responsables */}
                <div>
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <span className="inline-block w-1 h-8 bg-purple-600 rounded"></span>
                            Mes Responsabilités
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Vos courses et raids
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
                        {/* Courses Management Card - Only show if user has permission */}
                        {canAccessRaces && (
                            <div className="bg-white border-l-4 border-purple-500 p-6 shadow sm:rounded-lg">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-800">
                                            Courses
                                        </h2>
                                        <p className="mt-3 text-gray-600">
                                            {isAdmin
                                                ? `Total : ${races.length} courses`
                                                : `Vous êtes responsable de ${races.length} courses`}
                                        </p>
                                    </div>
                                    <RiRunLine className="w-12 h-12 text-purple-400" />
                                </div>
                                <div className="mt-4">
                                    {races.length > 0 && (
                                        <Link
                                            href={route("admin.races.index")}
                                            className="inline-flex items-center text-sm font-medium text-purple-600 hover:text-purple-700"
                                        >
                                            Gérer les courses
                                            <svg
                                                className="w-4 h-4 ml-1"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M9 5l7 7-7 7"
                                                />
                                            </svg>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Raids Management Card - Only show if user has permission */}
                        {canAccessRaids && (
                            <div className="bg-white border-l-4 border-purple-500 p-6 shadow sm:rounded-lg">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-800">
                                            Raids
                                        </h2>
                                        <p className="mt-3 text-gray-600">
                                            {isAdmin
                                                ? `Total : ${raids.length} raids`
                                                : `Vous êtes responsable de ${raids.length} raids`}
                                        </p>
                                    </div>
                                    <FaRegCompass className="w-12 h-12 text-purple-400" />
                                </div>
                                <div className="mt-4">
                                    {raids.length > 0 && (
                                        <Link
                                            href={route("admin.raids.index")}
                                            className="inline-flex items-center text-sm font-medium text-purple-600 hover:text-purple-700"
                                        >
                                            Gérer les raids
                                            <svg
                                                className="w-4 h-4 ml-1"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M9 5l7 7-7 7"
                                                />
                                            </svg>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Clubs Management Card - Only show if user has permission */}
                        {canAccessClubs && (
                            <div className="bg-white border-l-4 border-emerald-500 p-6 shadow sm:rounded-lg">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-800">
                                            Clubs
                                        </h2>
                                        <p className="mt-3 text-gray-600">
                                            Gérez vos clubs
                                        </p>
                                    </div>
                                    <svg
                                        className="w-12 h-12 text-emerald-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href={route("admin.clubs.index")}
                                        className="inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-700"
                                    >
                                        Gérer les clubs
                                        <svg
                                            className="w-4 h-4 ml-1"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
