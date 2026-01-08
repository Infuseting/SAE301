import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

/**
 * Display team details including members and information.
 */
export default function Show({ team, auth }) {
    const isCreator = auth?.user?.id === team?.creator_id;

    return (
        <AuthenticatedLayout>
            <Head title={team.name} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Team Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row gap-6">
                                {/* Team Image */}
                                <div className="flex-shrink-0">
                                    <img
                                        src={team.image || 'https://via.placeholder.com/200'}
                                        alt={team.name}
                                        className="h-48 w-48 rounded-lg object-cover"
                                    />
                                </div>

                                {/* Team Info */}
                                <div className="flex-1">
                                    <div className="flex justify-between items-start mb-4">
                                        <h1 className="text-4xl font-bold text-gray-900">
                                            {team.name}
                                        </h1>
                                        {isCreator && (
                                            <button className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                Inviter
                                            </button>
                                        )}
                                    </div>
                                    <div className="text-gray-600">
                                        <p className="mb-2">
                                            <span className="font-semibold">Membres:</span> {team.members?.length || 0}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Team Members */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h2 className="text-2xl font-bold text-gray-900 mb-6">
                                Membres de l'équipe
                            </h2>

                            {team.members && team.members.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {team.members.map((member) => (
                                        <div
                                            key={member.id}
                                            className="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
                                        >
                                            <img
                                                src={member.avatar || 'https://via.placeholder.com/48'}
                                                alt={member.name}
                                                className="h-12 w-12 rounded-full object-cover"
                                            />
                                            <div>
                                                <p className="font-semibold text-gray-900">
                                                    {member.name}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <p className="text-gray-500">
                                        Aucun membre dans cette équipe
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
