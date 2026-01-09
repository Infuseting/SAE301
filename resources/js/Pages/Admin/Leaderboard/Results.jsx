import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

/**
 * Admin Leaderboard Results page - Shows all race rankings (including private users)
 * 
 * Features:
 * - Individual rankings: Shows ALL users (public and private profiles)
 *   Displays: rank, name, race, raw time, malus, final time, points, visibility status
 * - Team rankings: All teams shown
 *   Displays: rank, team name, age category, race name, raw time, malus, final time, points, team members list
 * - Search by name/team
 * - Filter by individual/team
 * - Edit and delete results
 */
export default function LeaderboardResults({ results, raceId, race, type, search }) {
    const messages = usePage().props.translations?.messages || {};
    const [searchTerm, setSearchTerm] = useState(search || '');
    const [selectedType, setSelectedType] = useState(type || 'individual');
    const [editingResult, setEditingResult] = useState(null);
    const [editForm, setEditForm] = useState({});

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.leaderboard.results', { raceId }), {
            type: selectedType,
            search: searchTerm,
        }, { preserveState: true });
    };

    const handleTypeChange = (newType) => {
        setSelectedType(newType);
        router.get(route('admin.leaderboard.results', { raceId }), {
            type: newType,
            search: searchTerm,
        }, { preserveState: true });
    };

    const handleDelete = (resultId) => {
        if (confirm(messages.confirm_delete || 'Are you sure you want to delete this result?')) {
            router.delete(route('admin.leaderboard.destroy', { resultId }), {
                data: { type: selectedType },
                preserveState: true,
            });
        }
    };

    const handleEdit = (result) => {
        setEditingResult(result);
        if (selectedType === 'team') {
            setEditForm({
                average_temps: result.average_temps_formatted || '',
                average_malus: result.average_malus_formatted || '',
                points: result.points || 0,
                category: result.category || '',
                puce: result.puce || '',
            });
        } else {
            setEditForm({
                temps: result.temps_formatted || '',
                malus: result.malus_formatted || '',
                points: result.points || 0,
            });
        }
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        router.put(route('admin.leaderboard.update', { resultId: editingResult.id }), {
            type: selectedType,
            ...editForm,
        }, {
            preserveState: true,
            onSuccess: () => {
                setEditingResult(null);
                setEditForm({});
            },
        });
    };

    const handleEditCancel = () => {
        setEditingResult(null);
        setEditForm({});
    };

    const handleExport = () => {
        window.location.href = route('admin.leaderboard.export', { 
            raceId: raceId, 
            type: selectedType 
        });
    };

    const getRankBadge = (rank) => {
        if (rank === 1) return 'bg-yellow-400 text-yellow-900';
        if (rank === 2) return 'bg-gray-300 text-gray-800';
        if (rank === 3) return 'bg-amber-600 text-white';
        return 'bg-gray-100 text-gray-700';
    };

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
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {messages.leaderboard_results || 'Résultats du classement'}
                        </h2>
                        {race && (
                            <p className="text-sm text-gray-500 mt-1">
                                {race.race_name} - {formatDate(race.race_date_start)}
                            </p>
                        )}
                    </div>
                    <button
                        onClick={() => router.get(route('admin.leaderboard.index'))}
                        className="text-sm text-emerald-600 hover:text-emerald-700"
                    >
                        ← {messages.back || 'Retour'}
                    </button>
                </div>
            }
        >
            <Head title={messages.leaderboard_results || 'Résultats du classement'} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Info banner - Admin sees all users */}
                    {!isTeamView && (
                        <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                            <div className="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5 text-amber-600">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                                <p className="text-sm text-amber-700">
                                    {messages.admin_all_users_visible || 'Vue admin: Tous les participants sont affichés (profils publics et privés).'}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
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
                                                ? (messages.search_team || 'Rechercher une équipe...')
                                                : (messages.search_name || 'Rechercher un nom...')}
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
                                        CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Results Table */}
                    {results && results.data && results.data.length > 0 ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h2 className="text-lg font-bold text-gray-900">
                                    {race?.race_name} - {isTeamView ? (messages.team_rankings || 'Classement Équipes') : (messages.individual_rankings || 'Classement Individuel')}
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
                                            {isTeamView && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.category || 'Catégorie'}
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
                                                {messages.points || 'Points'}
                                            </th>
                                            {isTeamView && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {messages.members || 'Membres'}
                                                </th>
                                            )}
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.actions || 'Actions'}
                                            </th>
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
                                                            {/* Show visibility badge for individual results */}
                                                            {!isTeamView && (
                                                                <span className={`ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                                                    result.is_public
                                                                        ? 'bg-green-100 text-green-800'
                                                                        : 'bg-gray-100 text-gray-600'
                                                                }`}>
                                                                    {result.is_public
                                                                        ? (messages.public || 'Public')
                                                                        : (messages.private || 'Privé')}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                {isTeamView && (
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                            result.age_category ? 'bg-indigo-100 text-indigo-800' :
                                                            result.category === 'Masculin' ? 'bg-blue-100 text-blue-800' :
                                                            result.category === 'Féminin' ? 'bg-pink-100 text-pink-800' :
                                                            result.category === 'Mixte' ? 'bg-purple-100 text-purple-800' :
                                                            'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {result.age_category || result.category || '-'}
                                                        </span>
                                                    </td>
                                                )}
                                                <td className="px-6 py-4 whitespace-nowrap text-gray-700 font-mono">
                                                    {isTeamView ? result.average_temps_formatted : result.temps_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-red-600 font-mono">
                                                    +{isTeamView ? result.average_malus_formatted : result.malus_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap font-bold text-emerald-600 font-mono">
                                                    {isTeamView ? result.average_temps_final_formatted : result.temps_final_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold ${
                                                        result.points >= 100 ? 'bg-yellow-100 text-yellow-800' :
                                                        result.points >= 50 ? 'bg-emerald-100 text-emerald-800' :
                                                        result.points > 0 ? 'bg-blue-100 text-blue-800' :
                                                        'bg-gray-100 text-gray-500'
                                                    }`}>
                                                        {result.points || 0} pts
                                                    </span>
                                                </td>
                                                {isTeamView && (
                                                    <td className="px-6 py-4">
                                                        <div className="max-w-xs">
                                                            {result.members && result.members.length > 0 ? (
                                                                <div className="flex flex-wrap gap-1">
                                                                    {result.members.map((member, idx) => (
                                                                        <span 
                                                                            key={member.id || idx}
                                                                            className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                                                                        >
                                                                            {member.name}
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            ) : (
                                                                <span className="text-gray-400 text-sm">
                                                                    {result.member_count} {result.member_count > 1 ? (messages.members || 'membres') : (messages.member || 'membre')}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                )}
                                                <td className="px-6 py-4 whitespace-nowrap text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {/* Edit button */}
                                                        <button
                                                            onClick={() => handleEdit(result)}
                                                            className="text-blue-600 hover:text-blue-800 transition"
                                                            title={messages.edit || 'Modifier'}
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                            </svg>
                                                        </button>
                                                        {/* Delete button */}
                                                        <button
                                                            onClick={() => handleDelete(result.id)}
                                                            className="text-red-600 hover:text-red-800 transition"
                                                            title={messages.delete || 'Supprimer'}
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
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
                                                onClick={() => router.get(route('admin.leaderboard.results', { raceId }), {
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
                                                onClick={() => router.get(route('admin.leaderboard.results', { raceId }), {
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
                                {messages.no_results_found || 'Aucun résultat trouvé'}
                            </h3>
                            <p className="text-gray-500">
                                {search
                                    ? (messages.try_different_search || 'Essayez avec un autre terme de recherche.')
                                    : (messages.no_results_for_race || 'Aucun résultat pour cette course.')
                                }
                            </p>
                        </div>
                    )}
                </div>
            </div>

            {/* Edit Modal */}
            {editingResult && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
                        <div className="px-6 py-4 border-b border-gray-100">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-bold text-gray-900">
                                    {messages.edit_result || 'Modifier le résultat'}
                                </h3>
                                <button
                                    onClick={handleEditCancel}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p className="text-sm text-gray-500 mt-1">
                                {isTeamView ? editingResult.team_name : editingResult.user_name}
                            </p>
                        </div>

                        <form onSubmit={handleEditSubmit} className="p-6 space-y-4">
                            {/* Info about rank */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p className="text-sm text-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 inline mr-1">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                    {messages.rank_auto_calculated || 'Le rang est recalculé automatiquement selon le temps final.'}
                                </p>
                            </div>

                            {isTeamView ? (
                                <>
                                    {/* Team fields */}
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {messages.time || 'Temps'}
                                            </label>
                                            <input
                                                type="text"
                                                value={editForm.average_temps}
                                                onChange={(e) => setEditForm({...editForm, average_temps: e.target.value})}
                                                placeholder="HH:MM:SS.SS"
                                                className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {messages.penalty || 'Malus'}
                                            </label>
                                            <input
                                                type="text"
                                                value={editForm.average_malus}
                                                onChange={(e) => setEditForm({...editForm, average_malus: e.target.value})}
                                                placeholder="HH:MM:SS.SS"
                                                className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {messages.points || 'Points'}
                                            </label>
                                            <input
                                                type="number"
                                                value={editForm.points}
                                                onChange={(e) => setEditForm({...editForm, points: parseInt(e.target.value) || 0})}
                                                min="0"
                                                className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {messages.category || 'Catégorie'}
                                            </label>
                                            <select
                                                value={editForm.category}
                                                onChange={(e) => setEditForm({...editForm, category: e.target.value})}
                                                className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                                <option value="">{messages.none || '-'}</option>
                                                <option value="Masculin">{messages.male || 'Masculin'}</option>
                                                <option value="Féminin">{messages.female || 'Féminin'}</option>
                                                <option value="Mixte">{messages.mixed || 'Mixte'}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {messages.puce || 'Puce'}
                                        </label>
                                        <input
                                            type="text"
                                            value={editForm.puce}
                                            onChange={(e) => setEditForm({...editForm, puce: e.target.value})}
                                            className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        />
                                    </div>
                                </>
                            ) : (
                                <>
                                    {/* Individual fields */}
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {messages.time || 'Temps'}
                                            </label>
                                            <input
                                                type="text"
                                                value={editForm.temps}
                                                onChange={(e) => setEditForm({...editForm, temps: e.target.value})}
                                                placeholder="HH:MM:SS.SS"
                                                className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {messages.penalty || 'Malus'}
                                            </label>
                                            <input
                                                type="text"
                                                value={editForm.malus}
                                                onChange={(e) => setEditForm({...editForm, malus: e.target.value})}
                                                placeholder="HH:MM:SS.SS"
                                                className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {messages.points || 'Points'}
                                        </label>
                                        <input
                                            type="number"
                                            value={editForm.points}
                                            onChange={(e) => setEditForm({...editForm, points: parseInt(e.target.value) || 0})}
                                            min="0"
                                            className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        />
                                    </div>
                                </>
                            )}

                            {/* Form buttons */}
                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    onClick={handleEditCancel}
                                    className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                                >
                                    {messages.cancel || 'Annuler'}
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
                                >
                                    {messages.save || 'Enregistrer'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
