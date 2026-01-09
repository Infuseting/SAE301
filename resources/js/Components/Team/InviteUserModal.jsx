import { useState, useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';

/**
 * Modal for inviting existing users to a team.
 * Supports two modes:
 * - Team mode (teamId provided): sends invite via API
 * - Create mode (onSelect provided): calls callback with user data
 */
export default function InviteUserModal({ isOpen, onClose, users, teamMembers, auth, onEmailInviteOpen, teamId, onSelect }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const { post, processing } = useForm();
    const messages = usePage().props.translations?.messages || {};

    // Mode création : recherche API
    const isCreateMode = !!onSelect;

    useEffect(() => {
        if (!isCreateMode || !searchQuery.trim()) {
            setSearchResults([]);
            return;
        }
        const timeoutId = setTimeout(async () => {
            setIsSearching(true);
            try {
                const response = await fetch(`/api/users/search?q=${encodeURIComponent(searchQuery)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (response.ok) {
                    const data = await response.json();
                    setSearchResults(data);
                }
            } catch (error) {
                console.error('Search error:', error);
            }
            setIsSearching(false);
        }, 300);
        return () => clearTimeout(timeoutId);
    }, [searchQuery, isCreateMode]);

    const memberIds = teamMembers?.map(m => m.id) || [];
    
    // En mode création, utiliser les résultats de recherche API
    // En mode équipe, filtrer la liste users fournie
    const availableUsers = isCreateMode 
        ? searchResults.filter(user => user.id !== auth?.user?.id && !memberIds.includes(user.id))
        : (users || [])
            .filter(user => user.id !== auth?.user?.id)
            .filter(user => !memberIds.includes(user.id))
            .filter(user => 
                (user.name || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
                (user.email || '').toLowerCase().includes(searchQuery.toLowerCase())
            );

    const handleInvite = (user) => {
        if (isCreateMode) {
            onSelect(user);
            onClose();
        } else {
            post(`/teams/${teamId}/invite/${user.id}`, { preserveScroll: true });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
                {/* Modal Header */}
                <div className="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 className="text-2xl font-bold text-gray-900">{messages['modal.invite_user.title'] || 'Invite users'}</h3>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                    >
                        ×
                    </button>
                </div>

                {/* Modal Body */}
                <div className="p-6">
                    {/* Search Bar */}
                    <input
                        type="text"
                        placeholder={messages['modal.invite_user.search_placeholder'] || 'Search for a user...'}
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4"
                    />

                    {/* User List */}
                    <div className="max-h-96 overflow-y-auto">
                        {availableUsers.length > 0 ? (
                            <div className="space-y-2">
                                {availableUsers.map((user) => (
                                    <div
                                        key={user.id}
                                        className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                                    >
                                        <div className="flex items-center gap-3">
                                            <img
                                                src={user.avatar || 'https://via.placeholder.com/40'}
                                                alt={user.name}
                                                className="h-10 w-10 rounded-full object-cover"
                                            />
                                            <div>
                                                <p className="font-semibold text-gray-900">{user.name}</p>
                                                <p className="text-sm text-gray-500">{user.email}</p>
                                            </div>
                                        </div>
                                        <button 
                                            onClick={() => handleInvite(user)}
                                            disabled={processing || isSearching}
                                            className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors disabled:opacity-50"
                                        >
                                            {processing || isSearching ? '...' : (isCreateMode ? (messages['modal.invite_user.add'] || 'Add') : (messages['modal.invite_user.invite'] || 'Invite'))}
                                        </button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <p className="text-gray-500 mb-4">
                                    {searchQuery ? (messages['modal.invite_user.no_user_found'] || 'No user found') : (messages['modal.invite_user.all_members'] || 'All users are already members')}
                                </p>
                                {searchQuery && (
                                    <button
                                        onClick={onEmailInviteOpen}
                                        className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                    >
                                        {messages['modal.invite_user.invite_new'] || 'Invite a new user'}
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {/* Modal Footer */}
                <div className="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        {messages['close'] || 'Close'}
                    </button>
                </div>
            </div>
        </div>
    );
}
