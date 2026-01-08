import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, usePage } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import RaidProgress from '@/Components/Raid/RaidProgress';
import { Settings, Plus, MapPin, Calendar, Info, Users, ChevronRight, Trophy } from 'lucide-react';

/**
 * Raid Detail Component
 * Displays raid information and associated courses with premium UI
 */
export default function Index({ raid, courses = [], typeCategories = [], isRaidManager, canEditRaid, canAddRace }) {
    const messages = usePage().props.translations?.messages || {};

    return (
        <AuthenticatedLayout>
            <Head title={raid?.raid_name || 'Détails du Raid'} />

            {/* Premium Header */}
            <div className="relative overflow-hidden">
                {/* Background Image or Gradient */}
                {raid.raid_image ? (
                    <div className="absolute inset-0">
                        <img
                            src={`/storage/${raid.raid_image}`}
                            alt={raid.raid_name}
                            className="w-full h-full object-cover"
                        />
                        <div className="absolute inset-0 bg-emerald-900/70" />
                    </div>
                ) : (
                    <div className="absolute inset-0 bg-emerald-600" />
                )}
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Trophy className="w-64 h-64" />
                </div>
                <div className="relative py-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2">
                            <Link href={route('raids.index')} className="text-emerald-100 hover:text-white flex items-center gap-2 text-xs font-black uppercase tracking-widest transition-colors mb-4">
                                <ChevronRight className="h-4 w-4 rotate-180" />
                                Retour
                            </Link>
                            <h1 className="text-4xl font-black text-white italic tracking-tighter">
                                {raid?.raid_name.toUpperCase()}
                            </h1>
                            <div className="flex items-center gap-4 text-emerald-50 text-sm font-medium">
                                <div className="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full backdrop-blur-sm">
                                    <MapPin className="h-3.5 w-3.5" />
                                    {raid.raid_city}
                                </div>
                                <div className="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full backdrop-blur-sm">
                                    <Trophy className="h-3.5 w-3.5" />
                                    {courses.length} Courses
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            {canEditRaid && (
                                <Link href={route('raids.edit', raid.raid_id)}>
                                    <button className="bg-white text-emerald-700 hover:bg-emerald-50 px-6 py-3 rounded-2xl font-black text-sm transition-all shadow-xl shadow-emerald-900/20 flex items-center gap-2">
                                        <Settings className="h-4 w-4" />
                                        PARAMÈTRES
                                    </button>
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="py-12 bg-gray-50/50 min-h-screen">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
                        {/* Left Column: Progress & Info */}
                        <div className="lg:col-span-4 space-y-8">
                            <RaidProgress raid={raid} />
                        </div>

                        {/* Right Column: Description & Race List */}
                        <div className="lg:col-span-8 space-y-8">
                            {/* Description Section */}
                            <div className="bg-white rounded-3xl border border-blue-100 p-8 shadow-sm space-y-6">
                                <h3 className="text-xs font-black text-blue-900 flex items-center uppercase tracking-widest">
                                    <Info className="h-4 w-4 mr-2 text-blue-500" />
                                    Description
                                </h3>
                                <p className="text-sm text-blue-800/70 leading-relaxed italic">
                                    "{raid.raid_description}"
                                </p>

                                {raid.raid_site_url && (
                                    <div className="pt-4">
                                        <a href={raid.raid_site_url} target="_blank" className="inline-flex items-center text-xs font-bold text-blue-600 hover:text-blue-700 group">
                                            Visiter le site officiel
                                            <ChevronRight className="h-3 w-3 ml-1 group-hover:translate-x-1 transition-transform" />
                                        </a>
                                    </div>
                                )}
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-1">
                                    <h2 className="text-2xl font-black text-blue-900 flex items-center gap-3 italic">
                                        COURSES DISPONIBLES
                                    </h2>
                                    <p className="text-xs font-bold text-blue-700/40 uppercase tracking-widest">
                                        {courses.length} COURSE{courses.length > 1 ? 'S' : ''}
                                    </p>
                                </div>
                                {canAddRace && (
                                    <Link href={route('races.create', { raid_id: raid.raid_id })}>
                                        <button className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-black text-xs transition-all shadow-xl shadow-blue-200 flex items-center gap-2">
                                            <Plus className="h-4 w-4" />
                                            NOUVELLE COURSE
                                        </button>
                                    </Link>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                {courses.map((course) => (
                                    <div key={course.id} className="bg-white rounded-3xl border border-blue-50 overflow-hidden hover:shadow-2xl hover:shadow-blue-900/5 transition-all group flex flex-col border-b-4 border-b-transparent hover:border-b-emerald-500">
                                        <div className="relative h-56 overflow-hidden bg-gradient-to-br from-blue-100 to-blue-50">
                                            {course.image ? (
                                                <img
                                                    src={course.image}
                                                    alt={course.name}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 text-blue-300">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                                                    </svg>
                                                </div>
                                            )}
                                            <div className="absolute top-6 left-6">
                                                <span className={`px-4 py-1.5 rounded-full text-[10px] font-black tracking-widest uppercase border backdrop-blur-md shadow-lg ${course.is_finished ? 'bg-gray-900/80 text-white border-white/20' :
                                                    course.is_open ? 'bg-emerald-500/90 text-white border-emerald-400' :
                                                        'bg-blue-600/90 text-white border-blue-400'
                                                    }`}>
                                                    {course.is_finished ? 'Terminée' : course.is_open ? 'Ouvert' : 'À venir'}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="p-8 flex-1 flex flex-col">
                                            <div className="flex justify-between items-start mb-6">
                                                <div>
                                                    <h3 className="text-xl font-black text-blue-900 group-hover:text-emerald-600 transition-colors uppercase italic leading-none mb-2">
                                                        {course.name}
                                                    </h3>
                                                    <p className="text-[10px] font-black text-blue-800/30 uppercase tracking-[0.2em]">
                                                        ORGANISÉ PAR {course.organizer_name}
                                                    </p>
                                                </div>
                                                <div className="bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-100">
                                                    <span className="text-[10px] font-black text-blue-600 uppercase">
                                                        {course.difficulty}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 mb-8">
                                                <div className="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                                                    <p className="text-[10px] text-blue-900/30 font-black uppercase tracking-widest mb-1">Date</p>
                                                    <p className="text-xs font-bold text-blue-900">
                                                        {new Date(course.start_date).toLocaleDateString()}
                                                    </p>
                                                </div>
                                                <div className="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                                                    <p className="text-[10px] text-blue-900/30 font-black uppercase tracking-widest mb-1">Âge min.</p>
                                                    <p className="text-xs font-bold text-blue-900">{course.min_age} ANS</p>
                                                </div>
                                            </div>

                                            <div className="flex gap-3 mt-auto">
                                                <Link href={route('races.show', course.id)} className="flex-1">
                                                    <button className={`w-full py-4 rounded-2xl font-black text-xs transition-all flex items-center justify-center gap-2 tracking-widest uppercase ${course.is_finished
                                                        ? 'bg-blue-900 text-white hover:bg-black shadow-xl shadow-blue-900/20'
                                                        : 'bg-blue-600 text-white hover:bg-blue-700 shadow-xl shadow-blue-200'
                                                        }`}>
                                                        {course.is_finished ? 'Consulter les résultats' : course.is_open ? "S'inscrire" : 'Plus de détails'}
                                                        <ChevronRight className="h-4 w-4" />
                                                    </button>
                                                </Link>

                                                {course.can_edit && (
                                                    <Link href={route('races.edit', course.id)}>
                                                        <button className="p-4 bg-white border-2 border-blue-50 text-blue-400 hover:text-blue-600 hover:border-blue-100 hover:bg-blue-50 rounded-2xl transition-all">
                                                            <Settings className="h-5 w-5" />
                                                        </button>
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {courses.length === 0 && (
                                <div className="bg-white rounded-[2rem] border-2 border-dashed border-blue-100 p-20 text-center space-y-4">
                                    <div className="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <Trophy className="w-10 h-10 text-blue-200" />
                                    </div>
                                    <h3 className="text-xl font-black text-blue-900 italic">AUCUNE COURSE</h3>
                                    <p className="text-sm text-blue-700/40 max-w-xs mx-auto font-bold uppercase tracking-widest">
                                        Aucune course n'est disponible pour ce raid pour le moment.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
