import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ClubCard from '@/Components/ClubCard';

export default function Index({ clubs, filters }) {
    const messages = usePage().props.translations?.messages || {};
    const { auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    
    // Check if user can create clubs (must be adherent or admin)
    const userRoles = auth?.user?.roles || [];
    const canCreateClub = userRoles.some(role => 
        ['adherent', 'admin'].includes(role.name || role)
    );

    return (
        <AuthenticatedLayout>
            <Head title={messages.clubs} />

            <div className="min-h-screen bg-gray-50">
                {/* Hero Section - Matching Welcome.jsx style */}
                <div className="relative bg-gradient-to-r from-emerald-600 to-emerald-500 py-20">
                    {/* Subtle Pattern Overlay */}
                    <div className="absolute inset-0 opacity-10">
                        <div className="absolute inset-0" style={{
                            backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                            backgroundSize: '32px 32px'
                        }} />
                    </div>

                    <div className="relative max-w-7xl mx-auto px-6">
                        <div className="text-center">
                            {/* Title */}
                            <h1 className="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4 drop-shadow-lg">
                                {messages.browse_clubs}
                            </h1>
                            <p className="mt-4 max-w-2xl mx-auto text-xl text-emerald-50">
                                {messages.clubs_subtitle}
                            </p>

                            {/* Search Bar - Matching Welcome.jsx style */}
                            <div className="mt-10 max-w-3xl mx-auto">
                                <form method="GET" className="relative bg-white rounded-2xl p-2 shadow-2xl">
                                    <div className="flex flex-col md:flex-row gap-2">
                                        <div className="flex-1 px-4 py-3 flex items-center gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5 text-emerald-600">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                            <input
                                                type="text"
                                                name="search"
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                placeholder={messages.search_clubs}
                                                className="w-full bg-transparent border-none p-0 text-gray-900 placeholder-gray-400 focus:ring-0 font-medium text-lg"
                                            />
                                        </div>
                                        <button
                                            type="submit"
                                            className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-8 py-4 font-bold transition flex items-center justify-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor" className="w-5 h-5">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                            {messages.search_button}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Clubs Grid Section */}
                <section className="py-16 bg-gray-50">
                    <div className="max-w-7xl mx-auto px-6">
                        {/* Section Header */}
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-12">
                            <div>
                                <h2 className="text-3xl font-bold text-gray-900">
                                    {clubs.total} {messages.clubs}
                                </h2>
                                <p className="mt-2 text-gray-600">{messages.clubs_subtitle}</p>
                            </div>

                        {/* Create Club Button - Only show if user can create clubs */}
                        {canCreateClub && (
                            <Link
                                href={route('clubs.create')}
                                className="inline-flex items-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold transition shadow-lg shadow-emerald-900/20 hover:shadow-xl hover:-translate-y-0.5"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor" className="w-5 h-5 mr-2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                {messages.create_club}
                            </Link>
                        )}
                        </div>

                        {clubs.data.length === 0 ? (
                            <div className="text-center py-20">
                                <div className="bg-white rounded-2xl p-16 shadow-sm border border-gray-100 max-w-2xl mx-auto">
                                    {/* Icon */}
                                    <div className="bg-emerald-50 w-20 h-20 mx-auto rounded-3xl shadow-sm flex items-center justify-center mb-6 text-emerald-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-10 h-10">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                        </svg>
                                    </div>

                                    <h3 className="text-2xl font-bold text-gray-900 mb-3">
                                        {messages.no_clubs_found}
                                    </h3>
                                    <p className="text-gray-600 text-lg mb-8">
                                        {messages.no_clubs_description}
                                    </p>
                                    {/* Create Club Button - Only show if user can create clubs */}
                                    {canCreateClub && (
                                        <Link
                                            href={route('clubs.create')}
                                            className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold transition shadow-lg hover:shadow-xl"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            {messages.create_club}
                                        </Link>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <>
                                {/* Clubs Grid */}
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                    {clubs.data.map((club) => (
                                        <ClubCard key={club.club_id} club={club} />
                                    ))}
                                </div>

                                {/* Pagination */}
                                {clubs.last_page > 1 && (
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
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
