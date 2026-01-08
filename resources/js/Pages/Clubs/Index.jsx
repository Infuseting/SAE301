import { Head, Link, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

/**
 * Clubs List Component
 * Displays a list of all clubs with dynamic search functionality
 * Design consistent with Raid/List page
 */
export default function Index({ clubs, filters }) {
    const messages = usePage().props.translations?.messages || {};
    const { auth } = usePage().props;
    const [hoveredClub, setHoveredClub] = useState(null);
    
    // Check if user can create clubs (must be adherent or admin)
    const userRoles = auth?.user?.roles || [];
    const canCreateClub = userRoles.some(role => 
        ['adherent', 'admin'].includes(role.name || role)
    );

    // Extract all clubs data for client-side filtering
    const allClubs = Array.isArray(clubs) ? clubs : clubs?.data || [];
    
    // Search state - initialized from URL params
    const [searchQuery, setSearchQuery] = useState(filters?.search || '');
    
    /**
     * Filter clubs based on search query (client-side dynamic search)
     */
    const filteredClubs = useMemo(() => {
        if (!searchQuery.trim()) {
            return allClubs;
        }
        
        const query = searchQuery.toLowerCase();
        return allClubs.filter(club => {
            return (
                club.club_name?.toLowerCase().includes(query) ||
                club.club_city?.toLowerCase().includes(query) ||
                club.club_address?.toLowerCase().includes(query)
            );
        });
    }, [searchQuery, allClubs]);

    /**
     * Clear search
     */
    const clearSearch = () => {
        setSearchQuery('');
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.clubs || 'Clubs'} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-7xl mx-auto px-6">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                    {messages.clubs || 'Tous les clubs'}
                                </h1>
                                <p className="text-gray-600">
                                    {messages.clubs_subtitle || 'Découvrez tous les clubs et rejoignez une communauté'}
                                </p>
                            </div>
                            {canCreateClub && (
                                <Link href={route('clubs.create')}>
                                    <button className="inline-flex items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        {messages.create_club || 'Créer un club'}
                                    </button>
                                </Link>
                            )}
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
                                    placeholder={messages.search_clubs || "Rechercher un club par nom, ville ou adresse..."}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>
                            {searchQuery && (
                                <button
                                    type="button"
                                    onClick={clearSearch}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Effacer
                                </button>
                            )}
                        </div>
                        
                        {/* Results Count */}
                        {searchQuery && (
                            <div className="mt-4 pt-4 border-t border-gray-100">
                                <p className="text-sm text-gray-600">
                                    {filteredClubs.length > 0 ? (
                                        <>
                                            <span className="font-semibold text-gray-900">{filteredClubs.length}</span> club{filteredClubs.length > 1 ? 's' : ''} trouvé{filteredClubs.length > 1 ? 's' : ''} pour "<span className="font-medium">{searchQuery}</span>"
                                        </>
                                    ) : (
                                        <>Aucun club trouvé pour "<span className="font-medium">{searchQuery}</span>"</>
                                    )}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Empty State */}
                    {filteredClubs.length === 0 ? (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-12 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 text-gray-300 mx-auto mb-4">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                {searchQuery ? 'Aucun résultat trouvé' : (messages.no_clubs_found || 'Aucun club disponible')}
                            </h3>
                            <p className="text-gray-600 mb-6 max-w-md mx-auto">
                                {searchQuery 
                                    ? 'Essayez de modifier votre recherche ou effacez les filtres pour voir tous les clubs.'
                                    : (messages.no_clubs_description || 'Il n\'y a actuellement aucun club disponible. Revenez bientôt !')}
                            </p>
                            {searchQuery ? (
                                <button
                                    onClick={clearSearch}
                                    className="inline-flex items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Voir tous les clubs
                                </button>
                            ) : canCreateClub && (
                                <Link href={route('clubs.create')}>
                                    <button className="inline-flex items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Créer le premier club
                                    </button>
                                </Link>
                            )}
                        </div>
                    ) : (
                        <>
                            {/* Clubs Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                {filteredClubs.map((club) => (
                                    <Link key={club.club_id} href={route('clubs.show', club.club_id)}>
                                        <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow h-full flex flex-col">
                                            {/* Club Image/Placeholder */}
                                            <div className="relative h-48 overflow-hidden bg-gradient-to-br from-emerald-100 to-emerald-50">
                                                {club.club_image ? (
                                                    <img
                                                        src={`/storage/${club.club_image}`}
                                                        alt={club.club_name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 text-emerald-300">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                                        </svg>
                                                    </div>
                                                )}
                                                {/* Member Count Badge */}
                                                {club.members && club.members.length > 0 && (
                                                    <div className="absolute top-3 right-3">
                                                        <span className="bg-emerald-600 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                                            {club.members.length} membre{club.members.length > 1 ? 's' : ''}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Club Content */}
                                            <div className="p-6 flex-1 flex flex-col">
                                                <h3 className="text-lg font-bold text-gray-900 mb-3">
                                                    {club.club_name}
                                                </h3>

                                                {/* Location */}
                                                <div className="flex items-start gap-2 mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                    </svg>
                                                    <p className="text-sm text-gray-600 line-clamp-1">
                                                        {club.club_city} {club.club_postal_code}
                                                    </p>
                                                </div>

                                                {/* Address */}
                                                {club.club_address && (
                                                    <div className="flex items-start gap-2 mb-3">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                                        </svg>
                                                        <p className="text-xs text-gray-500 line-clamp-2">
                                                            {club.club_address}
                                                        </p>
                                                    </div>
                                                )}

                                                {/* Creator */}
                                                {club.creator && (
                                                    <div className="flex items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 flex-shrink-0">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                        </svg>
                                                        <p className="text-xs text-gray-500 font-medium">
                                                            {club.creator.first_name} {club.creator.last_name}
                                                        </p>
                                                    </div>
                                                )}

                                                {/* CTA Button */}
                                                <div className="mt-auto">
                                                    {club.user_membership_status === 'pending' ? (
                                                        <button 
                                                            onMouseEnter={() => setHoveredClub(club.club_id)}
                                                            onMouseLeave={() => setHoveredClub(null)}
                                                            className="w-full inline-flex justify-center items-center px-4 py-2 bg-amber-500 hover:bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-all duration-150"
                                                        >
                                                            {hoveredClub === club.club_id ? 'ANNULER' : 'En attente d\'une réponse'}
                                                        </button>
                                                    ) : (
                                                        <button className="w-full inline-flex justify-center items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                            Voir les détails
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 ml-2">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                            </svg>
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>

                            {/* Pagination - Only show if using Laravel pagination */}
                            {clubs.last_page && clubs.last_page > 1 && (
                                <div className="mt-12 flex justify-center">
                                    <nav className="flex items-center gap-2">
                                        {clubs.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-4 py-2 rounded-lg font-medium transition ${link.active
                                                    ? 'bg-emerald-600 text-white shadow-lg'
                                                    : link.url
                                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-emerald-200'
                                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
