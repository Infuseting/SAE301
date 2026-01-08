import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function LeaderboardResults({ results, raceId, type, search }) {
    const messages = usePage().props.translations?.messages || {};
    const [searchTerm, setSearchTerm] = useState(search || '');
    const [selectedType, setSelectedType] = useState(type || 'individual');

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
                preserveState: true,
            });
        }
    };

    const getRankBadge = (rank) => {
        if (rank === 1) return 'bg-yellow-400 text-yellow-900';
        if (rank === 2) return 'bg-gray-300 text-gray-800';
        if (rank === 3) return 'bg-amber-600 text-white';
        return 'bg-gray-100 text-gray-700';
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {messages.leaderboard_results || 'Leaderboard Results'}
                    </h2>
                    <button
                        onClick={() => router.get(route('admin.leaderboard.index'))}
                        className="text-sm text-emerald-600 hover:text-emerald-700"
                    >
                        ← {messages.back || 'Back'}
                    </button>
                </div>
            }
        >
            <Head title={messages.leaderboard_results || 'Leaderboard Results'} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row gap-4 items-end">
                                {/* Type Toggle */}
                                <div className="flex-1">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {messages.view_type || 'View Type'}
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
                                            {messages.individual || 'Individual'}
                                        </button>
                                        <button
                                            onClick={() => handleTypeChange('team')}
                                            className={`flex-1 px-4 py-2 text-sm font-medium transition ${
                                                selectedType === 'team'
                                                    ? 'bg-emerald-600 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            {messages.team || 'Team'}
                                        </button>
                                    </div>
                                </div>

                                {/* Search */}
                                <div className="flex-1">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {messages.search || 'Search'}
                                    </label>
                                    <form onSubmit={handleSearch} className="flex gap-2">
                                        <input
                                            type="text"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            placeholder={selectedType === 'team'
                                                ? (messages.search_team || 'Search team...')
                                                : (messages.search_name || 'Search name...')}
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
                            </div>
                        </div>
                    </div>

                    {/* Results Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {messages.rank || 'Rank'}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {selectedType === 'team' ? (messages.team || 'Team') : (messages.name || 'Name')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {messages.time || 'Time'}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {messages.penalty || 'Penalty'}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {messages.final_time || 'Final Time'}
                                        </th>
                                        {selectedType === 'team' && (
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.members || 'Members'}
                                            </th>
                                        )}
                                        {selectedType === 'individual' && (
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {messages.actions || 'Actions'}
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {results.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={selectedType === 'team' ? 6 : 6} className="px-6 py-12 text-center text-gray-500">
                                                {messages.no_results || 'No results found'}
                                            </td>
                                        </tr>
                                    ) : (
                                        results.data.map((result) => (
                                            <tr key={result.id} className="hover:bg-gray-50 transition">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold ${getRankBadge(result.rank)}`}>
                                                        {result.rank}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        {selectedType === 'team' && result.team_image && (
                                                            <img
                                                                src={result.team_image}
                                                                alt={result.team_name}
                                                                className="w-8 h-8 rounded-full mr-3 object-cover"
                                                            />
                                                        )}
                                                        <span className="font-medium text-gray-900">
                                                            {selectedType === 'team' ? result.team_name : result.user_name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-gray-700 font-mono">
                                                    {selectedType === 'team' ? result.average_temps_formatted : result.temps_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-red-600 font-mono">
                                                    +{selectedType === 'team' ? result.average_malus_formatted : result.malus_formatted}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap font-bold text-emerald-600 font-mono">
                                                    {selectedType === 'team' ? result.average_temps_final_formatted : result.temps_final_formatted}
                                                </td>
                                                {selectedType === 'team' && (
                                                    <td className="px-6 py-4 whitespace-nowrap text-gray-500">
                                                        {result.member_count}
                                                    </td>
                                                )}
                                                {selectedType === 'individual' && (
                                                    <td className="px-6 py-4 whitespace-nowrap text-right">
                                                        <button
                                                            onClick={() => handleDelete(result.id)}
                                                            className="text-red-600 hover:text-red-800 transition"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                )}
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {results.last_page > 1 && (
                            <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                                <p className="text-sm text-gray-500">
                                    {messages.showing || 'Showing'} {results.data.length} {messages.of || 'of'} {results.total} {messages.results || 'results'}
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
                                            {messages.previous || 'Previous'}
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
                                            {messages.next || 'Next'}
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
