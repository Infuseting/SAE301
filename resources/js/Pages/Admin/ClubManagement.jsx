import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FaEdit, FaEye, FaUsersCog } from 'react-icons/fa';

/**
 * ClubManagement page component for admin area.
 * Displays all clubs that the authenticated user manages (as leader or manager).
 * Accessible to users with the responsable-club role.
 */
export default function ClubManagement({ clubs }) {
    const { translations } = usePage().props;
    const messages = translations?.messages || {};

    return (
        <AuthenticatedLayout>
            <Head title={messages['admin.clubs.title'] || "Gestion des Clubs"} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Page Header */}
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <FaUsersCog className="text-emerald-600" />
                            {messages['admin.clubs.title'] || 'Gestion des Clubs'}
                        </h1>
                        <p className="mt-2 text-gray-600">
                            {clubs.length === 0 
                                ? (messages['admin.clubs.no_clubs_to_manage'] || "Aucun club à gérer pour le moment")
                                : (messages['admin.clubs.clubs_to_manage'] || ":count club(s) à gérer").replace(':count', clubs.length)
                            }
                        </p>
                    </div>

                    {/* Clubs List */}
                    {clubs.length === 0 ? (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <FaUsersCog className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                {messages.no_clubs || 'Aucun club trouvé'}
                            </h3>
                            <p className="text-gray-500 mb-6">
                                {messages['admin.clubs.no_clubs_description'] || "Vous n'avez aucun club à gérer pour le moment."}
                            </p>
                            <Link
                                href={route('clubs.create')}
                                className="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
                            >
                                {messages.create_club || 'Créer un club'}
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {clubs.map((club) => (
                                <div 
                                    key={club.club_id} 
                                    className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                                >
                                    <div className="p-6">
                                        <div className="flex items-start gap-6">
                                            {/* Club Image */}
                                            {club.club_image && (
                                                <div className="flex-shrink-0 w-32 h-32 rounded-lg overflow-hidden bg-gray-100">
                                                    <img
                                                        src={club.club_image.startsWith('/storage/') ? club.club_image : `/storage/${club.club_image}`}
                                                        alt={club.club_name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                </div>
                                            )}

                                            {/* Club Info */}
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                                    {club.club_name}
                                                </h3>
                                                {club.club_description && (
                                                    <p className="text-gray-600 mb-4 line-clamp-2">
                                                        {club.club_description}
                                                    </p>
                                                )}
                                                <div className="flex flex-wrap gap-4 text-sm text-gray-500">
                                                    {club.club_address && (
                                                        <span className="flex items-center gap-1">
                                                            📍 {club.club_address}
                                                        </span>
                                                    )}
                                                    {club.members_count !== undefined && (
                                                        <span className="flex items-center gap-1">
                                                            👥 {(messages['admin.clubs.members_count'] || ":count membre(s)").replace(':count', club.members_count)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Actions */}
                                            <div className="flex-shrink-0 flex items-center gap-2">
                                                <Link
                                                    href={route('clubs.show', club.club_id)}
                                                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition"
                                                    title={messages.view || "Voir"}
                                                >
                                                    <FaEye className="w-5 h-5" />
                                                </Link>
                                                <Link
                                                    href={route('clubs.edit', club.club_id)}
                                                    className="p-2 text-emerald-600 hover:bg-emerald-50 rounded-full transition"
                                                    title={messages.edit || "Modifier"}
                                                >
                                                    <FaEdit className="w-5 h-5" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
