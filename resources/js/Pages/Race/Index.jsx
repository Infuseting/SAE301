import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';

/**
 * Races List Component
 * Displays a list of all races with dynamic search functionality
 * Design consistent with Clubs and Raids listing pages
 */
export default function Index({ auth, races }) {
    const messages = usePage().props.translations?.messages || {};


    
    // Extract all races data for client-side filtering
    const allRaces = Array.isArray(races) ? races : [];
    
    // Search state
    const [searchQuery, setSearchQuery] = useState('');
    
    /**
     * Get race status priority for sorting
     * @returns {number} Priority (lower = higher priority)
     */
    const getRacePriority = (race) => {
        if (!race.isOpen) {
            return 2; // Fermée
        }
        const now = new Date();
        const startDate = new Date(race.race_date_start);
        if (startDate < now) {
            return 3; // Terminée
        }
        return 1; // Ouverte
    };

    /**
     * Filter and sort races based on search query (client-side dynamic search)
     * Sorting priority: 1. Ouvertes, 2. Fermées, 3. Terminées
     */
    const filteredRaces = useMemo(() => {
        let filtered = allRaces;
        
        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            filtered = allRaces.filter(race => {
                return (
                    race.race_name?.toLowerCase().includes(query) ||
                    race.location?.toLowerCase().includes(query) ||
                    race.raid?.name?.toLowerCase().includes(query) ||
                    race.raid?.club?.name?.toLowerCase().includes(query)
                );
            });
        }
        
        // Sort by status: Ouvertes (1) -> Fermées (2) -> Terminées (3)
        return filtered.sort((a, b) => {
            const priorityA = getRacePriority(a);
            const priorityB = getRacePriority(b);
            
            if (priorityA !== priorityB) {
                return priorityA - priorityB;
            }
            
            // If same priority, sort by date (ascending)
            return new Date(a.race_date_start) - new Date(b.race_date_start);
        });
    }, [searchQuery, allRaces]);

    /**
     * Format date for display in French format
     * @param {string} dateString - ISO date string
     * @returns {string} Formatted date
     */
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    /**
     * Get status badge style based on race status
     */
    const getStatusBadge = (race) => {
        if (!race.isOpen) {
            return { text: messages['races.status_closed'] || 'Fermée', color: 'bg-red-600' };
        }
        const now = new Date();
        const startDate = new Date(race.race_date_start);
        if (startDate < now) {
            return { text: messages['races.status_finished'] || 'Terminée', color: 'bg-gray-600' };
        }
        return { text: messages['races.status_open'] || 'Ouverte', color: 'bg-green-600' };
    };

    /**
     * Clear search
     */
    const clearSearch = () => {
        setSearchQuery('');
    };

    return (
        <PublicLayout user={auth?.user}>
            <Head title={messages['races.page_title'] || "Calendrier des Courses"} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-7xl mx-auto px-6">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                    {messages['races.page_title'] || "Calendrier des Courses"}
                                </h1>
                                <p className="text-gray-600">
                                    {messages['races.page_subtitle'] || "Découvrez les prochaines épreuves d'orientation"}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Search Bar */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-8">
                        <div className="flex flex-col md:flex-row gap-4">
                            <div className="flex-1">
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder={messages['races.search_placeholder'] || "Rechercher une course par nom, lieu, raid ou club..."}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>
                            {searchQuery && (
                                <button
                                    type="button"
                                    onClick={clearSearch}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    {messages['races.clear'] || "Effacer"}
                                </button>
                            )}
                        </div>
                        
                        {/* Results Count */}
                        {searchQuery && (
                            <div className="mt-4 pt-4 border-t border-gray-100">
                                <p className="text-sm text-gray-600">
                                    {filteredRaces.length > 0 ? (
                                        <>
                                            <span className="font-semibold text-gray-900">{filteredRaces.length}</span> {messages['races.results_found'] || `course${filteredRaces.length > 1 ? 's' : ''} trouvée${filteredRaces.length > 1 ? 's' : ''}`} {messages['races.for'] || "pour"} "<span className="font-medium">{searchQuery}</span>"
                                        </>
                                    ) : (
                                        <>{messages['races.no_results'] || "Aucune course trouvée"} {messages['races.for'] || "pour"} "<span className="font-medium">{searchQuery}</span>"</>
                                    )}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Empty State */}
                    {filteredRaces.length === 0 ? (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-12 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 text-gray-300 mx-auto mb-4">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                            </svg>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                {searchQuery ? (messages['races.empty_search_title'] || 'Aucun résultat trouvé') : (messages['races.empty_title'] || 'Aucune course disponible')}
                            </h3>
                            <p className="text-gray-600 mb-6 max-w-md mx-auto">
                                {searchQuery 
                                    ? (messages['races.empty_search_message'] || 'Essayez de modifier votre recherche ou effacez les filtres pour voir toutes les courses.')
                                    : (messages['races.empty_message'] || 'Il n\'y a actuellement aucune course prévue. Revenez bientôt pour découvrir de nouvelles épreuves !')}
                            </p>
                            {searchQuery && (
                                <button
                                    onClick={clearSearch}
                                    className="inline-flex items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    {messages['races.view_all'] || "Voir toutes les courses"}
                                </button>
                            )}
                        </div>
                    ) : (
                        <>
                            {/* Races Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                {filteredRaces.map((race) => (
                                    <Link key={race.race_id} href={route('races.show', race.race_id)}>
                                        <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow h-full flex flex-col">
                                            {/* Race Image */}
                                            <div className="relative h-48 overflow-hidden bg-gradient-to-br from-indigo-100 to-indigo-50">
                                                {race.image_url ? (
                                                    <img
                                                        src={race.image_url}
                                                        alt={race.race_name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 text-indigo-300">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                                                        </svg>
                                                    </div>
                                                )}
                                                {/* Status Badge */}
                                                {(() => {
                                                    const status = getStatusBadge(race);
                                                    return (
                                                        <div className="absolute top-3 right-3">
                                                            <span className={`${status.color} text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg`}>
                                                                {status.text}
                                                            </span>
                                                        </div>
                                                    );
                                                })()}
                                            </div>

                                            {/* Race Content */}
                                            <div className="p-6 flex-1 flex flex-col">
                                                <h3 className="text-lg font-bold text-gray-900 mb-3">
                                                    {race.race_name}
                                                </h3>

                                                {/* Location */}
                                                <div className="flex items-start gap-2 mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                    </svg>
                                                    <p className="text-sm text-gray-600 line-clamp-1">
                                                        {race.location}
                                                    </p>
                                                </div>

                                                {/* Date */}
                                                <div className="flex items-start gap-2 mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                    </svg>
                                                    <p className="text-sm text-gray-600">
                                                        {formatDate(race.race_date_start)}
                                                    </p>
                                                </div>

                                                {/* Duration */}
                                                <div className="flex items-start gap-2 mb-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                    <p className="text-sm text-gray-600">
                                                        {race.race_duration_minutes} min
                                                    </p>
                                                </div>

                                                {/* Raid & Club */}
                                                {race.raid && (
                                                    <div className="flex items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 flex-shrink-0">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                                        </svg>
                                                        <p className="text-xs text-gray-500 font-medium line-clamp-1">
                                                            {race.raid.name} {race.raid.club && `• ${race.raid.club.name}`}
                                                        </p>
                                                    </div>
                                                )}

                                                {/* CTA Button */}
                                                <div className="mt-auto">
                                                    <button className="w-full inline-flex justify-center items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                        {messages['races.view_details'] || "Voir les détails"}
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 ml-2">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
