import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Header from '@/Components/Header';

/**
 * Public Leaderboard Index page - Shows all public race rankings
 * Features: search by name/race, filter by individual/team, export CSV
 * Only shows users with public profiles
 */
export default function LeaderboardIndex({ races, selectedRace, results, type, search }) {
    const { auth } = usePage().props;
    const messages = usePage().props.translations?.messages || {};
    
    const [searchTerm, setSearchTerm] = useState(search || '');
    const [selectedType, setSelectedType] = useState(type || 'individual');

    /**
     * Handle search form submission
     */
    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('leaderboard.index'), {
            type: selectedType,
            search: searchTerm,
        }, { preserveState: true });
    };

    /**
     * Handle type toggle (individual/team)
     */
    const handleTypeChange = (newType) => {
        setSelectedType(newType);
        router.get(route('leaderboard.index'), {
            type: newType,
            search: searchTerm,
        }, { preserveState: true });
    };

    /**
     * Handle CSV export
     * - If a specific race is selected, exports that race only
     * - If viewing general leaderboard (all races), exports all races
     */
    const handleExport = () => {
        if (selectedRace && selectedRace.race_id) {
            // Export specific race
            window.location.href = route('leaderboard.export', { 
                raceId: selectedRace.race_id, 
                type: selectedType 
            });
        } else if (results?.data?.length > 0) {
            // Export all races (general leaderboard)
            window.location.href = route('leaderboard.export.all', { 
                type: selectedType 
            });
        }
    };

    /**
     * Get badge style based on rank
     */
    const getRankBadge = (rank) => {
        if (rank === 1) return 'bg-yellow-400 text-yellow-900';
        if (rank === 2) return 'bg-gray-300 text-gray-800';
        if (rank === 3) return 'bg-amber-600 text-white';
        return 'bg-gray-100 text-gray-700';
    };

    /**
     * Format date to localized string
     */
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    const isTeamView = selectedType === 'team';

    return (
        <>
            <Head title={messages.general_leaderboard || 'Classement Général'} />

            <div className="min-h-screen bg-gray-50">
                {/* Navigation */}
                <Header />

                <div className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        {/* Info banner about public profiles */}
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div className="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5 text-blue-600">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                <p className="text-sm text-blue-700">
                                    {messages.public_profiles_only || 'Seuls les participants avec un profil public sont affichés dans ce classement.'}
                                </p>
                            </div>
                        </div>

                        {/* Filters */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* Type Toggle */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {messages.view_type || 'Type de classement'}
                                    </label>
                                    <div className="flex rounded-lg border border-gray-300 overflow-hidden">
                                        <button
                                            onClick={() => handleTypeChange('individual')}
                                            className={`flex-1 px-4 py-2 text-sm font-medium transition ${
                                                selectedType === 'individual'
                                                    ? 'bg-emerald-600 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            {messages.individual || 'Individuel'}
                                        </button>
                                        <button
                                            onClick={() => handleTypeChange('team')}
                                            className={`flex-1 px-4 py-2 text-sm font-medium transition ${
                                                selectedType === 'team'
                                                    ? 'bg-emerald-600 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            {messages.team || 'Équipe'}
                                        </button>
                                    </div>
                                </div>

                                {/* Search */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {messages.search || 'Rechercher'}
                                    </label>
                                    <form onSubmit={handleSearch} className="flex gap-2">
                                        <input
                                            type="text"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            placeholder={isTeamView 
                                                ? (messages.search_team_or_race || 'Équipe ou course...') 
                                                : (messages.search_name_or_race || 'Nom ou course...')}
                                            className="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        />
                                        <button
                                            type="submit"
                                            className="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>

                                {/* Export button */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {messages.export || 'Exporter'}
                                    </label>
                                    <button
                                        onClick={handleExport}
                                        disabled={!results?.data?.length}
                                        className={`w-full px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-2 ${
                                            results?.data?.length
                                                ? 'bg-gray-800 text-white hover:bg-gray-700'
                                                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                        }`}
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        {selectedRace ? 'CSV' : (messages.export_all || 'CSV (Toutes courses)')}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Results */}
                        {results && results.data && results.data.length > 0 ? (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                    <h2 className="text-lg font-bold text-gray-900">
                                        {selectedRace 
                                            ? `${selectedRace.race_name} - ${isTeamView ? (messages.team_rankings || 'Classement Équipes') : (messages.individual_rankings || 'Classement Individuel')}`
                                            : isTeamView 
                                                ? (messages.all_team_rankings || 'Tous les classements équipes')
                                                : (messages.all_individual_rankings || 'Tous les classements individuels')
                                        }
                                    </h2>
                                    <p className="text-sm text-gray-500">
                                        {results.total} {messages.results || 'résultats'}
                                    </p>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.rank || 'Rang'}
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {isTeamView ? (messages.team || 'Équipe') : (messages.name || 'Nom')}
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.race || 'Course'}
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.time || 'Temps'}
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.penalty || 'Malus'}
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.final_time || 'Temps final'}
                                                </th>
                                                {isTeamView && (
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        {messages.members || 'Membres'}
                                                    </th>
                                                )}
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {results.data.map((result, index) => (
                                                <tr key={`${result.id}-${index}`} className="hover:bg-gray-50 transition">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold ${getRankBadge(result.rank)}`}>
                                                            {result.rank}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            {isTeamView && result.team_image && (
                                                                <img
                                                                    src={result.team_image}
                                                                    alt={result.team_name}
                                                                    className="w-8 h-8 rounded-full mr-3 object-cover"
                                                                />
                                                            )}
                                                            {!isTeamView && result.user_photo && (
                                                                <img
                                                                    src={result.user_photo}
                                                                    alt={result.user_name}
                                                                    className="w-8 h-8 rounded-full mr-3 object-cover"
                                                                />
                                                            )}
                                                            <div>
                                                                <span className="font-medium text-gray-900">
                                                                    {isTeamView ? result.team_name : result.user_name}
                                                                </span>
                                                                {!isTeamView && result.user_id && (
                                                                    <Link 
                                                                        href={route('profile.show', result.user_id)}
                                                                        className="block text-xs text-emerald-600 hover:underline"
                                                                    >
                                                                        {messages.view_profile || 'Voir le profil'}
                                                                    </Link>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div>
                                                            <span className="text-gray-900">{result.race_name}</span>
                                                            <span className="block text-xs text-gray-500">{formatDate(result.race_date)}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-gray-700 font-mono">
                                                        {isTeamView ? result.average_temps_formatted : result.temps_formatted}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-red-600 font-mono">
                                                        +{isTeamView ? result.average_malus_formatted : result.malus_formatted}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap font-bold text-emerald-600 font-mono">
                                                        {isTeamView ? result.average_temps_final_formatted : result.temps_final_formatted}
                                                    </td>
                                                    {isTeamView && (
                                                        <td className="px-6 py-4 whitespace-nowrap text-gray-500">
                                                            {result.member_count}
                                                        </td>
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Pagination */}
                                {results.last_page > 1 && (
                                    <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                                        <p className="text-sm text-gray-500">
                                            Page {results.current_page} / {results.last_page}
                                        </p>
                                        <div className="flex gap-2">
                                            {results.current_page > 1 && (
                                                <button
                                                    onClick={() => router.get(route('leaderboard.index'), {
                                                        type: selectedType,
                                                        search: searchTerm,
                                                        page: results.current_page - 1,
                                                    })}
                                                    className="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 transition"
                                                >
                                                    {messages.previous || 'Précédent'}
                                                </button>
                                            )}
                                            {results.current_page < results.last_page && (
                                                <button
                                                    onClick={() => router.get(route('leaderboard.index'), {
                                                        type: selectedType,
                                                        search: searchTerm,
                                                        page: results.current_page + 1,
                                                    })}
                                                    className="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 transition"
                                                >
                                                    {messages.next || 'Suivant'}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 mx-auto text-gray-300 mb-4">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-2.748 0" />
                                </svg>
                                <h3 className="text-xl font-bold text-gray-900 mb-2">
                                    {search 
                                        ? (messages.no_results_found || 'Aucun résultat trouvé')
                                        : (messages.no_public_results || 'Aucun résultat public disponible')
                                    }
                                </h3>
                                <p className="text-gray-500">
                                    {search
                                        ? (messages.try_different_search || 'Essayez avec un autre terme de recherche.')
                                        : (messages.no_public_profiles_info || 'Les participants doivent avoir un profil public pour apparaître ici.')
                                    }
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
