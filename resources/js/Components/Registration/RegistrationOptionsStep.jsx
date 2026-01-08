import React from 'react';
import { User, Users, UserMinus } from 'lucide-react';

/**
 * Registration options step - choose whether user participates in team or not.
 * 
 * @param {object} registrationData - Current registration data
 * @param {function} updateRegistrationData - Update handler
 * @param {function} onNext - Handler for proceeding to next step
 * @param {object} translations - Translation strings
 */
export default function RegistrationOptionsStep({ registrationData, updateRegistrationData, onNext, translations }) {
    const handleSelect = (participating) => {
        updateRegistrationData({ isCreatorParticipating: participating });
        onNext();
    };

    return (
        <div className="space-y-6">
            <div className="text-center mb-8">
                <h3 className="text-xl font-black text-slate-800 uppercase tracking-tight">
                    Comment souhaitez-vous vous inscrire ?
                </h3>
                <p className="text-slate-500 mt-2">
                    Choisissez votre mode de participation à cette course
                </p>
            </div>

            <div className="grid gap-4">
                {/* Option 1: User participates */}
                <button
                    onClick={() => handleSelect(true)}
                    className={`
                        relative p-6 rounded-2xl border-2 text-left transition-all
                        hover:border-blue-500 hover:shadow-lg hover:shadow-blue-100
                        ${registrationData.isCreatorParticipating
                            ? 'border-blue-500 bg-blue-50'
                            : 'border-slate-200 bg-white'
                        }
                    `}
                >
                    <div className="flex items-start gap-4">
                        <div className={`
                            p-3 rounded-xl
                            ${registrationData.isCreatorParticipating
                                ? 'bg-blue-500 text-white'
                                : 'bg-slate-100 text-slate-400'
                            }
                        `}>
                            <User className="h-6 w-6" />
                        </div>
                        <div>
                            <h4 className="font-bold text-slate-800 text-lg">
                                Je participe avec mon équipe
                            </h4>
                            <p className="text-slate-500 text-sm mt-1">
                                Vous serez inscrit en tant que membre de l'équipe et participerez à la course.
                            </p>
                        </div>
                    </div>
                    {registrationData.isCreatorParticipating && (
                        <div className="absolute top-4 right-4 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                            </svg>
                        </div>
                    )}
                </button>

                {/* Option 2: User doesn't participate */}
                <button
                    onClick={() => handleSelect(false)}
                    className={`
                        relative p-6 rounded-2xl border-2 text-left transition-all
                        hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-100
                        ${!registrationData.isCreatorParticipating
                            ? 'border-emerald-500 bg-emerald-50'
                            : 'border-slate-200 bg-white'
                        }
                    `}
                >
                    <div className="flex items-start gap-4">
                        <div className={`
                            p-3 rounded-xl
                            ${!registrationData.isCreatorParticipating
                                ? 'bg-emerald-500 text-white'
                                : 'bg-slate-100 text-slate-400'
                            }
                        `}>
                            <Users className="h-6 w-6" />
                        </div>
                        <div>
                            <h4 className="font-bold text-slate-800 text-lg">
                                J'inscris une équipe sans moi
                            </h4>
                            <p className="text-slate-500 text-sm mt-1">
                                Vous inscrivez une équipe mais vous ne participerez pas à la course.
                            </p>
                        </div>
                    </div>
                    {!registrationData.isCreatorParticipating && (
                        <div className="absolute top-4 right-4 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                            <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                            </svg>
                        </div>
                    )}
                </button>
            </div>
        </div>
    );
}
