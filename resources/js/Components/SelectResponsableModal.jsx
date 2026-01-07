import { useState, useEffect } from 'react';

/**
 * Modal component for selecting a responsible user
 * Allows searching by name and selecting a single user
 * 
 * @param {boolean} isOpen - Controls modal visibility
 * @param {function} onClose - Callback when modal is closed
 * @param {function} onSelect - Callback when a user is selected
 * @param {array} users - Array of available users to select from
 */
export default function SelectResponsableModal({ isOpen, onClose, onSelect, users = [] }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [filteredUsers, setFilteredUsers] = useState([]);

    // Filter users based on search query
    useEffect(() => {
        if (searchQuery.trim() === '') {
            setFilteredUsers(users);
        } else {
            const query = searchQuery.toLowerCase();
            const filtered = users.filter(user => 
                user.name.toLowerCase().includes(query) ||
                (user.email && user.email.toLowerCase().includes(query))
            );
            setFilteredUsers(filtered);
        }
    }, [searchQuery, users]);

    // Reset search when modal opens
    useEffect(() => {
        if (isOpen) {
            setSearchQuery('');
            setFilteredUsers(users);
        }
    }, [isOpen, users]);

    /**
     * Handle user selection
     * @param {object} user - The selected user object
     */
    const handleSelectUser = (user) => {
        onSelect(user);
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            {/* Backdrop overlay */}
            <div 
                className="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                onClick={onClose}
            />
            
            {/* Modal container */}
            <div className="flex min-h-full items-center justify-center p-4">
                <div className="relative bg-white rounded-xl shadow-2xl w-full max-w-lg transform transition-all">
                    {/* Header */}
                    <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 className="text-lg font-semibold text-gray-900">
                            Sélectionner un responsable
                        </h3>
                        <button
                            type="button"
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600 transition"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Search input */}
                    <div className="px-6 py-4 border-b border-gray-100">
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Rechercher par nom ou email..."
                                className="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                autoFocus
                            />
                        </div>
                    </div>

                    {/* Users list */}
                    <div className="max-h-80 overflow-y-auto">
                        {filteredUsers.length > 0 ? (
                            <ul className="divide-y divide-gray-100">
                                {filteredUsers.map((user) => (
                                    <li key={user.id}>
                                        <button
                                            type="button"
                                            onClick={() => handleSelectUser(user)}
                                            className="w-full px-6 py-3 flex items-center gap-4 hover:bg-indigo-50 transition text-left"
                                        >
                                            {/* Avatar */}
                                            <div className="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <span className="text-indigo-600 font-semibold text-sm">
                                                    {user.name.charAt(0).toUpperCase()}
                                                </span>
                                            </div>
                                            {/* User info */}
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 truncate">
                                                    {user.name}
                                                </p>
                                                <p className="text-sm text-gray-500 truncate">
                                                    {user.email}
                                                </p>
                                            </div>
                                            {/* Select indicator */}
                                            <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="px-6 py-12 text-center">
                                <svg className="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p className="mt-4 text-sm text-gray-500">
                                    {searchQuery ? 'Aucun utilisateur trouvé' : 'Aucun utilisateur disponible'}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                        <button
                            type="button"
                            onClick={onClose}
                            className="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-4 rounded-lg transition"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
