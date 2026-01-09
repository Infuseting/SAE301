import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FaEdit, FaEye, FaRoute } from 'react-icons/fa';

export default function RaidManagement({ raids }) {
    const { translations } = usePage().props;
    const messages = translations?.messages || {};

    return (
        <AuthenticatedLayout>
            <Head title={messages['admin.raids.title'] || "Gestion des Raids"} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Page Header */}
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <FaRoute className="text-purple-600" />
                            {messages['admin.raids.title'] || 'Gestion des Raids'}
                        </h1>
                        <p className="mt-2 text-gray-600">
                            {raids.length === 0 
                                ? (messages['admin.raids.no_raids_to_manage'] || "Aucun raid à gérer pour le moment")
                                : (messages['admin.raids.raids_to_manage'] || ":count raid(s) à gérer").replace(':count', raids.length)
                            }
                        </p>
                    </div>

                    {/* Raids List */}
                    {raids.length === 0 ? (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <FaRoute className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                {messages.no_raids || 'Aucun raid trouvé'}
                            </h3>
                            <p className="text-gray-500 mb-6">
                                {messages['admin.raids.no_raids_description'] || "Vous n'avez aucun raid à gérer pour le moment."}
                            </p>
                            <Link
                                href={route('raids.create')}
                                className="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                            >
                                {messages.create_raid || 'Créer un raid'}
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {raids.map((raid) => (
                                <div 
                                    key={raid.raid_id} 
                                    className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                                >
                                    <div className="p-6">
                                        <div className="flex items-start gap-6">
                                            {/* Raid Image */}
                                            {raid.raid_image && (
                                                <div className="flex-shrink-0 w-32 h-32 rounded-lg overflow-hidden bg-gray-100">
                                                    <img
                                                        src={raid.raid_image.startsWith('/storage/') ? raid.raid_image : `/storage/${raid.raid_image}`}
                                                        alt={raid.raid_name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                </div>
                                            )}

                                            {/* Raid Info */}
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                                    {raid.raid_name}
                                                </h3>
                                                {raid.raid_description && (
                                                    <p className="text-gray-600 mb-4 line-clamp-2">
                                                        {raid.raid_description}
                                                    </p>
                                                )}
                                                <div className="flex flex-wrap gap-4 text-sm text-gray-500">
                                                    {raid.raid_date && (
                                                        <span className="flex items-center gap-1">
                                                            📅 {new Date(raid.raid_date).toLocaleDateString('fr-FR')}
                                                        </span>
                                                    )}
                                                    {raid.races_count !== undefined && (
                                                        <span className="flex items-center gap-1">
                                                            🏃 {(messages['admin.raids.races_count'] || ":count course(s)").replace(':count', raid.races_count)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Actions */}
                                            <div className="flex-shrink-0 flex items-center gap-2">
                                                <Link
                                                    href={route('raids.show', raid.raid_id)}
                                                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition"
                                                    title={messages.view || "Voir"}
                                                >
                                                    <FaEye className="w-5 h-5" />
                                                </Link>
                                                <Link
                                                    href={route('raids.edit', raid.raid_id)}
                                                    className="p-2 text-purple-600 hover:bg-purple-50 rounded-full transition"
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
