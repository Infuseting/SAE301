import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import UserMenu from '@/Components/UserMenu';

export default function LeaderboardIndex({ races, selectedRace, results, type, search }) {
    const { auth } = usePage().props;
    const messages = usePage().props.translations?.messages || {};
    
    const [searchTerm, setSearchTerm] = useState(search || '');
    const [selectedType, setSelectedType] = useState(type || 'individual');
    const [selectedRaceId, setSelectedRaceId] = useState(selectedRace?.race_id || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('leaderboard.index'), {
            race_id: selectedRaceId,
            type: selectedType,
            search: searchTerm,
        }, { preserveState: true });
    };

    const handleRaceChange = (raceId) => {
        setSelectedRaceId(raceId);
        router.get(route('leaderboard.index'), {
            race_id: raceId,
            type: selectedType,
            search: searchTerm,
        }, { preserveState: true });
    };

    const handleTypeChange = (newType) => {
        setSelectedType(newType);
        if (selectedRaceId) {
            router.get(route('leaderboard.index'), {
                race_id: selectedRaceId,
                type: newType,
                search: searchTerm,
            }, { preserveState: true });
        }
    };

    const getRankBadge = (rank) => {
        if (rank === 1) return 'bg-yellow-400 text-yellow-900';
        if (rank === 2) return 'bg-gray-300 text-gray-800';
        if (rank === 3) return 'bg-amber-600 text-white';
        return 'bg-gray-100 text-gray-700';
    };

    return (
        <>
            <Head title={messages.leaderboard || 'Leaderboard'} />

            <div className="min-h-screen bg-gray-50">
                {/* Navigation */}
                <nav className="border-b border-gray-100 bg-white">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex h-16 justify-between">
                            <div className="flex items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800 hover:text-emerald-600 transition-colors" />
                                </Link>
                                <span className="ml-4 text-xl font-bold text-gray-900">
                                    {messages.leaderboard || 'Leaderboard'}
                                </span>
                            </div>
                            <div className="flex items-center gap-4">
                                <LanguageSwitcher />
                                {auth.user ? (
                                    <UserMenu user={auth.user} />
                                ) : (
                                    <div className="flex gap-4">
                                        <Link
                                            href={route('login')}
                                            className="px-4 py-2 text-gray-700 hover:text-emerald-600 transition font-medium"
                                        >
                                            {messages.login || 'Login'}
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full transition font-bold"
                                        >
                                            {messages.register || 'Register'}
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </nav>

                <div className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        {/* Filters */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* Race Selection */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {messages.select_race || 'Select Race'}
                                    </label>
                                    <select
                                        value={selectedRaceId}
                                        onChange={(e) => handleRaceChange(e.target.value)}
                                        className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                        <option value="">{messages.choose_race || 'Choose a race...'}</option>
                                        {races.map((race) => (
                                            <option key={race.race_id} value={race.race_id}>
                                                {race.race_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Type Toggle */}
                                <div>
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
                                <div>
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

                        {/* Results */}
                        {selectedRace && results ? (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                    <h2 className="text-lg font-bold text-gray-900">
                                        {selectedRace.race_name} - {selectedType === 'team' 
                                            ? (messages.team_rankings || 'Team Rankings') 
                                            : (messages.individual_rankings || 'Individual Rankings')}
                                    </h2>
                                    <p className="text-sm text-gray-500">
                                        {results.total} {messages.results || 'results'}
                                    </p>
                                </div>

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
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {results.data.map((result) => (
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
                                                        race_id: selectedRaceId,
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
                                                    onClick={() => router.get(route('leaderboard.index'), {
                                                        race_id: selectedRaceId,
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
                        ) : (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 mx-auto text-gray-300 mb-4">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-2.748 0" />
                                </svg>
                                <h3 className="text-xl font-bold text-gray-900 mb-2">
                                    {messages.select_race_to_view || 'Select a race to view rankings'}
                                </h3>
                                <p className="text-gray-500">
                                    {messages.choose_race_above || 'Choose a race from the dropdown above to see the leaderboard'}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
