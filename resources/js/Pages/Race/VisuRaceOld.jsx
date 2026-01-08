import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

export default function VisuRace({ auth, race: raceData, error, errorMessage }) {
    // If race not found, display error message
    if (error || !raceData) {
        return (
            <AuthenticatedLayout
                user={auth?.user}
                header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Course non trouvée</h2>}
            >
                <Head title="Course non trouvée" />

                <div className="py-12">
                    <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-white shadow-sm rounded-2xl overflow-hidden">
                            <div className="p-12 text-center">
                                {/* Error Icon */}
                                <div className="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-6">
                                    <svg className="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                
                                <h3 className="text-2xl font-bold text-gray-900 mb-3">
                                    {error || 'Course non trouvée'}
                                </h3>
                                <p className="text-gray-600 mb-8">
                                    {errorMessage || "La course que vous recherchez n'existe pas ou a été supprimée."}
                                </p>
                                
                                <div className="flex justify-center gap-4">
                                    <Link
                                        href="/"
                                        className="inline-flex items-center px-6 py-3 bg-amber-900 hover:bg-amber-800 text-white font-semibold rounded-lg transition"
                                    >
                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Retour à l'accueil
                                    </Link>
                                    <button
                                        onClick={() => window.history.back()}
                                        className="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition"
                                    >
                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                        Page précédente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    // Données du backend via props
    const race = raceData || {
        id: 1,
        title: "La Boussole de la Forêt",
        description: "Une course d'orientation passionnante à travers les sentiers de la forêt de Fontainebleau. Venez découvrir ce parcours exceptionnel avec des balises réparties sur 8 km de terrain varié. Idéal pour les amateurs comme pour les confirmés, cette épreuve vous offrira un défi technique et physique dans un cadre naturel magnifique.",
        location: "Fontainebleau, France",
        latitude: 48.4009,
        longitude: 2.6985,
        raceDate: "2026-10-12T09:00:00Z",
        endDate: "2026-10-12T17:00:00Z",
        duration: "2:30",
        raceType: "medium",
        difficulty: "medium",
        status: "planned",
        imageUrl: "https://images.unsplash.com/photo-1541625602330-2277a4c46182?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80",
        maxParticipants: 150,
        minParticipants: 20,
        registeredCount: 87,
        maxPerTeam: 4,
        minTeams: 5,
        maxTeams: 30,
        organizer: {
            id: 1,
            name: "Club Orientation Paris",
            email: "contact@co-paris.fr"
        },
        categories: [
            { name: "Junior", minAge: 12, maxAge: 17, price: 15 },
            { name: "Senior", minAge: 18, maxAge: 39, price: 25 },
            { name: "Vétéran", minAge: 40, maxAge: 99, price: 20 }
        ],
        licenseDiscount: "5€ de réduction pour les licenciés FFCO",
        meals: "Repas inclus (sandwich + boisson)",
        mealsPrice: 8,
        createdAt: "2026-01-05T10:30:00Z",
        updatedAt: "2026-01-06T14:00:00Z"
    };

    // Formatage des dates
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    };

    const formatTime = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    };

    // Labels pour les types et statuts
    const raceTypeLabels = {
        sprint: 'Sprint',
        short: 'Courte Distance',
        medium: 'Moyenne Distance',
        long: 'Longue Distance',
        night: 'Nocturne'
    };

    const difficultyLabels = {
        easy: { label: 'Facile', color: 'bg-green-100 text-green-800' },
        medium: { label: 'Moyen', color: 'bg-yellow-100 text-yellow-800' },
        hard: { label: 'Difficile', color: 'bg-red-100 text-red-800' }
    };

    const statusLabels = {
        planned: { label: 'Planifiée', color: 'bg-blue-100 text-blue-800' },
        ongoing: { label: 'En cours', color: 'bg-green-100 text-green-800' },
        completed: { label: 'Terminée', color: 'bg-gray-100 text-gray-800' },
        cancelled: { label: 'Annulée', color: 'bg-red-100 text-red-800' }
    };

    const availableSpots = race.maxParticipants - race.registeredCount;
    const progressPercentage = (race.registeredCount / race.maxParticipants) * 100;

    // Component for no image placeholder
    const NoImagePlaceholder = () => (
        <div className="w-full h-full bg-gray-200 flex flex-col items-center justify-center">
            <svg className="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p className="mt-2 text-sm text-gray-500">Aucune image disponible</p>
        </div>
    );

    return (
        <AuthenticatedLayout
            user={auth?.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Détails de la Course</h2>}
        >
            <Head title={race.title} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white shadow-sm rounded-2xl overflow-hidden">
                        {/* Header with image on the side */}
                        <div className="flex flex-col lg:flex-row">
                            {/* Image Section - Left side */}
                            <div className="lg:w-1/3 h-64 lg:h-auto lg:min-h-[400px] relative">
                                {race.imageUrl ? (
                                    <img
                                        src={race.imageUrl.startsWith('/storage/') ? race.imageUrl : `/storage/${race.imageUrl}`}
                                        alt={race.title}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <NoImagePlaceholder />
                                )}
                            </div>

                            {/* Header Info - Right side */}
                            <div className="lg:w-2/3 p-6 lg:p-8 flex flex-col justify-center">
                                <div className="flex items-center gap-3 mb-4">
                                    <span className={`px-3 py-1 rounded-full text-sm font-medium ${statusLabels[race.status]?.color || 'bg-gray-100 text-gray-800'}`}>
                                        {statusLabels[race.status]?.label || 'Inconnu'}
                                    </span>
                                    <span className={`px-3 py-1 rounded-full text-sm font-medium ${difficultyLabels[race.difficulty]?.color || 'bg-gray-100 text-gray-800'}`}>
                                        {difficultyLabels[race.difficulty]?.label || 'Non défini'}
                                    </span>
                                    <span className="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                                        {raceTypeLabels[race.raceType] || 'Course'}
                                    </span>
                                </div>
                                <h1 className="text-3xl font-bold text-gray-900 mb-4">{race.title}</h1>
                                <p className="text-gray-600 leading-relaxed mb-6">{race.description}</p>
                                
                                {/* Quick info */}
                                <div className="flex flex-wrap gap-4 text-sm text-gray-600">
                                    <div className="flex items-center gap-2">
                                        <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{formatDate(race.raceDate)}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <svg className="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{race.duration}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <svg className="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>{race.location}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="p-8 border-t border-gray-100">
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Colonne principale */}
                                <div className="lg:col-span-2 space-y-8">
                                    {/* Informations de la course */}
                                    <section>
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Informations</h3>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                        <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-gray-500">Date de départ</p>
                                                        <p className="font-medium text-gray-900">{formatDate(race.raceDate)}</p>
                                                        <p className="text-sm text-gray-600">{formatTime(race.raceDate)}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                                        <svg className="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-gray-500">Date de fin</p>
                                                        <p className="font-medium text-gray-900">{formatDate(race.endDate)}</p>
                                                        <p className="text-sm text-gray-600">{formatTime(race.endDate)}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                        <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-gray-500">Durée estimée</p>
                                                        <p className="font-medium text-gray-900">{race.duration}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                                        <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-gray-500">Lieu</p>
                                                        <p className="font-medium text-gray-900">{race.location}</p>
                                                        <p className="text-xs text-gray-400">({race.latitude}, {race.longitude})</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    {/* Participants */}
                                    <section>
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Participants</h3>
                                        <div className="bg-gray-50 rounded-lg p-4">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="text-sm text-gray-600">Places occupées</span>
                                                <span className="text-sm font-medium text-gray-900">
                                                    {race.registeredCount} / {race.maxParticipants}
                                                </span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-3">
                                                <div
                                                    className="bg-indigo-600 h-3 rounded-full transition-all duration-300"
                                                    style={{ width: `${progressPercentage}%` }}
                                                />
                                            </div>
                                            <p className="mt-2 text-sm text-gray-500">
                                                {availableSpots > 0 
                                                    ? `${availableSpots} places restantes`
                                                    : 'Complet !'
                                                }
                                            </p>
                                            <div className="mt-4 grid grid-cols-3 gap-4 text-center">
                                                <div>
                                                    <p className="text-2xl font-bold text-gray-900">{race.minParticipants}</p>
                                                    <p className="text-xs text-gray-500">Min. participants</p>
                                                </div>
                                                <div>
                                                    <p className="text-2xl font-bold text-gray-900">{race.maxPerTeam}</p>
                                                    <p className="text-xs text-gray-500">Max. par équipe</p>
                                                </div>
                                                <div>
                                                    <p className="text-2xl font-bold text-gray-900">{race.minTeams} - {race.maxTeams}</p>
                                                    <p className="text-xs text-gray-500">Équipes</p>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    {/* Catégories */}
                                    <section>
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Catégories & Tarifs</h3>
                                        <div className="overflow-hidden rounded-lg border border-gray-200">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Âge</th>
                                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {race.categories.map((cat, index) => (
                                                        <tr key={index}>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{cat.name}</td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{cat.minAge} - {cat.maxAge} ans</td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">{cat.price}€</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                        {race.licenseDiscount && (
                                            <p className="mt-3 text-sm text-green-600 flex items-center gap-2">
                                                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                </svg>
                                                {race.licenseDiscount}
                                            </p>
                                        )}
                                    </section>
                                </div>

                                {/* Colonne latérale */}
                                <div className="space-y-6">
                                    {/* Bouton d'inscription */}
                                    <div className="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                                        <button className="w-full bg-amber-900 hover:bg-amber-800 text-white font-semibold py-3 px-6 rounded-lg transition mb-4">
                                            S'inscrire à la course
                                        </button>
                                        <p className="text-center text-sm text-gray-500">
                                            {availableSpots} places disponibles
                                        </p>
                                    </div>

                                    {/* Organisateur */}
                                    <div className="bg-gray-50 rounded-xl p-6">
                                        <h4 className="font-semibold text-gray-900 mb-4">Organisateur</h4>
                                        <div className="flex items-center gap-3">
                                            <div className="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center">
                                                <span className="text-white font-bold text-lg">
                                                    {race.organizer.name.charAt(0)}
                                                </span>
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900">{race.organizer.name}</p>
                                                <p className="text-sm text-gray-500">{race.organizer.email}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Options supplémentaires */}
                                    <div className="bg-gray-50 rounded-xl p-6">
                                        <h4 className="font-semibold text-gray-900 mb-4">Options</h4>
                                        <div className="space-y-3">
                                            {race.meals && (
                                                <div className="flex justify-between items-center">
                                                    <div className="flex items-center gap-2">
                                                        <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                        </svg>
                                                        <span className="text-sm text-gray-600">{race.meals}</span>
                                                    </div>
                                                    <span className="text-sm font-medium text-gray-900">{race.mealsPrice}€</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Informations techniques */}
                                    <div className="bg-gray-50 rounded-xl p-6">
                                        <h4 className="font-semibold text-gray-900 mb-4">Infos techniques</h4>
                                        <div className="space-y-2 text-sm">
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">ID Course</span>
                                                <span className="font-mono text-gray-900">#{race.id}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Créée le</span>
                                                <span className="text-gray-900">{formatDate(race.createdAt)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Mise à jour</span>
                                                <span className="text-gray-900">{formatDate(race.updatedAt)}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="flex gap-3">
                                        <button className="flex-1 border border-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg hover:bg-gray-50 transition">
                                            Partager
                                        </button>
                                        <button className="flex-1 border border-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg hover:bg-gray-50 transition">
                                            Contacter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
