import { usePage } from '@inertiajs/react';

/**
 * Component to display user roles with badges
 */
export default function UserRolesDisplay({ className = '' }) {
    const { auth, translations } = usePage().props;
    const messages = translations?.messages || {};
    const user = auth?.user;
    const roles = user?.roles || [];

    /**
     * Get role display configuration (color, icon, label)
     */
    const getRoleConfig = (role) => {
        const configs = {
            'admin': {
                color: 'bg-red-100 text-red-800 border-red-200',
                label: messages.role_admin || 'Administrateur',
            },
            'adherent': {
                color: 'bg-green-100 text-green-800 border-green-200',
                label: messages.role_adherent || 'Adherent',
            },
            'responsable-club': {
                color: 'bg-indigo-100 text-indigo-800 border-indigo-200',
                label: messages.role_responsable_club || 'Responsable de club',
            },
            'club-manager': {
                color: 'bg-indigo-100 text-indigo-800 border-indigo-200',
                label: messages.role_responsable_club || 'Responsable de club',
            },
            'gestionnaire-raid': {
                color: 'bg-purple-100 text-purple-800 border-purple-200',
                label: messages.role_gestionnaire_raid || 'Gestionnaire Raid',
            },
            'responsable-course': {
                color: 'bg-orange-100 text-orange-800 border-orange-200',
                label: messages.role_responsable_course || 'Responsable Course',
            },
            'user': {
                color: 'bg-gray-100 text-gray-800 border-gray-200',
                label: messages.role_user || 'Utilisateur',
            },
            'guest': {
                color: 'bg-gray-50 text-gray-600 border-gray-200',
                label: messages.role_guest || 'Visiteur',
            },
        };

        return configs[role] || {
            color: 'bg-gray-100 text-gray-800 border-gray-200',
            label: role,
        };
    };

    if (!roles.length) {
        return (
            <div className={className}>
                <p className="text-sm text-gray-500 italic">
                    {messages.no_roles_assigned || 'Aucun rôle assigné'}
                </p>
            </div>
        );
    }

    return (
        <div className={className}>
            <div className="flex flex-wrap gap-2">
                {roles.map((role) => {
                    const roleName = typeof role === 'object' ? role.name : role;
                    const config = getRoleConfig(roleName);
                    return (
                        <span
                            key={roleName}
                            className={`inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium border ${config.color}`}
                        >
                            {config.label}
                        </span>
                    );
                })}
            </div>

            {/* Licence/PPS Info */}
            {user?.licence_info && (
                <div className="mt-4 space-y-2">
                    {user.licence_info.licence_number && (
                        <div className="flex items-center gap-2 text-sm">
                            <span className={`w-2 h-2 rounded-full ${user.licence_info.has_valid_licence ? 'bg-green-500' : 'bg-red-500'}`}></span>
                            <span className="text-gray-600">
                                {messages.licence_number || 'Licence'}: {user.licence_info.licence_number}
                            </span>
                            {user.licence_info.licence_expiry_date && (
                                <span className="text-gray-400 text-xs">
                                    ({messages.valid_until || 'Valide jusqu\'au'} {new Date(user.licence_info.licence_expiry_date).toLocaleDateString()})
                                </span>
                            )}
                        </div>
                    )}
                    {user.licence_info.pps_code && (
                        <div className="flex items-center gap-2 text-sm">
                            <span className={`w-2 h-2 rounded-full ${user.licence_info.has_valid_pps ? 'bg-green-500' : 'bg-red-500'}`}></span>
                            <span className="text-gray-600">
                                {messages.pps_code || 'Code PPS'}: {user.licence_info.pps_code}
                            </span>
                            {user.licence_info.pps_expiry_date && (
                                <span className="text-gray-400 text-xs">
                                    ({messages.valid_until || 'Valide jusqu\'au'} {new Date(user.licence_info.pps_expiry_date).toLocaleDateString()})
                                </span>
                            )}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
