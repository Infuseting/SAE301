import { useState, useEffect } from 'react';
import SelectResponsableModal from './SelectResponsableModal';

/**
 * Reusable User Selection Component
 * Displays the selected user or a button to select one, and manages the selection modal.
 * 
 * @param {Array} users - List of available users
 * @param {Number|String} selectedId - Currently selected user ID
 * @param {Function} onSelect - Callback when a user is selected (receives the user object)
 * @param {String} label - Label for the button/display (optional)
 * @param {String} idKey - The key to use for identifying users (default: 'id')
 */
export default function UserSelect({
    users = [],
    selectedId,
    onSelect,
    label = "Responsable",
    idKey = "id"
}) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

    // Update selected user when selectedId or users change
    useEffect(() => {
        if (selectedId && users.length > 0) {
            const user = users.find(u => String(u[idKey]) === String(selectedId));
            if (user) {
                setSelectedUser(user);
            }
        } else if (!selectedId) {
            setSelectedUser(null);
        }
    }, [selectedId, users, idKey]);

    const handleSelect = (user) => {
        setSelectedUser(user);
        onSelect(user);
        setIsModalOpen(false);
    };

    return (
        <div className="w-full">
            {selectedUser ? (
                <div className="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div className="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <span className="text-green-600 font-semibold text-sm">
                            {(selectedUser.name || selectedUser.full_name || "?").charAt(0).toUpperCase()}
                        </span>
                    </div>
                    <div className="flex-1">
                        <p className="text-sm font-medium text-gray-900">{selectedUser.name || selectedUser.full_name}</p>
                        <p className="text-xs text-gray-500">{selectedUser.email}</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setIsModalOpen(true)}
                        className="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                    >
                        Modifier
                    </button>
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => setIsModalOpen(true)}
                    className="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Sélectionner un {label.toLowerCase()}
                </button>
            )}

            <SelectResponsableModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSelect={handleSelect}
                users={users}
            />
        </div>
    );
}
