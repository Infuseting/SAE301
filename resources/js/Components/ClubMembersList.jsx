import { useForm, usePage } from '@inertiajs/react';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

/**
 * ClubMembersList component - Displays club members and pending requests
 * 
 * @param {Array} members - Array of approved members
 * @param {Array} pendingRequests - Array of pending join requests (managers only)
 * @param {boolean} isManager - Whether current user is a manager
 * @param {number} clubId - Club ID for actions
 */
export default function ClubMembersList({ members = [], pendingRequests = [], isManager = false, clubId }) {
    const messages = usePage().props.translations?.messages || {};

    const { post, delete: destroy, processing } = useForm();

    const handleApprove = (userId) => {
        post(route('clubs.members.approve', { club: clubId, user: userId }));
    };

    const handleReject = (userId) => {
        post(route('clubs.members.reject', { club: clubId, user: userId }));
    };

    const handleRemove = (userId) => {
        if (confirm(messages.confirm_remove_member || 'Are you sure you want to remove this member?')) {
            destroy(route('clubs.members.remove', { club: clubId, user: userId }));
        }
    };

    return (
        <div className="space-y-8">
            {/* Pending Requests (Managers Only) */}
            {isManager && pendingRequests.length > 0 && (
                <div>
                    <h3 className="text-lg font-bold text-gray-900 mb-4">{messages.pending_requests}</h3>
                    <div className="bg-amber-50 border border-amber-200 rounded-lg divide-y divide-amber-200">
                        {pendingRequests.map((request) => (
                            <div key={request.id} className="p-4 flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <img
                                        src={request.profile_photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(request.name)}&background=10b981&color=fff`}
                                        alt={request.name}
                                        className="w-10 h-10 rounded-full"
                                    />
                                    <div>
                                        <p className="font-semibold text-gray-900">{request.name}</p>
                                        <p className="text-sm text-gray-500">{request.email}</p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <PrimaryButton
                                        onClick={() => handleApprove(request.id)}
                                        disabled={processing}
                                    >
                                        {messages.approve_club || 'Approve'}
                                    </PrimaryButton>
                                    <SecondaryButton
                                        onClick={() => handleReject(request.id)}
                                        disabled={processing}
                                    >
                                        {messages.reject_club || 'Reject'}
                                    </SecondaryButton>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Members List */}
            <div>
                <h3 className="text-lg font-bold text-gray-900 mb-4">
                    {messages.club_members} ({members.length})
                </h3>
                <div className="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
                    {members.length === 0 ? (
                        <div className="p-8 text-center text-gray-500">
                            {messages.no_members || 'No members yet'}
                        </div>
                    ) : (
                        members.map((member) => (
                            <div key={member.id} className="p-4 flex items-center justify-between hover:bg-gray-50 transition">
                                <div className="flex items-center space-x-3">
                                    <img
                                        src={member.profile_photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=10b981&color=fff`}
                                        alt={member.name}
                                        className="w-10 h-10 rounded-full"
                                    />
                                    <div>
                                        <p className="font-semibold text-gray-900">{member.name}</p>
                                        <div className="flex items-center space-x-2">
                                            <p className="text-sm text-gray-500">{member.email}</p>
                                            {member.pivot?.role === 'manager' && (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                                                    {messages.club_managers || 'Manager'}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                {isManager && member.pivot?.role !== 'manager' && (
                                    <DangerButton
                                        onClick={() => handleRemove(member.id)}
                                        disabled={processing}
                                    >
                                        {messages.remove || 'Remove'}
                                    </DangerButton>
                                )}
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
