import { Head, useForm, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function LeaderboardIndex({ races }) {
    const messages = usePage().props.translations?.messages || {};
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
        race_id: '',
        type: 'individual',
    });

    const [dragActive, setDragActive] = useState(false);

    const handleDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === 'dragenter' || e.type === 'dragover') {
            setDragActive(true);
        } else if (e.type === 'dragleave') {
            setDragActive(false);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            setData('file', e.dataTransfer.files[0]);
        }
    };

    const handleFileChange = (e) => {
        if (e.target.files && e.target.files[0]) {
            setData('file', e.target.files[0]);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.leaderboard.import'), {
            forceFormData: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    const viewResults = (raceId) => {
        router.get(route('admin.leaderboard.results', { raceId }));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {messages.leaderboard_management || 'Leaderboard Management'}
                </h2>
            }
        >
            <Head title={messages.leaderboard_management || 'Leaderboard Management'} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* CSV Import */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-bold text-gray-900 mb-4">
                                    {messages.import_csv || 'Import CSV'}
                                </h3>

                                <form onSubmit={submit} className="space-y-6">
                                    {/* Race Selection */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            {messages.select_race || 'Select Race'}
                                        </label>
                                        <select
                                            value={data.race_id}
                                            onChange={(e) => setData('race_id', e.target.value)}
                                            className="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                            <option value="">{messages.choose_race || 'Choose a race...'}</option>
                                            {races.map((race) => (
                                                <option key={race.race_id} value={race.race_id}>
                                                    {race.race_name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.race_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.race_id}</p>
                                        )}
                                    </div>

                                    {/* Import Type Selection */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            {messages.import_type || 'Import Type'}
                                        </label>
                                        <div className="flex gap-4">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="type"
                                                    value="individual"
                                                    checked={data.type === 'individual'}
                                                    onChange={(e) => setData('type', e.target.value)}
                                                    className="text-emerald-600 focus:ring-emerald-500"
                                                />
                                                <span className="text-sm text-gray-700">
                                                    {messages.individual || 'Individual'}
                                                </span>
                                            </label>
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="type"
                                                    value="team"
                                                    checked={data.type === 'team'}
                                                    onChange={(e) => setData('type', e.target.value)}
                                                    className="text-emerald-600 focus:ring-emerald-500"
                                                />
                                                <span className="text-sm text-gray-700">
                                                    {messages.team || 'Team'}
                                                </span>
                                            </label>
                                        </div>
                                        {errors.type && (
                                            <p className="mt-1 text-sm text-red-600">{errors.type}</p>
                                        )}
                                    </div>

                                    {/* File Upload */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            {messages.csv_file || 'CSV File'}
                                        </label>
                                        <div
                                            className={`relative border-2 border-dashed rounded-lg p-6 transition ${
                                                dragActive
                                                    ? 'border-emerald-500 bg-emerald-50'
                                                    : 'border-gray-300 hover:border-gray-400'
                                            }`}
                                            onDragEnter={handleDrag}
                                            onDragLeave={handleDrag}
                                            onDragOver={handleDrag}
                                            onDrop={handleDrop}
                                        >
                                            <input
                                                type="file"
                                                accept=".csv,.txt"
                                                onChange={handleFileChange}
                                                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            />
                                            <div className="text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-12 h-12 mx-auto text-gray-400 mb-3">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                                </svg>
                                                {data.file ? (
                                                    <p className="text-emerald-600 font-medium">{data.file.name}</p>
                                                ) : (
                                                    <>
                                                        <p className="text-gray-600 font-medium">
                                                            {messages.drag_drop_file || 'Drag & drop your CSV file here'}
                                                        </p>
                                                        <p className="text-sm text-gray-500 mt-1">
                                                            {messages.or_click_browse || 'or click to browse'}
                                                        </p>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                        {errors.file && (
                                            <p className="mt-1 text-sm text-red-600">{errors.file}</p>
                                        )}
                                    </div>

                                    {/* CSV Format Info */}
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <h4 className="text-sm font-medium text-gray-700 mb-2">
                                            {messages.csv_format || 'CSV Format'}
                                        </h4>
                                        <p className="text-xs text-gray-500 mb-2">
                                            {messages.csv_format_description || 'The CSV file should have the following columns (semicolon separated):'}
                                        </p>
                                        <code className="text-xs bg-white px-2 py-1 rounded border">
                                            {data.type === 'team' ? 'team_id;temps;malus' : 'user_id;temps;malus'}
                                        </code>
                                        <p className="text-xs text-gray-500 mt-2">
                                            {messages.time_format_info || 'Time can be in seconds (3600.50) or HH:MM:SS format (01:00:00.50)'}
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing || !data.file || !data.race_id}
                                        className="w-full px-4 py-3 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing
                                            ? (messages.importing || 'Importing...')
                                            : (messages.import || 'Import')}
                                    </button>
                                </form>
                            </div>
                        </div>

                        {/* Race List */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-bold text-gray-900 mb-4">
                                    {messages.races || 'Races'}
                                </h3>

                                {races.length === 0 ? (
                                    <div className="text-center py-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-12 h-12 mx-auto text-gray-300 mb-3">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                        <p className="text-gray-500">{messages.no_races || 'No races available'}</p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {races.map((race) => (
                                            <div
                                                key={race.race_id}
                                                className="flex items-center justify-between p-4 rounded-lg border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition"
                                            >
                                                <div 
                                                    className="flex-1 cursor-pointer"
                                                    onClick={() => viewResults(race.race_id)}
                                                >
                                                    <h4 className="font-medium text-gray-900">{race.race_name}</h4>
                                                    <p className="text-sm text-gray-500">
                                                        {new Date(race.race_date_start).toLocaleDateString()}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <a
                                                        href={route('admin.leaderboard.export', { raceId: race.race_id })}
                                                        className="p-2 text-gray-500 hover:text-emerald-600 hover:bg-emerald-100 rounded-lg transition"
                                                        title={messages.export_csv || 'Export CSV'}
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                        </svg>
                                                    </a>
                                                    <button
                                                        onClick={() => viewResults(race.race_id)}
                                                        className="p-2 text-gray-400 hover:text-emerald-600 transition"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
