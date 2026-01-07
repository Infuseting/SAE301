import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import SelectResponsableModal from '@/Components/SelectResponsableModal';
import { Trophy, Plus } from 'lucide-react';

export default function NewRace({ auth, users = [], types = [], ageCategories = [], raid_id = null, raid = null }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedResponsable, setSelectedResponsable] = useState(null);
    const [selectedAgeCategories, setSelectedAgeCategories] = useState([]);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        responsableId: '',
        startDate: '',
        startTime: '',
        duration: '',
        endDate: '',
        endTime: '',
        minParticipants: '',
        maxParticipants: '',
        maxPerTeam: '1',
        minTeams: '1',
        maxTeams: '1',
        priceMajor: '',
        priceMinor: '',
        priceMajorAdherent: '',
        priceMinorAdherent: '',
        difficulty: '',
        type: types.length > 0 ? types[0].id : '',
        mealPrice: '',
        image: null,
        raid_id: raid_id || '',
    });

    // Date validation state
    const [dateErrors, setDateErrors] = useState({});

    // Get raid date boundaries if raid exists
    const getRaidDateBoundaries = () => {
        if (!raid) {
            return {
                minDate: new Date().toISOString().split('T')[0],
                maxDate: null,
            };
        }

        // Parse raid dates
        const raidStartDate = raid.raid_date_start ? new Date(raid.raid_date_start).toISOString().split('T')[0] : null;
        const raidEndDate = raid.raid_date_end ? new Date(raid.raid_date_end).toISOString().split('T')[0] : null;

        return {
            minDate: raidStartDate || new Date().toISOString().split('T')[0],
            maxDate: raidEndDate,
        };
    };

    // Validate dates on change
    const validateDates = (fieldName, value) => {
        const newErrors = { ...dateErrors };
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const startDate = fieldName === 'startDate' ? value : data.startDate;
        const endDate = fieldName === 'endDate' ? value : data.endDate;
        const startTime = fieldName === 'startTime' ? value : data.startTime;
        const endTime = fieldName === 'endTime' ? value : data.endTime;
        const duration = fieldName === 'duration' ? value : data.duration;

        const { minDate, maxDate } = getRaidDateBoundaries();

        // Check start date is not in the past
        if (startDate) {
            const start = new Date(startDate);
            if (start < today) {
                newErrors.startDate = 'La date de début ne peut pas être dans le passé';
            } else if (minDate && startDate < minDate) {
                // Check if raid is associated and start date is before raid start
                newErrors.startDate = `La date de début doit être après le ${new Date(minDate).toLocaleDateString('fr-FR')} (début du raid)`;
            } else if (maxDate && startDate > maxDate) {
                // Check if raid is associated and start date is after raid end
                newErrors.startDate = `La date de début doit être avant le ${new Date(maxDate).toLocaleDateString('fr-FR')} (fin du raid)`;
            } else {
                delete newErrors.startDate;
            }
        }

        // Check end date is after or equal to start date
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            if (end < start) {
                newErrors.endDate = 'La date de fin doit être égale ou postérieure à la date de début';
            } else if (maxDate && endDate > maxDate) {
                // Check if raid is associated and end date is after raid end
                newErrors.endDate = `La date de fin doit être avant le ${new Date(maxDate).toLocaleDateString('fr-FR')} (fin du raid)`;
            } else {
                delete newErrors.endDate;
            }
        }

        // Check end time is after start time + duration if same day
        if (startDate && endDate && startDate === endDate && startTime && endTime) {
            const startMinutes = parseInt(startTime.split(':')[0]) * 60 + parseInt(startTime.split(':')[1]);
            const endMinutes = parseInt(endTime.split(':')[0]) * 60 + parseInt(endTime.split(':')[1]);
            let durationMinutes = 0;
            
            if (duration && duration.includes(':')) {
                const [h, m] = duration.split(':');
                durationMinutes = parseInt(h) * 60 + parseInt(m);
            }

            if (endMinutes < startMinutes + durationMinutes) {
                newErrors.endTime = 'L\'heure de fin doit être après l\'heure de début + durée de la course';
            } else {
                delete newErrors.endTime;
            }
        }

        setDateErrors(newErrors);
    };

    const handleDateChange = (e) => {
        const { name, value } = e.target;
        setData(name, value);
        validateDates(name, value);
    };

    const isCompetitive = types.find(t => t.id === data.type)?.name.toLowerCase() === 'compétitif' ||
        types.find(t => t.id === data.type)?.name.toLowerCase() === 'competitif';

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setData(name, value);
    };

    const [imagePreview, setImagePreview] = useState(null);

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('image', file);
            setImagePreview(URL.createObjectURL(file));
        }
    };


    /**
     * Handle responsable selection from modal
     * @param {object} user - The selected user object
     */
    const handleSelectResponsable = (user) => {
        setSelectedResponsable(user);
        setData('responsableId', user.id);
    };

    /**
     * Toggle age category selection
     * @param {number} categoryId - The age category ID
     */
    const toggleAgeCategory = (categoryId) => {
        setSelectedAgeCategories(prev => 
            prev.includes(categoryId)
                ? prev.filter(id => id !== categoryId)
                : [...prev, categoryId]
        );
    };

    /**
     * Get filterable age categories based on race type
     * For competitive races, exclude categories with age_max < 18
     */
    const getFilteredAgeCategories = () => {
        return ageCategories.filter(cat => {
            if (isCompetitive) {
                // For competitive races, only show categories for adults (18+)
                // Keep categories where age_min >= 18
                return cat.age_min >= 18;
            }
            return true;
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('races.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
        >
            <Head title="Créer une Course" />

            {/* Header / Hero Section */}
            <div className="bg-blue-900 py-16 relative overflow-hidden border-b-8 border-emerald-500">
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Trophy className="w-96 h-96 -rotate-12" />
                </div>

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="space-y-4">
                        <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.2em] shadow-lg bg-emerald-500 text-white">
                            <Plus className="h-4 w-4" />
                            Nouvelle Épreuve
                        </span>

                        <h1 className="text-5xl font-black text-white italic tracking-tighter leading-none uppercase">
                            Créer une Course
                        </h1>
                        {raid && (
                            <p className="text-blue-100/80 text-sm font-bold uppercase tracking-widest">
                                Pour le raid : <span className="text-emerald-400">{raid.raid_name}</span>
                            </p>
                        )}
                    </div>
                </div>
            </div>

            <div className="py-12 bg-gray-50">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-lg sm:rounded-2xl">
                        <form onSubmit={handleSubmit} className="p-8">
                            {/* Affichage des erreurs */}
                            {Object.keys(errors).length > 0 && (
                                <div className="mb-6 p-4 bg-red-50 border-2 border-red-300 rounded-xl">
                                    <h3 className="text-sm font-black text-red-800 mb-2 uppercase">⚠️ Erreurs de validation</h3>
                                    <ul className="text-sm text-red-700 space-y-1">
                                        {Object.entries(errors).map(([field, message]) => (
                                            <li key={field} className="font-medium">• {message}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-8">
                                {/* Colonne Gauche - Éléments Obligatoires */}
                                <div>
                                    {/* Titre Section */}
                                    <h3 className="text-lg font-black text-blue-900 mb-6 uppercase tracking-wider">📋 Informations Principales</h3>

                                    {/* Nom de la course */}
                                    <div className="mb-6">
                                        <label htmlFor="title" className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">🎯 Nom de la course</label>
                                        <input
                                            type="text"
                                            id="title"
                                            name="title"
                                            value={data.title}
                                            onChange={handleInputChange}
                                            placeholder="Ex: La Grande Aventure 2026"
                                            className="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm"
                                            required
                                        />
                                    </div>

                                    {/* Description de la course */}
                                    <div className="mb-6">
                                        <label htmlFor="description" className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">📝 Description</label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            value={data.description}
                                            onChange={handleInputChange}
                                            placeholder="Décrivez la course (parcours, règles, etc.)"
                                            rows="4"
                                            className="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm"
                                        />
                                    </div>

                                    {/* Sélection du responsable */}
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Responsable de la course
                                        </label>
                                        {selectedResponsable ? (
                                            <div className="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                                <div className="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                    <span className="text-green-600 font-semibold text-sm">
                                                        {selectedResponsable.name.charAt(0).toUpperCase()}
                                                    </span>
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium text-gray-900">{selectedResponsable.name}</p>
                                                    <p className="text-xs text-gray-500">{selectedResponsable.email}</p>
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
                                                Sélectionner un responsable
                                            </button>
                                        )}
                                    </div>

                                    {/* Affichage des dates du raid */}
                                    {raid && raid.raid_date_start && raid.raid_date_end && (
                                        <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <h4 className="text-sm font-semibold text-blue-900 mb-3 flex items-center gap-2">
                                                <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Dates du raid
                                            </h4>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p className="text-xs text-blue-600 font-medium mb-1">Début</p>
                                                    <p className="text-sm font-semibold text-blue-900">
                                                        {new Date(raid.raid_date_start).toLocaleDateString('fr-FR', { 
                                                            weekday: 'long', 
                                                            year: 'numeric', 
                                                            month: 'long', 
                                                            day: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit'
                                                        })}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-xs text-blue-600 font-medium mb-1">Fin</p>
                                                    <p className="text-sm font-semibold text-blue-900">
                                                        {new Date(raid.raid_date_end).toLocaleDateString('fr-FR', { 
                                                            weekday: 'long', 
                                                            year: 'numeric', 
                                                            month: 'long', 
                                                            day: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit'
                                                        })}
                                                    </p>
                                                </div>
                                            </div>
                                            <p className="text-xs text-blue-700 mt-3 italic">
                                                💡 Les dates de votre course doivent être comprises dans cette plage
                                            </p>
                                        </div>
                                    )}

                                    {/* Date et heure de départ */}
                                    <div className="mb-6">
                                        <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">📅 Date et heure de départ</label>
                                        <div className="grid grid-cols-3 gap-2">
                                            <input
                                                type="date"
                                                name="startDate"
                                                value={data.startDate}
                                                onChange={handleDateChange}
                                                min={getRaidDateBoundaries().minDate}
                                                max={getRaidDateBoundaries().maxDate}
                                                className={`col-span-2 px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm ${dateErrors.startDate ? 'border-red-500' : ''}`}
                                                required
                                            />
                                            <input
                                                type="time"
                                                name="startTime"
                                                value={data.startTime}
                                                onChange={handleDateChange}
                                                className="px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                required
                                            />
                                        </div>
                                        {dateErrors.startDate && <p className="mt-1 text-xs text-red-600 font-medium">{dateErrors.startDate}</p>}
                                    </div>

                                    {/* Durée */}
                                    <div className="mb-6">
                                        <label htmlFor="duration" className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">⏱️ Durée</label>
                                        <input
                                            type="text"
                                            id="duration"
                                            name="duration"
                                            value={data.duration}
                                            onChange={handleDateChange}
                                            placeholder="2:30"
                                            pattern="\d+:\d{2}"
                                            className="w-full px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Format: h:mm (ex: 2:30)</p>
                                    </div>

                                    {/* Date et heure de fin */}
                                    <div className="mb-6">
                                        <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">📅 Date et heure de fin</label>
                                        <div className="grid grid-cols-3 gap-2">
                                            <input
                                                type="date"
                                                name="endDate"
                                                value={data.endDate}
                                                onChange={handleDateChange}
                                                min={data.startDate || getRaidDateBoundaries().minDate}
                                                max={getRaidDateBoundaries().maxDate}
                                                className={`col-span-2 px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm ${dateErrors.endDate ? 'border-red-500' : ''}`}
                                            />
                                            <input
                                                type="time"
                                                name="endTime"
                                                value={data.endTime}
                                                onChange={handleDateChange}
                                                className={`px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm ${dateErrors.endTime ? 'border-red-500' : ''}`}
                                            />
                                        </div>
                                        {dateErrors.endDate && <p className="mt-1 text-xs text-red-600 font-medium">{dateErrors.endDate}</p>}
                                        {dateErrors.endTime && <p className="mt-1 text-xs text-red-600 font-medium">{dateErrors.endTime}</p>}
                                    </div>

                                    {/* Nombre de participants */}
                                    <div className="mb-6">
                                        <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">👥 Participants</label>
                                        <div className="grid grid-cols-3 gap-2 mb-2">
                                            <input
                                                type="number"
                                                name="minParticipants"
                                                value={data.minParticipants}
                                                onChange={handleInputChange}
                                                placeholder="Min"
                                                className="px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            />
                                            <input
                                                type="number"
                                                name="maxParticipants"
                                                value={data.maxParticipants}
                                                onChange={handleInputChange}
                                                placeholder="Max"
                                                className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                            <input
                                                type="number"
                                                name="maxParticipants"
                                                value={data.maxParticipants}
                                                onChange={handleInputChange}
                                                placeholder="Max"
                                                className="px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            />
                                            <input
                                                type="number"
                                                name="maxPerTeam"
                                                value={data.maxPerTeam}
                                                onChange={handleInputChange}
                                                placeholder="Par équipe"
                                                className="px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            />
                                        </div>
                                    </div>

                                    <div className="mb-6">
                                        <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">🏔️ Difficulté</label>
                                        <input
                                            type="text"
                                            name="difficulty"
                                            value={data.difficulty}
                                            onChange={handleInputChange}
                                            placeholder="Ex: Facile, Expert, Technique..."
                                            className="w-full px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            required
                                        />
                                        {errors.difficulty && <p className="mt-1 text-xs text-red-600 font-medium">{errors.difficulty}</p>}
                                    </div>

                                    <div className="mb-6">
                                        <label className="block text-xs font-black text-blue-900 mb-3 uppercase tracking-wider">🏷️ Type</label>
                                        <div className="space-y-2">
                                            {types.length > 0 ? (
                                                types.map((type) => (
                                                    <label key={type.id} className="flex items-center cursor-pointer">
                                                        <input
                                                            type="radio"
                                                            name="type"
                                                            value={type.id}
                                                            checked={data.type === type.id}
                                                            onChange={(e) => setData('type', parseInt(e.target.value))}
                                                            className="w-4 h-4 text-blue-600"
                                                        />
                                                        <span className="ml-2 text-gray-700 capitalize font-medium text-sm">{type.name}</span>
                                                    </label>
                                                ))
                                            ) : (
                                                <p className="text-sm text-gray-500 italic">Aucun type disponible</p>
                                            )}
                                        </div>
                                        {isCompetitive && (
                                            <div className="mt-4 p-3 bg-blue-50 border-2 border-blue-200 rounded-lg flex items-start gap-2">
                                                <svg className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p className="text-xs text-blue-700">
                                                    <strong>Mode Compétitif actitvé :</strong> Les courses compétitives sont réservées aux adultes (18 ans et plus). Les âges minimums seront ajustés automatiquement.
                                                </p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Catégories d'âges */}
                                    <div className="mb-6">
                                        <label className="block text-xs font-black text-blue-900 mb-3 uppercase tracking-wider">🎂 Catégories d'Âges</label>
                                        {isCompetitive && (
                                            <p className="text-xs text-blue-700 mb-3 p-2 bg-blue-50 border border-blue-200 rounded-lg">
                                                ⚠️ Mode compétitif : Seules les catégories pour adultes (18+) sont disponibles
                                            </p>
                                        )}
                                        <div className="space-y-2">
                                            {getFilteredAgeCategories().length > 0 ? (
                                                getFilteredAgeCategories().map((category) => (
                                                    <label key={category.id} className="flex items-center cursor-pointer p-2 hover:bg-gray-50 rounded-lg transition">
                                                        <input
                                                            type="checkbox"
                                                            checked={selectedAgeCategories.includes(category.id)}
                                                            onChange={() => toggleAgeCategory(category.id)}
                                                            className="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500"
                                                        />
                                                        <span className="ml-2 text-gray-700 font-medium text-sm flex-1">
                                                            {category.nom}
                                                        </span>
                                                        <span className="text-xs text-gray-500">
                                                            {category.age_max ? `${category.age_min}-${category.age_max} ans` : `${category.age_min}+ ans`}
                                                        </span>
                                                    </label>
                                                ))
                                            ) : (
                                                <p className="text-sm text-gray-500 italic">Aucune catégorie disponible</p>
                                            )}
                                        </div>
                                        {selectedAgeCategories.length === 0 && (
                                            <p className="mt-2 text-xs text-red-600 font-medium">⚠️ Veuillez sélectionner au moins une catégorie d'âge</p>
                                        )}
                                    </div>
                                </div>

                                {/* Colonne Droite */}
                                <div>
                                    {/* Tarifs */}
                                    <div className="mb-8">
                                        <h3 className="text-lg font-black text-blue-900 mb-4 uppercase tracking-wider">💰 Tarifs d'Inscription</h3>
                                    
                                    {/* Prix sur une ligne : Mineur | Majeur | Adhérent */}
                                    <div className={`grid ${isCompetitive ? 'grid-cols-2' : 'grid-cols-3'} gap-4`}>
                                        {/* Colonne Mineurs - cachée si compétitif */}
                                        {!isCompetitive && (
                                            <div>
                                                <label className="block text-xs font-medium text-gray-700 mb-2">Mineurs</label>
                                                <label className="block text-xs text-gray-600 mb-2">Standard</label>
                                                <div className="flex items-center">
                                                    <input
                                                        type="number"
                                                        name="priceMinor"
                                                        value={data.priceMinor}
                                                        onChange={handleInputChange}
                                                        placeholder="0.00"
                                                        step="0.01"
                                                        min="0"
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                        required
                                                    />
                                                    <span className="ml-2 text-gray-500 text-sm">€</span>
                                                </div>
                                                {errors.priceMinor && <p className="mt-1 text-xs text-red-600">{errors.priceMinor}</p>}
                                            </div>
                                        )}

                                        {/* Colonne Majeurs */}
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 mb-2">Majeurs</label>
                                            <label className="block text-xs text-gray-600 mb-2">Standard</label>
                                            <div className="flex items-center">
                                                <input
                                                    type="number"
                                                    name="priceMajor"
                                                    value={data.priceMajor}
                                                    onChange={handleInputChange}
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    min="0"
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                    required
                                                />
                                                <span className="ml-2 text-gray-500 text-sm">€</span>
                                            </div>
                                            {errors.priceMajor && <p className="mt-1 text-xs text-red-600">{errors.priceMajor}</p>}
                                        </div>

                                        {/* Colonne Adhérents */}
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 mb-2">Adhérents</label>
                                            <label className="block text-xs text-gray-600 mb-2">Licenciés club</label>
                                            <div className="flex items-center">
                                                <input
                                                    type="number"
                                                    name="priceMajorAdherent"
                                                    value={data.priceMajorAdherent}
                                                    onChange={handleInputChange}
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    min="0"
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                />
                                                <span className="ml-2 text-gray-500 text-sm">€</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Note informative */}
                                    {isCompetitive ? (
                                        <div className="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                                            <svg className="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p className="text-xs text-amber-700">
                                                <strong>Mode compétitif :</strong> Les tarifs pour les mineurs ne sont pas disponibles car les courses compétitives sont réservées aux adultes (18 ans et plus).
                                            </p>
                                        </div>
                                    ) : (
                                        <p className="mt-3 text-xs text-gray-500 italic">
                                            💡 Le tarif adhérent doit être inférieur ou égal aux autres tarifs
                                        </p>
                                    )}

                                </div>

                                {/* Nombre d'équipes */}
                                <div className="mb-8">
                                    <h3 className="text-lg font-black text-blue-900 mb-4 uppercase tracking-wider">👥 Configuration</h3>
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">Nombre d'équipes</label>
                                            <div className="flex gap-2">
                                                <input
                                                    type="number"
                                                    name="minTeams"
                                                    value={data.minTeams}
                                                    onChange={handleInputChange}
                                                    placeholder="Min"
                                                    className="flex-1 px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                />
                                                <input
                                                    type="number"
                                                    name="maxTeams"
                                                    value={data.maxTeams}
                                                    onChange={handleInputChange}
                                                    placeholder="Max"
                                                    className="flex-1 px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">Max par équipe</label>
                                            <input
                                                type="number"
                                                name="maxPerTeam"
                                                value={data.maxPerTeam}
                                                onChange={handleInputChange}
                                                placeholder="Max par équipe"
                                                className="w-full px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Prix du repas */}
                                <div className="mb-8">
                                    <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">🍽️ Prix du repas (optionnel)</label>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="number"
                                            name="mealPrice"
                                            value={data.mealPrice}
                                            onChange={handleInputChange}
                                            placeholder="0.00"
                                            step="0.01"
                                            min="0"
                                            className="flex-1 px-3 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        />
                                        <span className="text-gray-500 font-semibold">€</span>
                                    </div>
                                </div>

                                {/* Image */}
                                <div className="mb-8">
                                    <label className="block text-xs font-black text-blue-900 mb-2 uppercase tracking-wider">🖼️ Image (optionnel)</label>
                                    <div className="w-full h-40 bg-gray-200 rounded-xl flex items-center justify-center mb-3 border-2 border-dashed border-gray-300 overflow-hidden">
                                        {data.image ? (
                                            <img
                                                src={URL.createObjectURL(data.image)}
                                                alt="Preview"
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <span className="text-gray-400">Aperçu image</span>
                                        )}
                                    </div>
                                    <label className="inline-flex items-center px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 hover:text-blue-700 text-xs font-black uppercase tracking-widest rounded-lg cursor-pointer transition-colors border-2 border-blue-200">
                                        + Ajouter une image
                                        <input
                                            type="file"
                                            name="image"
                                            onChange={handleImageChange}
                                            accept="image/*"
                                            className="hidden"
                                        />
                                    </label>
                                </div>
                            </div>
                            </div>

                            {/* Bouton Submit */}
                            <div className="mt-8 flex justify-center pt-6 border-t border-gray-200">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={`inline-flex items-center gap-2 px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg ${
                                        processing 
                                            ? 'bg-gray-400 cursor-not-allowed text-gray-600' 
                                            : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-200 hover:shadow-emerald-300'
                                    }`}
                                >
                                    <Trophy className="h-5 w-5" />
                                    {processing ? 'Création en cours...' : 'Créer la course'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {/* Modal de sélection du responsable */}
            <SelectResponsableModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSelect={handleSelectResponsable}
                users={users}
            />
        </AuthenticatedLayout>
    );
}