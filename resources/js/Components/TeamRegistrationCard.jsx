import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import {
    Users, CheckCircle2, XCircle, Clock, CreditCard,
    FileText, UserCheck, ChevronDown, ChevronUp, Loader2
} from 'lucide-react';

/**
 * TeamRegistrationCard Component
 * 
 * Displays team members with their registration status (License, PPS, Payment, Presence)
 * and allows managers to validate PPS, payment, and presence.
 * 
 * This component is shared between:
 * - Race Scanner page (after QR code scan)
 * - Race Registration Management section
 * 
 * @param {Object} props
 * @param {Object} props.team - Team data with members
 * @param {number} props.raceId - Race ID for API calls
 * @param {Function} props.onPPSClick - Handler for PPS button click
 * @param {Function} props.onPaymentClick - Handler for Payment button click
 * @param {Function} props.onPresenceToggle - Handler for presence toggle
 * @param {Function} props.onMemberUpdate - Callback when a member is updated
 * @param {boolean} props.isCompact - If true, shows compact view (for scanner)
 * @param {boolean} props.showHeader - If true, shows team header
 */
export default function TeamRegistrationCard({
    team,
    raceId,
    onPPSClick,
    onPaymentClick,
    onPresenceToggle,
    onMemberUpdate,
    isCompact = false,
    showHeader = true
}) {
    const page = usePage();
    const [expanded, setExpanded] = useState(!isCompact);
    const [loadingPresence, setLoadingPresence] = useState({});
    const [members, setMembers] = useState(team?.members || []);

    if (!team || !team.members || team.members.length === 0) {
        return (
            <div className="bg-gray-50 rounded-xl p-6 text-center border-2 border-gray-200">
                <Users className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                <p className="text-gray-500 font-medium">Aucun membre trouvé</p>
            </div>
        );
    }

    /**
     * Handle presence toggle for a member
     * @param {number} regId - Registration ID
     */
    const handleTogglePresence = async (regId) => {
        setLoadingPresence(prev => ({ ...prev, [regId]: true }));
        
        try {
            const response = await axios.post(`/races/${raceId}/toggle-presence`, {
                reg_id: regId
            });

            if (response.data.success) {
                // Update local state
                setMembers(prevMembers => 
                    prevMembers.map(m => 
                        m.reg_id === regId 
                            ? { ...m, is_present: response.data.is_present }
                            : m
                    )
                );
                
                // Notify parent component
                if (onPresenceToggle) {
                    onPresenceToggle(regId, response.data.is_present);
                }
                if (onMemberUpdate) {
                    onMemberUpdate(regId, { is_present: response.data.is_present });
                }
            }
        } catch (error) {
            console.error('Error toggling presence:', error);
        } finally {
            setLoadingPresence(prev => ({ ...prev, [regId]: false }));
        }
    };

    /**
     * Handle PPS click for a member
     * @param {Object} member - Member data
     */
    const handlePPSClick = (member) => {
        if (onPPSClick) {
            onPPSClick(member);
        }
    };

    /**
     * Handle Payment click
     */
    const handlePaymentClick = () => {
        if (onPaymentClick) {
            onPaymentClick(team.id);
        }
    };

    /**
     * Render the license badge for a member
     * @param {Object} member - Member data
     */
    const renderLicenseBadge = (member) => {
        const isValid = member.is_license_valid;
        return (
            <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase ${
                isValid ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'
            }`}>
                {isValid ? <CheckCircle2 className="h-3 w-3" /> : <XCircle className="h-3 w-3" />}
                {member.adh_license || 'SANS'}
            </div>
        );
    };

    /**
     * Render the PPS badge/button for a member
     * @param {Object} member - Member data
     */
    const renderPPSBadge = (member) => {
        // If licensed, PPS is not required
        if (member.is_license_valid) {
            return (
                <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-gray-100 text-gray-500">
                    NON REQUIS
                </div>
            );
        }

        // PPS is valid
        if (member.is_pps_valid) {
            return (
                <button 
                    onClick={() => handlePPSClick(member)}
                    className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors cursor-pointer"
                >
                    <CheckCircle2 className="h-3 w-3" />
                    VALIDE
                </button>
            );
        }

        // PPS is pending
        if (member.pps_status === 'pending') {
            return (
                <button 
                    onClick={() => handlePPSClick(member)}
                    className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-orange-50 text-orange-600 hover:bg-orange-100 transition-colors cursor-pointer"
                >
                    <Clock className="h-3 w-3" />
                    EN ATTENTE
                </button>
            );
        }

        // PPS is required
        return (
            <button 
                onClick={() => handlePPSClick(member)}
                className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-red-50 text-red-600 hover:bg-red-100 transition-colors cursor-pointer"
            >
                <XCircle className="h-3 w-3" />
                REQUIS
            </button>
        );
    };

    /**
     * Render the payment badge/button for a member
     * @param {Object} member - Member data
     */
    const renderPaymentBadge = (member) => {
        if (member.reg_validated) {
            return (
                <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-600">
                    <CheckCircle2 className="h-3 w-3" />
                    PAYÉ
                </div>
            );
        }

        return (
            <button 
                onClick={handlePaymentClick}
                className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-orange-50 text-orange-600 hover:bg-orange-100 transition-colors cursor-pointer"
            >
                <CreditCard className="h-3 w-3" />
                EN ATTENTE
            </button>
        );
    };

    /**
     * Render the presence badge/button for a member
     * @param {Object} member - Member data
     */
    const renderPresenceBadge = (member) => {
        const isLoading = loadingPresence[member.reg_id];
        const isPresent = member.is_present;

        return (
            <button
                onClick={() => handleTogglePresence(member.reg_id)}
                disabled={isLoading}
                className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase transition-colors cursor-pointer ${
                    isPresent 
                        ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' 
                        : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
                }`}
            >
                {isLoading ? (
                    <Loader2 className="h-3 w-3 animate-spin" />
                ) : isPresent ? (
                    <>
                        <CheckCircle2 className="h-3 w-3" />
                        PRÉSENT
                    </>
                ) : (
                    <>
                        <XCircle className="h-3 w-3" />
                        ABSENT
                    </>
                )}
            </button>
        );
    };

    /**
     * Render the dossard badge for a member
     * @param {Object} member - Member data
     */
    const renderDossardBadge = (member) => {
        if (member.reg_dossard) {
            return (
                <div className="inline-flex items-center justify-center px-4 py-2 rounded-xl text-lg font-black bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg">
                    {member.reg_dossard}
                </div>
            );
        }

        return (
            <div className="text-[10px] text-gray-400 font-bold uppercase">
                Non attribué
            </div>
        );
    };

    return (
        <div className="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
            {/* Team Header */}
            {showHeader && (
                <div 
                    className={`bg-gradient-to-r from-blue-600 to-blue-700 p-4 ${isCompact ? 'cursor-pointer' : ''}`}
                    onClick={() => isCompact && setExpanded(!expanded)}
                >
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <Users className="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h3 className="text-lg font-black text-white uppercase italic">
                                    {team.name}
                                </h3>
                                <p className="text-[10px] text-blue-200 font-bold uppercase tracking-widest">
                                    {members.length} membre{members.length > 1 ? 's' : ''}
                                    {team.dossard && (
                                        <span className="ml-2">• Dossard #{team.dossard}</span>
                                    )}
                                </p>
                            </div>
                        </div>
                        {isCompact && (
                            <button className="text-white/80 hover:text-white transition-colors">
                                {expanded ? <ChevronUp className="w-5 h-5" /> : <ChevronDown className="w-5 h-5" />}
                            </button>
                        )}
                    </div>
                </div>
            )}

            {/* Members Table */}
            {(expanded || !isCompact) && (
                <div className="overflow-x-auto">
                    <table className="w-full divide-y divide-blue-50">
                        <thead className="bg-blue-50/50">
                            <tr>
                                <th className="px-4 py-3 text-left text-[10px] font-black text-blue-400 uppercase tracking-[0.15em]">
                                    Participant
                                </th>
                                <th className="px-4 py-3 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.15em]">
                                    Licence
                                </th>
                                <th className="px-4 py-3 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.15em]">
                                    PPS
                                </th>
                                <th className="px-4 py-3 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.15em]">
                                    Paiement
                                </th>
                                <th className="px-4 py-3 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.15em]">
                                    Dossard
                                </th>
                                <th className="px-4 py-3 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.15em]">
                                    Présent
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-blue-50">
                            {members.map((member, idx) => (
                                <tr key={member.participant_id || member.id || idx} className="hover:bg-blue-50/30 transition-colors">
                                    {/* Participant Info */}
                                    <td className="px-4 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center font-black text-blue-600 uppercase italic text-sm">
                                                {member.first_name?.[0]}{member.last_name?.[0]}
                                            </div>
                                            <div>
                                                <p className="text-sm font-black text-blue-900 uppercase italic">
                                                    {member.first_name} {member.last_name}
                                                </p>
                                                {member.is_captain && (
                                                    <span className="text-[9px] text-emerald-600 font-bold uppercase tracking-widest">
                                                        Capitaine
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </td>

                                    {/* License */}
                                    <td className="px-4 py-4 text-center">
                                        {renderLicenseBadge(member)}
                                    </td>

                                    {/* PPS */}
                                    <td className="px-4 py-4 text-center">
                                        {renderPPSBadge(member)}
                                    </td>

                                    {/* Payment */}
                                    <td className="px-4 py-4 text-center">
                                        {renderPaymentBadge(member)}
                                    </td>

                                    {/* Dossard */}
                                    <td className="px-4 py-4 text-center">
                                        {renderDossardBadge(member)}
                                    </td>

                                    {/* Presence */}
                                    <td className="px-4 py-4 text-center">
                                        {renderPresenceBadge(member)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
