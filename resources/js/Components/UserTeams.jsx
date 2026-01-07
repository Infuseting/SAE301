/**
 * UserLastRaces component - Displays user's teams in a card grid layout
 * @param {Array} teams - Array of team objects with name, image, and members
 */
export default function UserTeams({ teams = [] }) {
    // Demo data for display purposes
    const demoTeams = [
        {
            id: 1,
            name: "Les Coureurs",
            image: null,
            members: [
                { id: 1, name: "Jean Dupont" },
                { id: 2, name: "Marie Martin" },
                { id: 3, name: "Pierre Durand" },
            ]
        },
        {
            id: 2,
            name: "Speed Runners",
            image: null,
            members: [
                { id: 4, name: "Sophie Bernard" },
                { id: 5, name: "Lucas Petit" },
            ]
        },
        
    ];

    const displayTeams = teams.length > 0 ? teams : demoTeams;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <h2 className="text-2xl font-bold text-gray-900 mb-6">
                    Vos équipes
                </h2>
                
                {displayTeams.length === 0 ? (
                    <div className="bg-white p-6 border border-gray-200 rounded-lg shadow-sm text-center text-gray-500">
                        Vous n'êtes membre d'aucune équipe pour le moment.
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {displayTeams.map((team) => (
                            <div 
                                key={team.id} 
                                className="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200 flex flex-col"
                            >
                                {/* Team Image - Fixed height at top */}
                                <div className="w-full h-40 overflow-hidden bg-gray-100">
                                    {team.image ? (
                                        <img 
                                            src={team.image} 
                                            alt={team.name}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-gray-400 bg-gradient-to-br from-gray-100 to-gray-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                            </svg>
                                        </div>
                                    )}
                                </div>

                                {/* Team Info */}
                                <div className="p-4">
                                    <h3 className="text-lg font-bold text-gray-900 truncate">
                                        {team.name}
                                    </h3>
                                    <p className="text-sm text-gray-500">
                                        {team.members?.length || 0} membre{(team.members?.length || 0) > 1 ? 's' : ''}
                                    </p>
                                </div>

                                {/* Divider */}
                                <div className="border-t border-gray-100" />

                                {/* Members List */}
                                <div className="p-4">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Membres</h4>
                                    <ul className="space-y-2">
                                        {team.members?.slice(0, 5).map((member) => (
                                            <li key={member.id} className="flex items-center text-sm text-gray-600">
                                                <div className="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium text-gray-600 mr-2">
                                                    {member.name?.charAt(0)?.toUpperCase() || '?'}
                                                </div>
                                                <span className="truncate">{member.name}</span>
                                            </li>
                                        ))}
                                        {team.members?.length > 5 && (
                                            <li className="text-sm text-gray-400 italic">
                                                +{team.members.length - 5} autre{team.members.length - 5 > 1 ? 's' : ''}
                                            </li>
                                        )}
                                    </ul>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    ); 
}