import { router, usePage } from '@inertiajs/react';
import { useState, useEffect, useMemo } from 'react';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import { createPortal } from 'react-dom';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

/**
 * Raid List Component
 * Displays a paginated list of all raids with dynamic search functionality
 * Design consistent with profile, clubs/create, and races/create pages
 */
export default function List({ raids, filters, ageCategories = [] }) {
    const messages = usePage().props.translations?.messages || {};
    const { auth } = usePage().props;
    const isClubLeader = auth?.user?.is_club_leader || false;
    
    // Extract all raids data for client-side filtering
    const allRaids = Array.isArray(raids) ? raids : raids?.data || [];
    
    // Search state - initialized from URL params
    const [searchQuery, setSearchQuery] = useState(filters?.q || '');
    const [startDate, setStartDate] = useState(filters?.date ? new Date(filters.date) : null);
    const [category, setCategory] = useState(filters?.category || 'all');
    const [ageCategory, setAgeCategory] = useState(filters?.age_category || '');
    const [location, setLocation] = useState(filters?.location || '');
    const [locationType, setLocationType] = useState(filters?.location_type || 'city');
    
    /**
     * Filter raids based on search query (client-side dynamic search)
     */
    const filteredRaids = useMemo(() => {
        if (!searchQuery.trim()) {
            return allRaids;
        }
        
        const query = searchQuery.toLowerCase();
        return allRaids.filter(raid => {
            return (
                raid.raid_name?.toLowerCase().includes(query) ||
                raid.raid_city?.toLowerCase().includes(query) ||
                raid.raid_description?.toLowerCase().includes(query) ||
                raid.club?.club_name?.toLowerCase().includes(query)
            );
        });
    }, [searchQuery, allRaids]);

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
     * Clear search
     */
    const clearSearch = () => {
        setSearchQuery('');
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.raids || 'Raids'} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-7xl mx-auto px-6">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 mb-2">{messages.raids || 'Tous les raids'}</h1>
                                <p className="text-gray-600">
                                    Découvrez tous les raids disponibles et inscrivez-vous à l'aventure
                                </p>
                            </div>
                            {isClubLeader && (
                                <Link href={route('raids.create')}>
                                    <button className="inline-flex items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        {messages.create_raid || 'Créer un raid'}
                                    </button>
                                </Link>
                            )}
                        </div>
                    </div>

                    {/* Search Bar */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-8">
                        <div className="flex flex-col md:flex-row gap-3">
                            {/* Where */}
                            <div className="flex-[2] px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                                <label className="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                                    Localisation
                                </label>
                                <div className="flex items-center gap-2">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={2}
                                        stroke="currentColor"
                                        className="w-4 h-4 text-gray-300 flex-shrink-0"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                        />
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"
                                        />
                                    </svg>
                                    <div className="w-full flex gap-2.5 items-end">
                                        <div className="flex-1">
                                            <select 
                                                value={locationType}
                                                onChange={(e) => setLocationType(e.target.value)}
                                                className="w-full bg-transparent border-none p-0 text-gray-900 focus:ring-0 font-medium cursor-pointer text-sm"
                                            >
                                                <option value="city">Ville</option>
                                                <option value="department">Département</option>
                                                <option value="region">Région</option>
                                            </select>
                                        </div>
                                        <div className="flex-1">
                                            <input
                                                type="text"
                                                placeholder="Recherchez..."
                                                value={location}
                                                onChange={(e) => setLocation(e.target.value)}
                                                className="w-full bg-transparent border-none p-0 text-gray-900 placeholder-gray-400 focus:ring-0 font-semibold text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* When */}
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                                <label className="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                                    Date
                                </label>
                                <div className="flex items-center gap-2">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                        className="w-4 h-4 text-gray-400 flex-shrink-0"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0h18M5.25 12h13.5h-13.5Zm0 3.75h13.5h-13.5Z"
                                        />
                                    </svg>
                                    <div className="w-full">
                                        <DatePicker
                                            selected={startDate}
                                            onChange={(date) => setStartDate(date)}
                                            placeholderText="Choisir une date"
                                            className="w-full bg-transparent border-none p-0 text-gray-900 placeholder-gray-400 focus:ring-0 font-medium text-sm"
                                            dateFormat="dd/MM/yyyy"
                                            popperContainer={({ children }) =>
                                                createPortal(
                                                    children,
                                                    document.body
                                                )
                                            }
                                            popperClassName="!z-[100]"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Type */}
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                                <label className="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                                    Type
                                </label>
                                <select 
                                    value={category}
                                    onChange={(e) => setCategory(e.target.value)}
                                    className="w-full bg-transparent border-none p-0 text-gray-900 focus:ring-0 font-medium cursor-pointer text-sm">
                                    <option value="all">Tous</option>
                                    <option value="loisir">Loisir</option>
                                    <option value="competition">Compétition</option>
                                </select>
                            </div>

                            {/* Age Category */}
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                                <label className="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                                    Catégorie
                                </label>
                                <select 
                                    value={ageCategory}
                                    onChange={(e) => setAgeCategory(e.target.value)}
                                    className="w-full bg-transparent border-none p-0 text-gray-900 focus:ring-0 font-medium cursor-pointer text-sm">
                                    <option value="">Tous</option>
                                    {(ageCategories || []).map((cat) => (
                                        <option key={cat.id} value={cat.nom}>
                                            {cat.nom}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Search Button */}
                            <button
                                onClick={() => {
                                    const params = new URLSearchParams();
                                    if (location) {
                                        params.append("location", location);
                                        params.append("location_type", locationType);
                                    }
                                    if (startDate) params.append("date", startDate.toISOString().split('T')[0]);
                                    if (category !== "all") params.append("category", category);
                                    if (ageCategory) params.append("age_category", ageCategory);
                                    
                                    router.visit(route("raids.index") + (params.toString() ? `?${params.toString()}` : ""));
                                }}
                                className="bg-gray-800 hover:bg-gray-700 text-white rounded-md px-6 py-3 font-semibold transition flex items-center justify-center gap-2 md:w-auto w-full text-sm"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.5 5.5a7.5 7.5 0 0 0 10.5 10.5Z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Empty State */}
                    {filteredRaids.length === 0 ? (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-12 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 text-gray-300 mx-auto mb-4">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                            </svg>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                {searchQuery ? 'Aucun résultat trouvé' : 'Aucun raid disponible'}
                            </h3>
                            <p className="text-gray-600 mb-6 max-w-md mx-auto">
                                {searchQuery 
                                    ? 'Essayez de modifier votre recherche ou effacez les filtres pour voir tous les raids.'
                                    : 'Il n\'y a actuellement aucun raid disponible. Revenez bientôt pour découvrir de nouvelles aventures !'}
                            </p>
                            {searchQuery ? (
                                <button
                                    onClick={clearSearch}
                                    className="inline-flex items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Voir tous les raids
                                </button>
                            ) : isClubLeader && (
                                <Link href={route('raids.create')}>
                                    <button className="inline-flex items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Créer le premier raid
                                    </button>
                                </Link>
                            )}
                        </div>
                    ) : (
                        <>
                            {/* Raids Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                {filteredRaids.map((raid) => (
                                    <Link key={raid.raid_id} href={route('raids.show', raid.raid_id)}>
                                        <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow h-full flex flex-col">
                                            {/* Raid Image */}
                                            <div className="relative h-48 overflow-hidden bg-gradient-to-br from-indigo-100 to-indigo-50">
                                                {raid.raid_image ? (
                                                    <img
                                                        src={`/storage/${raid.raid_image}`}
                                                        alt={raid.raid_name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 text-indigo-300">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                                                        </svg>
                                                    </div>
                                                )}
                                                {raid.races_count > 0 && (
                                                    <div className="absolute top-3 right-3">
                                                        <span className="bg-indigo-600 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                                            {raid.races_count} course{raid.races_count > 1 ? 's' : ''}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Raid Content */}
                                            <div className="p-6 flex-1 flex flex-col">
                                                <h3 className="text-lg font-bold text-gray-900 mb-3">
                                                    {raid.raid_name}
                                                </h3>

                                                {/* Location */}
                                                <div className="flex items-start gap-2 mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                    </svg>
                                                    <p className="text-sm text-gray-600 line-clamp-1">
                                                        {raid.raid_city} {raid.raid_postal_code}
                                                    </p>
                                                </div>

                                                {/* Dates */}
                                                <div className="flex items-start gap-2 mb-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                    </svg>
                                                    <p className="text-sm text-gray-600">
                                                        {formatDate(raid.raid_date_start)}
                                                        {raid.raid_date_end && raid.raid_date_start !== raid.raid_date_end && (
                                                            <> - {formatDate(raid.raid_date_end)}</>
                                                        )}
                                                    </p>
                                                </div>

                                                {/* Club */}
                                                {raid.club && (
                                                    <div className="flex items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 text-gray-400 flex-shrink-0">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                                        </svg>
                                                        <p className="text-xs text-gray-500 font-medium">
                                                            {raid.club.club_name}
                                                        </p>
                                                    </div>
                                                )}

                                                {/* CTA Button */}
                                                <div className="mt-auto">
                                                    <button className="w-full inline-flex justify-center items-center px-4 py-2 bg-important border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                        Voir les détails
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
        </AuthenticatedLayout>
    );
}
