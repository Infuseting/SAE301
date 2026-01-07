import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

    // Race data from backend
    const race = raceData || {};

    // Mock raid data (will be replaced with real data when raid model exists)
    const raid = race.raid || null;

    // Date formatting helpers
    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    };

    const formatTime = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    };

    // Labels
    const difficultyLabels = {
        facile: { label: 'Facile', color: 'bg-green-100 text-green-800' },
        moyen: { label: 'Moyen', color: 'bg-yellow-100 text-yellow-800' },
        difficile: { label: 'Difficile', color: 'bg-red-100 text-red-800' }
    };

    const typeLabels = {
        'compétitif': { label: 'Compétitif', color: 'bg-blue-100 text-blue-800' },
        'loisir': { label: 'Loisir', color: 'bg-purple-100 text-purple-800' }
    };

    const statusLabels = {
        planned: { label: 'Planifiée', color: 'bg-blue-100 text-blue-800' },
        ongoing: { label: 'En cours', color: 'bg-green-100 text-green-800' },
        completed: { label: 'Terminée', color: 'bg-gray-100 text-gray-800' },
        cancelled: { label: 'Annulée', color: 'bg-red-100 text-red-800' }
    };

    // Calculate statistics
    const registeredCount = race.registeredCount || 0;
    const maxParticipants = race.maxParticipants || 0;
    const availableSpots = maxParticipants - registeredCount;
    const progressPercentage = maxParticipants > 0 ? (registeredCount / maxParticipants) * 100 : 0;

    // No image placeholder component
    const NoImagePlaceholder = ({ size = 'large' }) => (
        <div className={`w-full h-full bg-gray-200 flex flex-col items-center justify-center ${size === 'small' ? 'py-8' : ''}`}>
            <svg className={`${size === 'large' ? 'w-16 h-16' : 'w-12 h-12'} text-gray-400`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p className={`mt-2 ${size === 'large' ? 'text-sm' : 'text-xs'} text-gray-500`}>Aucune image</p>
        </div>
    );

    // Info card component
    const InfoCard = ({ icon, iconColor, title, value, subtitle }) => (
        <div className="bg-gray-50 rounded-lg p-4">
            <div className="flex items-center gap-3">
                <div className={`w-10 h-10 ${iconColor} rounded-lg flex items-center justify-center`}>
                    {icon}
                </div>
                <div>
                    <p className="text-sm text-gray-500">{title}</p>
                    <p className="font-medium text-gray-900">{value}</p>
                    {subtitle && <p className="text-sm text-gray-600">{subtitle}</p>}
                </div>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout
            user={auth?.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Détails de la Course</h2>}
        >
            <Head title={race.title || 'Détails de la Course'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                    
                    {/* ============================================ */}
                    {/* SECTION 1: INFORMATIONS DE LA COURSE */}
                    {/* ============================================ */}
                    <div className="bg-white shadow-sm rounded-2xl overflow-hidden">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-amber-900 to-amber-700 px-6 py-4">
                            <h2 className="text-xl font-bold text-white flex items-center gap-2">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Informations de la Course
                            </h2>
                        </div>

                        {/* Race content */}
                        <div className="flex flex-col lg:flex-row">
                            {/* Image Section */}
                            <div className="lg:w-1/3 h-64 lg:h-auto lg:min-h-[350px] relative">
                                {race.imageUrl ? (
                                    <img
                                        src={race.imageUrl}
                                        alt={race.title}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <NoImagePlaceholder />
                                )}
                            </div>

                            {/* Info Section */}
                            <div className="lg:w-2/3 p-6 lg:p-8">
                                {/* Badges */}
                                <div className="flex flex-wrap items-center gap-2 mb-4">
                                    {race.status && (
                                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${statusLabels[race.status]?.color || 'bg-gray-100 text-gray-800'}`}>
                                            {statusLabels[race.status]?.label || race.status}
                                        </span>
                                    )}
                                    {race.difficulty && (
                                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${difficultyLabels[race.difficulty]?.color || 'bg-gray-100 text-gray-800'}`}>
                                            {difficultyLabels[race.difficulty]?.label || race.difficulty}
                                        </span>
                                    )}
                                    {race.raceType && (
                                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${typeLabels[race.raceType]?.color || 'bg-amber-100 text-amber-800'}`}>
                                            {typeLabels[race.raceType]?.label || race.raceType}
                                        </span>
                                    )}
                                </div>

                                {/* Title */}
                                <h1 className="text-3xl font-bold text-gray-900 mb-4">{race.title}</h1>

                                {/* Description */}
                                {race.description && (
                                    <p className="text-gray-600 leading-relaxed mb-6">{race.description}</p>
                                )}

                                {/* Quick stats grid */}
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                    <div className="text-center p-3 bg-indigo-50 rounded-lg">
                                        <p className="text-2xl font-bold text-indigo-600">{race.maxParticipants || '-'}</p>
                                        <p className="text-xs text-gray-500">Places max</p>
                                    </div>
                                    <div className="text-center p-3 bg-green-50 rounded-lg">
                                        <p className="text-2xl font-bold text-green-600">{race.maxTeams || '-'}</p>
                                        <p className="text-xs text-gray-500">Équipes max</p>
                                    </div>
                                    <div className="text-center p-3 bg-purple-50 rounded-lg">
                                        <p className="text-2xl font-bold text-purple-600">{race.maxPerTeam || '-'}</p>
                                        <p className="text-xs text-gray-500">Par équipe</p>
                                    </div>
                                    <div className="text-center p-3 bg-amber-50 rounded-lg">
                                        <p className="text-2xl font-bold text-amber-600">{race.duration || '-'}</p>
                                        <p className="text-xs text-gray-500">Durée</p>
                                    </div>
                                </div>

                                {/* Date/time info */}
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <InfoCard
                                        icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>}
                                        iconColor="bg-indigo-100"
                                        title="Date de début"
                                        value={formatDate(race.raceDate)}
                                        subtitle={formatTime(race.raceDate)}
                                    />
                                    <InfoCard
                                        icon={<svg className="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>}
                                        iconColor="bg-red-100"
                                        title="Date de fin"
                                        value={formatDate(race.endDate)}
                                        subtitle={formatTime(race.endDate)}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Additional race details */}
                        <div className="p-6 lg:p-8 border-t border-gray-100">
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Participants section */}
                                <div className="lg:col-span-2">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Participants & Équipes</h3>
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        {maxParticipants > 0 && (
                                            <>
                                                <div className="flex justify-between items-center mb-2">
                                                    <span className="text-sm text-gray-600">Places occupées</span>
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {registeredCount} / {maxParticipants}
                                                    </span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-3 mb-2">
                                                    <div
                                                        className="bg-indigo-600 h-3 rounded-full transition-all duration-300"
                                                        style={{ width: `${progressPercentage}%` }}
                                                    />
                                                </div>
                                                <p className="text-sm text-gray-500 mb-4">
                                                    {availableSpots > 0 
                                                        ? `${availableSpots} places restantes`
                                                        : 'Complet !'
                                                    }
                                                </p>
                                            </>
                                        )}
                                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                                            <div>
                                                <p className="text-xl font-bold text-gray-900">{race.minParticipants || '-'}</p>
                                                <p className="text-xs text-gray-500">Min. participants</p>
                                            </div>
                                            <div>
                                                <p className="text-xl font-bold text-gray-900">{race.maxParticipants || '-'}</p>
                                                <p className="text-xs text-gray-500">Max. participants</p>
                                            </div>
                                            <div>
                                                <p className="text-xl font-bold text-gray-900">{race.minTeams || '-'} - {race.maxTeams || '-'}</p>
                                                <p className="text-xs text-gray-500">Nb équipes</p>
                                            </div>
                                            <div>
                                                <p className="text-xl font-bold text-gray-900">{race.maxPerTeam || '-'}</p>
                                                <p className="text-xs text-gray-500">Max. par équipe</p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Categories */}
                                    {race.categories && race.categories.length > 0 && (
                                        <div className="mt-6">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Catégories & Tarifs</h3>
                                            <div className="overflow-hidden rounded-lg border border-gray-200">
                                                <table className="min-w-full divide-y divide-gray-200">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
                                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Âge</th>
                                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prix</th>
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
                                        </div>
                                    )}
                                </div>

                                {/* Sidebar */}
                                <div className="space-y-6">
                                    {/* Registration button */}
                                    <div className="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                                        <button className="w-full bg-amber-900 hover:bg-amber-800 text-white font-semibold py-3 px-6 rounded-lg transition mb-4">
                                            S'inscrire à la course
                                        </button>
                                        {maxParticipants > 0 && (
                                            <p className="text-center text-sm text-gray-500">
                                                {availableSpots} places disponibles
                                            </p>
                                        )}
                                    </div>

                                    {/* Organizer */}
                                    {race.organizer && (
                                        <div className="bg-gray-50 rounded-xl p-6">
                                            <h4 className="font-semibold text-gray-900 mb-4">Responsable</h4>
                                            <div className="flex items-center gap-3">
                                                <div className="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center">
                                                    <span className="text-white font-bold text-lg">
                                                        {race.organizer.name?.charAt(0) || '?'}
                                                    </span>
                                                </div>
                                                <div>
                                                    <p className="font-medium text-gray-900">{race.organizer.name}</p>
                                                    <p className="text-sm text-gray-500">{race.organizer.email}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Options */}
                                    {(race.licenseDiscount || race.mealsPrice) && (
                                        <div className="bg-gray-50 rounded-xl p-6">
                                            <h4 className="font-semibold text-gray-900 mb-4">Options</h4>
                                            <div className="space-y-3">
                                                {race.licenseDiscount && (
                                                    <div className="flex items-center gap-2 text-green-600">
                                                        <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                        </svg>
                                                        <span className="text-sm">{race.licenseDiscount}€ réduction licenciés</span>
                                                    </div>
                                                )}
                                                {race.mealsPrice && (
                                                    <div className="flex justify-between items-center">
                                                        <span className="text-sm text-gray-600">Repas</span>
                                                        <span className="text-sm font-medium text-gray-900">{race.mealsPrice}€</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {/* Technical info */}
                                    <div className="bg-gray-50 rounded-xl p-6">
                                        <h4 className="font-semibold text-gray-900 mb-4">Infos techniques</h4>
                                        <div className="space-y-2 text-sm">
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">ID Course</span>
                                                <span className="font-mono text-gray-900">#{race.id}</span>
                                            </div>
                                            {race.createdAt && (
                                                <div className="flex justify-between">
                                                    <span className="text-gray-500">Créée le</span>
                                                    <span className="text-gray-900">{formatDate(race.createdAt)}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* ============================================ */}
                    {/* SECTION 2: INFORMATIONS DU RAID */}
                    {/* ============================================ */}
                    <div className="bg-white shadow-sm rounded-2xl overflow-hidden">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-indigo-900 to-indigo-700 px-6 py-4">
                            <h2 className="text-xl font-bold text-white flex items-center gap-2">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Raid associé
                            </h2>
                        </div>

                        {/* Raid content */}
                        {raid ? (
                            // Raid exists - display info
                            <div className="flex flex-col lg:flex-row">
                                {/* Raid image */}
                                <div className="lg:w-1/3 h-48 lg:h-auto lg:min-h-[250px] relative">
                                    {raid.imageUrl ? (
                                        <img
                                            src={raid.imageUrl}
                                            alt={raid.name}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <NoImagePlaceholder size="small" />
                                    )}
                                </div>

                                {/* Raid info */}
                                <div className="lg:w-2/3 p-6 lg:p-8">
                                    <h3 className="text-2xl font-bold text-gray-900 mb-3">{raid.name}</h3>
                                    {raid.description && (
                                        <p className="text-gray-600 leading-relaxed mb-4">{raid.description}</p>
                                    )}
                                    
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {raid.location && (
                                            <div className="flex items-center gap-2 text-gray-600">
                                                <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span>{raid.location}</span>
                                            </div>
                                        )}
                                        {raid.date && (
                                            <div className="flex items-center gap-2 text-gray-600">
                                                <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>{formatDate(raid.date)}</span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="mt-6">
                                        <Link
                                            href={`/raid/${raid.id}`}
                                            className="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition"
                                        >
                                            Voir le raid
                                            <svg className="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            // No raid - display message
                            <div className="p-12 text-center">
                                <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                    Aucun raid associé
                                </h3>
                                <p className="text-gray-500 max-w-md mx-auto">
                                    Cette course n'est pas encore liée à un raid. 
                                    Elle pourra être ajoutée à un raid ultérieurement par un organisateur.
                                </p>
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
