import React, { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import { X, ChevronRight, ChevronLeft, Check, Users, UserPlus, AlertCircle } from 'lucide-react';

import StepIndicator from './StepIndicator';
import AuthRequiredStep from './AuthRequiredStep';
import LicenceRequiredStep from './LicenceRequiredStep';
import RegistrationOptionsStep from './RegistrationOptionsStep';
import TeamSelectionStep from './TeamSelectionStep';
import TeamCreateStep from './TeamCreateStep';
import RegistrationConfirmStep from './RegistrationConfirmStep';

/**
 * Multi-step registration modal for race enrollment.
 * Handles authentication, licence verification, team selection, and registration.
 * 
 * @param {boolean} isOpen - Whether modal is visible
 * @param {function} onClose - Close modal handler
 * @param {object} race - Race data object
 */
export default function RegistrationModal({ isOpen, onClose, race, editMode = false, initialData = null }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const translations = usePage().props.translations?.messages || {};

    // Step management
    const [currentStep, setCurrentStep] = useState(0);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // Eligibility data
    const [eligibility, setEligibility] = useState(null);

    // Registration data
    const [registrationData, setRegistrationData] = useState({
        isCreatorParticipating: true,
        selectedTeam: null,
        isTemporaryTeam: false,
        temporaryTeamMembers: [],
    });

    // Initialize data if in edit mode
    useEffect(() => {
        if (isOpen && editMode && initialData) {
            setRegistrationData({
                isCreatorParticipating: initialData.is_creator_participating,
                selectedTeam: initialData.team,
                isTemporaryTeam: initialData.is_temporary_team,
                temporaryTeamMembers: (initialData.team_members || []).map(m => ({
                    ...m,
                    name: m.name || (m.email ? m.email.split('@')[0] : '')
                })),
            });
            // Skip directly to TeamCreateStep if it's a temporary team Edit
            if (initialData.is_temporary_team) {
                // We'll calculate the step index in getSteps
            }
        }
    }, [isOpen, editMode, initialData]);

    // Define steps based on user state
    const getSteps = () => {
        const steps = [];

        if (editMode && initialData) {
            // Edit Mode Steps
            if (initialData.is_temporary_team) {
                steps.push({ id: 'create', label: 'Modifier', component: TeamCreateStep });
                steps.push({ id: 'confirm', label: 'Confirmation', component: RegistrationConfirmStep });
            } else {
                // Permanent team edit NOT supported here (usually handled via team management)
                steps.push({ id: 'options', label: 'Options', component: RegistrationOptionsStep });
            }
            return steps;
        }

        // Standard Registration Steps
        // Step 1: Auth (only if not logged in)
        if (!user) {
            steps.push({ id: 'auth', label: 'Connexion', component: AuthRequiredStep, props: { raceId: race?.id } });
        }

        // Step 2: Licence (only if no valid credentials)
        if (user && eligibility && !eligibility.has_valid_credentials) {
            steps.push({ id: 'licence', label: 'Licence', component: LicenceRequiredStep });
        }

        // Step 3: Registration options (user in team or not)
        steps.push({ id: 'options', label: 'Options', component: RegistrationOptionsStep });

        // Step 4: Team selection
        steps.push({ id: 'team', label: 'Équipe', component: TeamSelectionStep });

        // Step 5: Team creation (if creating new team)
        if (registrationData.isTemporaryTeam) {
            steps.push({ id: 'create', label: 'Créer', component: TeamCreateStep });
        }

        // Step 6: Confirmation
        steps.push({ id: 'confirm', label: 'Confirmation', component: RegistrationConfirmStep });

        return steps;
    };

    const steps = getSteps();

    // Check eligibility on mount
    useEffect(() => {
        if (isOpen && user && race) {
            checkEligibility();
        }
    }, [isOpen, user, race]);

    const checkEligibility = async () => {
        setLoading(true);
        try {
            const response = await fetch(route('race.registration.check', race.id), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            setEligibility(data);
        } catch (err) {
            setError('Erreur lors de la vérification de l\'éligibilité');
        } finally {
            setLoading(false);
        }
    };

    const handleNext = () => {
        if (currentStep < steps.length - 1) {
            setCurrentStep(prev => prev + 1);
        }
    };

    const handleBack = () => {
        if (currentStep > 0) {
            setCurrentStep(prev => prev - 1);
        }
    };

    const handleSubmit = async () => {
        setLoading(true);
        setError(null);

        // Get CSRF token from cookie
        const getCsrfToken = () => {
            const name = 'XSRF-TOKEN=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const cookies = decodedCookie.split(';');
            for (let cookie of cookies) {
                cookie = cookie.trim();
                if (cookie.indexOf(name) === 0) {
                    return cookie.substring(name.length);
                }
            }
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        };

        try {
            const url = editMode
                ? route('race.registration.update', initialData.id)
                : route('race.register', race.id);
            const method = editMode ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(editMode ? {
                    temporary_team_data: registrationData.temporaryTeamMembers,
                    is_creator_participating: registrationData.isCreatorParticipating,
                } : {
                    team_id: registrationData.selectedTeam?.id || null,
                    is_temporary_team: registrationData.isTemporaryTeam,
                    temporary_team_data: registrationData.temporaryTeamMembers,
                    is_creator_participating: registrationData.isCreatorParticipating,
                }),
            });

            const data = await response.json();

            if (data.success) {
                onClose();
                router.reload();
            } else {
                setError(data.message || 'Erreur lors de l\'inscription');
            }
        } catch (err) {
            setError('Erreur lors de l\'inscription');
        } finally {
            setLoading(false);
        }
    };

    const updateRegistrationData = (updates) => {
        setRegistrationData(prev => ({ ...prev, ...updates }));
    };

    if (!isOpen) return null;

    const CurrentStepComponent = steps[currentStep]?.component;
    const isLastStep = currentStep === steps.length - 1;
    const isFirstStep = currentStep === 0;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-screen items-center justify-center p-4">
                {/* Backdrop */}
                <div
                    className="fixed inset-0 bg-blue-900/60 backdrop-blur-sm transition-opacity"
                    onClick={onClose}
                />

                {/* Modal */}
                <div className="relative w-full max-w-2xl transform overflow-hidden rounded-[2.5rem] bg-white shadow-2xl transition-all">
                    {/* Header */}
                    <div className="relative bg-gradient-to-r from-blue-900 to-blue-800 px-8 py-6">
                        <button
                            onClick={onClose}
                            className="absolute right-6 top-6 text-white/60 hover:text-white transition-colors"
                        >
                            <X className="h-6 w-6" />
                        </button>

                        <h2 className="text-2xl font-black text-white italic uppercase tracking-tight">
                            {translations.register_for_race || "S'inscrire à la course"}
                        </h2>
                        <p className="text-blue-100/60 text-sm font-medium mt-1">
                            {race?.title || race?.race_name}
                        </p>

                        {/* Step indicator */}
                        <div className="mt-6">
                            <StepIndicator
                                steps={steps}
                                currentStep={currentStep}
                            />
                        </div>
                    </div>

                    {/* Error message */}
                    {error && (
                        <div className="mx-8 mt-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
                            <AlertCircle className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
                            <p className="text-sm text-red-700">{error}</p>
                        </div>
                    )}

                    {/* Content */}
                    <div className="p-8">
                        {loading ? (
                            <div className="flex items-center justify-center py-16">
                                <div className="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600" />
                            </div>
                        ) : CurrentStepComponent ? (
                            <CurrentStepComponent
                                race={race}
                                user={user}
                                eligibility={eligibility}
                                registrationData={registrationData}
                                updateRegistrationData={updateRegistrationData}
                                onNext={handleNext}
                                onBack={handleBack}
                                translations={translations}
                            />
                        ) : null}
                    </div>

                    {/* Footer */}
                    <div className="px-8 pb-8 flex items-center justify-between gap-4">
                        {!isFirstStep ? (
                            <button
                                onClick={handleBack}
                                disabled={loading}
                                className="flex items-center gap-2 px-6 py-3 text-blue-600 font-bold text-sm uppercase tracking-widest hover:bg-blue-50 rounded-2xl transition-colors disabled:opacity-50"
                            >
                                <ChevronLeft className="h-4 w-4" />
                                Retour
                            </button>
                        ) : (
                            <div />
                        )}

                        {isLastStep ? (
                            <button
                                onClick={handleSubmit}
                                disabled={loading}
                                className="flex items-center gap-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-[0.2em] rounded-2xl shadow-lg shadow-emerald-200 transition-all disabled:opacity-50"
                            >
                                <Check className="h-4 w-4" />
                                Confirmer l'inscription
                            </button>
                        ) : (
                            <button
                                onClick={handleNext}
                                disabled={loading}
                                className="flex items-center gap-2 px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs uppercase tracking-[0.2em] rounded-2xl shadow-lg shadow-blue-200 transition-all disabled:opacity-50"
                            >
                                Suivant
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
