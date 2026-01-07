import React from 'react';
import { Calendar, Timer, CheckCircle2, Clock, MapPin } from 'lucide-react';

const RaidProgress = ({ raid }) => {
    const steps = [
        {
            label: 'Inscriptions',
            date: raid.registration_period?.ins_start_date,
            status: raid.is_upcoming ? 'upcoming' : (raid.is_open ? 'active' : 'completed')
        },
        {
            label: 'Fin Inscriptions',
            date: raid.registration_period?.ins_end_date,
            status: raid.is_finished ? 'completed' : (raid.is_open ? 'active' : 'upcoming')
        },
        {
            label: 'Événement',
            date: raid.raid_date_start,
            status: raid.is_finished ? 'completed' : (raid.is_upcoming || raid.is_open ? 'upcoming' : 'active')
        },
    ];

    return (
        <div className="bg-white rounded-3xl border border-blue-100 p-8 shadow-sm space-y-8">
            <div>
                <h3 className="text-sm font-black text-blue-900 mb-6 flex items-center uppercase tracking-widest">
                    <Timer className="h-4 w-4 mr-2 text-blue-500" />
                    Statut de l'événement
                </h3>

                <div className="relative pl-2">
                    <div className="absolute left-[1.125rem] top-2 bottom-2 w-0.5 bg-blue-50" />

                    <div className="space-y-10">
                        {steps.map((step, idx) => (
                            <div key={idx} className="relative pl-10">
                                <div className={`absolute left-0 w-6 h-6 rounded-full flex items-center justify-center border-2 border-white shadow-sm transition-all duration-500 ${step.status === 'completed' ? 'bg-emerald-500 scale-110' :
                                        step.status === 'active' ? 'bg-blue-600 ring-4 ring-blue-50 scale-125 animate-pulse' :
                                            'bg-white border-blue-100'
                                    }`}>
                                    {step.status === 'completed' && <CheckCircle2 className="h-3 w-3 text-white" />}
                                    {step.status === 'active' && <Clock className="h-3 w-3 text-white" />}
                                </div>

                                <div className="transition-all duration-300">
                                    <p className={`text-xs font-black uppercase tracking-wider ${step.status === 'active' ? 'text-blue-600' : 'text-blue-900/40'}`}>
                                        {step.label}
                                    </p>
                                    <p className={`text-sm font-bold mt-0.5 ${step.status === 'active' ? 'text-blue-900' : 'text-blue-700/30'}`}>
                                        {step.date ? new Date(step.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : 'À définir'}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <div className="pt-6 border-t border-blue-50 space-y-4">
                <div className="flex items-center gap-3 p-3 bg-blue-50/50 rounded-2xl border border-blue-100/50">
                    <div className="bg-blue-600 p-2 rounded-xl text-white shadow-md shadow-blue-200">
                        <MapPin className="h-4 w-4" />
                    </div>
                    <div>
                        <p className="text-[10px] font-black text-blue-900/40 uppercase tracking-widest">Lieu du raid</p>
                        <p className="text-sm font-bold text-blue-900">{raid.raid_city}</p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default RaidProgress;
