import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FaEdit, FaEye, FaRunning } from 'react-icons/fa';

export default function RaceManagement({ races }) {
    const { translations } = usePage().props;
    const messages = translations?.messages || {};

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des Courses" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Page Header */}
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <FaRunning className="text-blue-600" />
                            {messages.manage_races || 'Gestion des Courses'}
                        </h1>
                        <p className="mt-2 text-gray-600">
                            {races.length === 0 
                                ? "Aucune course à gérer pour le moment"
                                : `${races.length} course${races.length > 1 ? 's' : ''} à gérer`
                            }
                        </p>
                    </div>

                    {/* Races List */}
                    {races.length === 0 ? (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <FaRunning className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                {messages.no_races || 'Aucune course trouvée'}
                            </h3>
                            <p className="text-gray-500 mb-6">
                                Vous n'avez aucune course à gérer pour le moment.
                            </p>
                            <Link
                                href={route('races.create')}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                            >
                                Créer une course
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {races.map((race) => (
                                <div 
                                    key={race.race_id} 
                                    className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                                >
                                    <div className="p-6">
                                        <div className="flex items-start gap-6">
                                            {/* Race Image */}
                                            {race.race_image && (
                                                <div className="flex-shrink-0 w-32 h-32 rounded-lg overflow-hidden bg-gray-100">
                                                    <img
                                                        src={race.race_image.startsWith('/storage/') ? race.race_image : `/storage/${race.race_image}`}
                                                        alt={race.race_name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                </div>
                                            )}

                                            {/* Race Info */}
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                                    {race.race_name}
                                                </h3>
                                                <div className="flex flex-wrap gap-4 text-sm text-gray-500 mb-4">
                                                    {race.race_distance && (
                                                        <span className="flex items-center gap-1">
                                                            📏 {race.race_distance} km
                                                        </span>
                                                    )}
                                                    {race.race_type && (
                                                        <span className="flex items-center gap-1">
                                                            🏷️ {race.race_type}
                                                        </span>
                                                    )}
                                                    {race.raid_name && (
                                                        <span className="flex items-center gap-1">
                                                            🗺️ {race.raid_name}
                                                        </span>
                                                    )}
                                                </div>
                                                {race.race_description && (
                                                    <p className="text-gray-600 line-clamp-2">
                                                        {race.race_description}
                                                    </p>
                                                )}
                                            </div>

                                            {/* Actions */}
                                            <div className="flex-shrink-0 flex items-center gap-2">
                                                <Link
                                                    href={route('races.show', race.race_id)}
                                                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition"
                                                    title="Voir"
                                                >
                                                    <FaEye className="w-5 h-5" />
                                                </Link>
                                                <Link
                                                    href={route('races.edit', race.race_id)}
                                                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition"
                                                    title="Modifier"
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
