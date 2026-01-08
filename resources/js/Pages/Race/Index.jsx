import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Index({ auth, races }) {
    const Layout = auth?.user ? AuthenticatedLayout : GuestLayout;

    // Helper to format dates
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
        });
    };

    return (
        <Layout user={auth?.user}>
            <Head title="Calendrier des Courses" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold text-gray-900">Calendrier des Courses</h1>
                        <p className="mt-2 text-gray-600">Découvrez les prochaines épreuves d'orientation.</p>
                    </div>

                    {races.length === 0 ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                            Aucune course prévue pour le moment.
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {races.map((race) => (
                                <Link
                                    key={race.race_id || race.id}
                                    href={route('races.show', race.race_id || race.id)}
                                    className="group bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden border border-gray-100 block"
                                >
                                    <div className="aspect-video relative overflow-hidden bg-gray-100">
                                        {race.image_url || race.imageUrl ? (
                                            <img
                                                src={race.image_url || race.imageUrl}
                                                alt={race.race_name || race.title}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg className="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        )}
                                        <div className="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded-md text-sm font-bold text-gray-900 shadow-sm">
                                            {formatDate(race.race_date_start || race.raceDate)}
                                        </div>
                                    </div>
                                    <div className="p-4">
                                        <h3 className="font-bold text-lg text-gray-900 mb-1 group-hover:text-indigo-600 transition-colors">
                                            {race.race_name || race.title}
                                        </h3>
                                        <div className="flex items-center text-gray-500 text-sm mb-2">
                                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {race.location}
                                        </div>
                                        <div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-50">
                                            <span className="text-sm font-medium text-gray-900">
                                                {race.race_duration_minutes || parseInt(race.duration) * 60} min
                                            </span>
                                            <span className="text-indigo-600 text-sm font-semibold group-hover:underline">
                                                Voir les détails →
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </Layout>
    );
}
