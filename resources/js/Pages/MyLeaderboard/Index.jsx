import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * MyLeaderboard Index page - Shows the authenticated user's race results
 * Includes search by race name, sort by best/worst score, and filter by individual/team
 */
export default function MyLeaderboardIndex({ results, search, sortBy, type }) {
    const messages = usePage().props.translations?.messages || {};
    const { auth } = usePage().props;

    const [searchTerm, setSearchTerm] = useState(search || '');
    const [selectedSort, setSelectedSort] = useState(sortBy || 'best');
    const [selectedType, setSelectedType] = useState(type || 'individual');

    /**
     * Handle search form submission
     */
    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('my-leaderboard.index'), {
            search: searchTerm,
            sort: selectedSort,
            type: selectedType,
        }, { preserveState: true });
    };

    /**
     * Handle sort change
     */
    const handleSortChange = (newSort) => {
        setSelectedSort(newSort);
        router.get(route('my-leaderboard.index'), {
            search: searchTerm,
            sort: newSort,
            type: selectedType,
        }, { preserveState: true });
    };

    /**
     * Handle type change (individual/team)
     */
    const handleTypeChange = (newType) => {
        setSelectedType(newType);
        router.get(route('my-leaderboard.index'), {
            search: searchTerm,
            sort: selectedSort,
            type: newType,
        }, { preserveState: true });
    };

    /**
     * Get badge style based on rank
     */
    const getRankBadge = (rank, total) => {
        const percentage = (rank / total) * 100;
        if (rank === 1) return 'bg-yellow-400 text-yellow-900'; // Gold
        if (rank === 2) return 'bg-gray-300 text-gray-800'; // Silver
        if (rank === 3) return 'bg-amber-600 text-white'; // Bronze
        if (percentage <= 10) return 'bg-emerald-100 text-emerald-800'; // Top 10%
        if (percentage <= 25) return 'bg-blue-100 text-blue-800'; // Top 25%
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
        <AuthenticatedLayout>
            <Head title={messages.my_rankings || 'Mes Classements'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900">
                            {messages.my_rankings || 'Mes Classements'}
                        </h1>
                        <p className="mt-2 text-gray-600">
                            {messages.my_rankings_description || 'Retrouvez tous vos résultats aux courses auxquelles vous avez participé.'}
                        </p>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Search by race name */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    {messages.search_race || 'Rechercher une course'}
                                </label>
                                <form onSubmit={handleSearch} className="flex gap-2">
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder={messages.race_name_placeholder || 'Nom de la course...'}
                                        className="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <button
                                        type="submit"
                                        className="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            {/* Sort dropdown */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    {messages.sort_by || 'Trier par'}
                                </label>
                                <select
                                    value={selectedSort}
                                    onChange={(e) => handleSortChange(e.target.value)}
                                    className="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="best">{messages.best_score || 'Meilleur score'}</option>
                                    <option value="worst">{messages.worst_score || 'Pire score'}</option>
                                </select>
                            </div>

                            {/* Type toggle (individual/team) */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    {messages.view_type || 'Type de classement'}
                                </label>
                                <div className="flex rounded-lg border border-gray-300 overflow-hidden">
                                    <button
                                        onClick={() => handleTypeChange('individual')}
                                        className={`flex-1 px-4 py-2 text-sm font-medium transition ${
                                            selectedType === 'individual'
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        {messages.individual || 'Individuel'}
                                    </button>
                                    <button
                                        onClick={() => handleTypeChange('team')}
                                        className={`flex-1 px-4 py-2 text-sm font-medium transition ${
                                            selectedType === 'team'
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        {messages.team || 'Équipe'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Results */}
                    {results && results.data && results.data.length > 0 ? (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h2 className="text-lg font-bold text-gray-900">
                                    {isTeamView 
                                        ? (messages.your_team_results || 'Vos résultats en équipe')
                                        : (messages.your_results || 'Vos résultats')
                                    }
                                </h2>
                                <p className="text-sm text-gray-500">
                                    {results.total} {results.total > 1 ? (messages.races_participated || 'courses participées') : (messages.race_participated || 'course participée')}
                                </p>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.position || 'Position'}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.race || 'Course'}
                                            </th>
                                            {isTeamView && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.team || 'Équipe'}
                                                </th>
                                            )}
                                            {!isTeamView && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.team || 'Équipe'}
                                                </th>
                                            )}
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.time || 'Temps'}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.penalty || 'Malus'}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.final_time || 'Temps final'}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.date || 'Date'}
                                            </th>
                                            {isTeamView && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.members || 'Membres'}
                                                </th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {results.data.map((result) => (
                                            <tr key={result.id} className="hover:bg-gray-50 transition">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        <span className={`inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold ${getRankBadge(result.rank, result.total_participants)}`}>
                                                            {result.rank}
                                                        </span>
                                                        <span className="text-sm text-gray-500">
                                                            / {result.total_participants}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="font-medium text-gray-900">
                                                        {result.race_name}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="text-gray-700">
                                                        {result.team_name || '-'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-gray-700 font-mono">
                                                    {isTeamView ? result.average_temps_formatted : result.temps_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-red-600 font-mono">
                                                    +{isTeamView ? result.average_malus_formatted : result.malus_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap font-bold text-indigo-600 font-mono">
                                                    {isTeamView ? result.average_temps_final_formatted : result.temps_final_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-gray-500">
                                                    {formatDate(result.race_date)}
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
                        </div>
                    ) : (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 mx-auto text-gray-300 mb-4">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-2.748 0" />
                            </svg>
                            <h3 className="text-xl font-bold text-gray-900 mb-2">
                                {search 
                                    ? (messages.no_results_found || 'Aucun résultat trouvé')
                                    : isTeamView
                                        ? (messages.no_team_races_yet || 'Vous n\'avez pas encore participé à des courses en équipe')
                                        : (messages.no_races_yet || 'Vous n\'avez pas encore participé à des courses')
                                }
                            </h3>
                            <p className="text-gray-500">
                                {search
                                    ? (messages.try_different_search || 'Essayez avec un autre terme de recherche.')
                                    : (messages.participate_to_see_rankings || 'Participez à des courses pour voir vos classements ici.')
                                }
                            </p>
                        </div>
                    )}

                    {/* Stats summary */}
                    {results && results.data && results.data.length > 0 && (
                        <div className="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Total races */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-indigo-100 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6 text-indigo-600">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500">{messages.total_races || 'Courses totales'}</p>
                                        <p className="text-2xl font-bold text-gray-900">{results.total}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Best position */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-yellow-100 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6 text-yellow-600">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-2.748 0" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500">{messages.best_position || 'Meilleure position'}</p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {Math.min(...results.data.map(r => r.rank))}<sup>e</sup>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Podiums count */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-amber-100 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6 text-amber-600">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500">{messages.podiums || 'Podiums'}</p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {results.data.filter(r => r.rank <= 3).length}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
