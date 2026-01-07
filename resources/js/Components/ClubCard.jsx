import { Link, usePage } from '@inertiajs/react';

/**
 * ClubCard component - Premium design matching Welcome.jsx brand identity
 * 
 * @param {Object} club - Club object with club_id, club_name, club_city, club_postal_code, is_approved, members_count
 * @param {string} className - Additional CSS classes
 */
export default function ClubCard({ club, className = '' }) {
    const messages = usePage().props.translations?.messages || {};

    return (
        <Link
            href={route('clubs.show', club.club_id)}
            className={`group relative bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition duration-300 block ${className}`}
        >
            {/* Hero Image Section with Gradient Overlay */}
            <div className="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600">
                {/* Club Image or Gradient Background */}
                {club.club_image ? (
                    <>
                        <img
                            src={`/storage/${club.club_image}`}
                            alt={club.club_name}
                            className="absolute inset-0 w-full h-full object-cover"
                        />
                        {/* Dark overlay for better text visibility */}
                        <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
                    </>
                ) : (
                    <>
                        {/* Subtle Pattern Overlay */}
                        <div className="absolute inset-0 opacity-10">
                            <div className="absolute inset-0" style={{
                                backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                                backgroundSize: '24px 24px'
                            }} />
                        </div>

                        {/* Animated Gradient on Hover */}
                        <div className="absolute inset-0 bg-gradient-to-tr from-emerald-600 to-teal-500 opacity-0 group-hover:opacity-100 transition-opacity duration-500" />

                        {/* Club Icon */}
                        <div className="absolute inset-0 flex items-center justify-center">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                strokeWidth={1.5}
                                stroke="currentColor"
                                className="w-24 h-24 text-white drop-shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-all duration-500"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                                />
                            </svg>
                        </div>
                    </>
                )}

                {/* Status Badge - Top Left */}
                <div className="absolute top-4 left-4">
                    <span className={`text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider shadow-lg ${club.is_approved
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-amber-50 text-amber-700'
                        }`}>
                        {club.is_approved ? (messages.approved || 'Approved') : (messages.pending || 'Pending')}
                    </span>
                </div>

                {/* Members Count - Top Right */}
                {club.members_count !== undefined && (
                    <div className="absolute top-4 right-4">
                        <div className="flex items-center gap-1.5 bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-full shadow-lg">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                strokeWidth={2}
                                stroke="currentColor"
                                className="w-4 h-4 text-emerald-600"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
                                />
                            </svg>
                            <span className="text-sm font-bold text-gray-900">{club.members_count}</span>
                        </div>
                    </div>
                )}
            </div>

            {/* Content Section */}
            <div className="p-6">
                {/* Club Name */}
                <h3 className="text-xl font-bold text-gray-900 mb-3 group-hover:text-emerald-600 transition">
                    {club.club_name}
                </h3>

                {/* Location */}
                <div className="flex items-center text-gray-500 text-sm mb-4">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        strokeWidth={1.5}
                        stroke="currentColor"
                        className="w-4 h-4 mr-1.5 text-emerald-600"
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
                    {club.club_city}, {club.club_postal_code}
                </div>

                {/* View Details Link */}
                <div className="flex items-center text-emerald-600 font-semibold text-sm group-hover:gap-2 transition-all">
                    <span>{messages.view_details || 'View Details'}</span>
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        strokeWidth={2}
                        stroke="currentColor"
                        className="w-4 h-4 group-hover:translate-x-1 transition-transform"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </div>
            </div>
        </Link>
    );
}
