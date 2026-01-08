import React, { useState } from 'react';
import { Filter, CheckCircle2, ChevronDown, ListFilter, Target, Dna } from 'lucide-react';

const AdvancedFilter = ({ categories = [], onApply }) => {
    const [selectedItems, setSelectedItems] = useState({
        difficulty_names: [],
        type_ids: []
    });

    const [isOpen, setIsOpen] = useState(false);

    const toggleDifficulty = (level) => {
        setSelectedItems(prev => ({
            ...prev,
            difficulty_names: prev.difficulty_names.includes(level)
                ? prev.difficulty_names.filter(i => i !== level)
                : [...prev.difficulty_names, level]
        }));
    };

    const toggleType = (id) => {
        setSelectedItems(prev => ({
            ...prev,
            type_ids: prev.type_ids.includes(id)
                ? prev.type_ids.filter(i => i !== id)
                : [...prev.type_ids, id]
        }));
    };

    const handleClearFilters = () => {
        const cleared = { difficulty_names: [], type_ids: [] };
        setSelectedItems(cleared);
        onApply(cleared);
    };

    const difficulties = ['Facile', 'Moyen', 'Difficile', 'Expert'];

    return (
        <div className="bg-white rounded-3xl border-2 border-blue-50/50 shadow-xl shadow-blue-900/5 mb-8 overflow-hidden transition-all duration-500">
            <div
                className="p-6 cursor-pointer flex items-center justify-between hover:bg-blue-50/30 transition-colors"
                onClick={() => setIsOpen(!isOpen)}
            >
                <div className="flex items-center gap-4">
                    <div className="bg-blue-600 p-2.5 rounded-2xl shadow-lg shadow-blue-200">
                        <Filter className="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h3 className="text-lg font-black text-blue-900 leading-tight">Filtres de recherche</h3>
                        <p className="text-xs font-medium text-blue-700/60 mt-0.5">Personnalisez votre sélection d'épreuves</p>
                    </div>
                </div>
                <div className={`transition-transform duration-300 ${isOpen ? 'rotate-180' : ''}`}>
                    <ChevronDown className="h-5 w-5 text-blue-400" />
                </div>
            </div>

            <div className={`px-6 pb-6 space-y-8 transition-all duration-500 ease-in-out ${isOpen ? 'max-h-[1000px] opacity-100' : 'max-h-0 opacity-0 overflow-hidden'}`}>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    {/* Difficulties */}
                    <div className="space-y-4">
                        <label className="flex items-center text-sm font-black text-blue-900 uppercase tracking-widest">
                            <Target className="h-4 w-4 mr-2 text-blue-500" />
                            Niveaux de difficulté
                        </label>
                        <div className="flex flex-wrap gap-2">
                            {difficulties.map((level) => (
                                <button
                                    key={level}
                                    onClick={() => toggleDifficulty(level)}
                                    className={`px-4 py-2 rounded-xl text-xs font-bold transition-all border-2 ${selectedItems.difficulty_names.includes(level)
                                            ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-200 ring-4 ring-blue-50'
                                            : 'bg-white border-blue-50 text-blue-600 hover:border-blue-200 hover:bg-blue-50/30'
                                        }`}
                                >
                                    {level}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Types */}
                    <div className="space-y-4">
                        <label className="flex items-center text-sm font-black text-blue-900 uppercase tracking-widest">
                            <Dna className="h-4 w-4 mr-2 text-blue-500" />
                            Types d'épreuves
                        </label>
                        <div className="grid grid-cols-2 gap-2">
                            {categories.map((type) => (
                                <button
                                    key={type.type_id}
                                    onClick={() => toggleType(type.type_id)}
                                    className={`text-left px-4 py-3 rounded-xl text-xs font-bold transition-all border-2 flex items-center justify-between ${selectedItems.type_ids.includes(type.type_id)
                                            ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-200 ring-4 ring-blue-50'
                                            : 'bg-white border-blue-50 text-blue-700 hover:border-blue-200 hover:bg-shadow-blue-50'
                                        }`}
                                >
                                    <span className="line-clamp-1">{type.type_name}</span>
                                    {selectedItems.type_ids.includes(type.type_id) && (
                                        <CheckCircle2 className="h-3 w-3 text-white" />
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Action Bar */}
                <div className="flex flex-col sm:flex-row gap-4 pt-4 border-t border-blue-50">
                    <button
                        onClick={handleClearFilters}
                        className="px-6 py-2.5 text-sm font-bold text-blue-600 hover:text-blue-700 transition-colors bg-white border-2 border-blue-50 rounded-xl"
                    >
                        Réinitialiser
                    </button>
                    <button
                        onClick={() => onApply(selectedItems)}
                        className="flex-1 px-8 py-2.5 text-sm font-black text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-xl shadow-blue-200 active:scale-[0.98]"
                    >
                        Appliquer les filtres
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AdvancedFilter;
