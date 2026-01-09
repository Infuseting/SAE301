import { Link, usePage } from "@inertiajs/react";

/**
 * UserLastRaces component - Displays user's teams in a card grid layout
 * @param {Array} teams - Array of team objects with name, image, and members
 */
export default function UserLastRaces({ races = [] }) {
    const messages = usePage().props.translations?.messages || {};

    return (
        <div className="py-6">
            <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <h2 className="text-2xl font-bold text-gray-900 mb-6">
                    {messages['user_last_races.title'] || 'Your last races'}
                </h2>
                
                {races.length === 0 ? (
                    <div className="bg-white p-6 border border-gray-200 rounded-lg shadow-sm text-center text-gray-500">
                        {messages['user_last_races.no_races'] || 'You have not participated in any race yet.'}
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {races.map((race) => (
                            <div 
                                key={race.id} 
                                className="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200 flex flex-col"
                            >
                                {/* Race Image - Fixed height at top */}
                                <div className="w-full h-40 overflow-hidden bg-gray-100">
                                    {race.image ? (
                                        <img 
                                            src={race.image} 
                                            alt={race.name}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-gray-400 bg-gradient-to-br from-blue-50 to-blue-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                                            </svg>
                                        </div>
                                    )}
                                </div>

                                {/* Race Name */}
                                <div className="p-4 pb-2">
                                    <h3 className="text-lg font-bold text-gray-900 truncate">
                                        {race.name}
                                    </h3>
                                </div>

                                {/* Divider */}
                                <div className="border-t border-gray-100 mx-4" />

                                {/* Race Info */}
                                <div className="p-4 pt-3">
                                    <div className="flex justify-between items-center">
                                        <div>
                                            <p className="text-xs text-gray-500 uppercase tracking-wide">{messages['user_last_races.date'] || 'Date'}</p>
                                            <p className="text-sm font-medium text-gray-900">{race.date}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-xs text-gray-500 uppercase tracking-wide">{messages['user_last_races.position'] || 'Position'}</p>
                                            <p className="text-lg font-bold text-blue-600">{race.position}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-auto p-4 pt-0">
                                    <Link href= {route('races.show', race.id)}> 
                                        <button 
                                            
                                            className="inline-block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200"
                                        >
                                            {messages['user_last_races.view_race'] || 'View race'}
                                        </button>
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    ); 
}