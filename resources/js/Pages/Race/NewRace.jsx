import { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router, Link } from '@inertiajs/react';
import UserSelect from '@/Components/UserSelect';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import ImageUpload from '@/Components/ImageUpload';
import { Trophy, ChevronRight } from 'lucide-react';

/**
 * Helper function to convert duration in minutes to H:mm format
 * @param {number|null} minutes - Duration in minutes
 * @returns {string} Duration string in H:mm format
 */
const convertMinutesToDuration = (minutes) => {
    if (!minutes) return '';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}:${String(mins).padStart(2, '0')}`;
};

/**
 * Helper function to extract date from datetime string
 * @param {string|null} datetime - Datetime string
 * @returns {string} Date string (YYYY-MM-DD)
 */
const extractDate = (datetime) => {
    if (!datetime) return '';
    return datetime.split('T')[0].split(' ')[0];
};

/**
 * Helper function to extract time from datetime string
 * @param {string|null} datetime - Datetime string
 * @returns {string} Time string (HH:mm)
 */
const extractTime = (datetime) => {
    if (!datetime) return '';
    const timePart = datetime.split('T')[1] || datetime.split(' ')[1];
    return timePart ? timePart.substring(0, 5) : '';
};

/**
 * NewRace Component - Form for creating or editing a race
 * @param {Object} props - Component props
 * @param {Object} props.auth - Authentication data
 * @param {Array} props.users - List of users for responsable selection
 * @param {Array} props.types - List of race types
 * @param {Array} props.ageCategories - List of age categories
 * @param {number|null} props.raid_id - Raid ID (for new races)
 * @param {Object|null} props.raid - Raid data
 * @param {Object|null} props.race - Race data (null for create, object for edit)
 */
export default function NewRace({ auth, users = [], types = [], ageCategories = [], raid_id = null, raid = null, race = null }) {
    // Determine if we're in edit mode
    const isEditMode = race !== null;
    
    // Find the user ID from adh_id for edit mode
    const getResponsableUserId = () => {
        if (!race || !race.adh_id) return '';
        const user = users.find(u => u.adh_id === race.adh_id);
        return user ? user.id : '';
    };

    const { data, setData, post, put, processing, errors } = useForm({
        title: race?.race_name || '',
        description: race?.race_description || '',
        responsableId: getResponsableUserId(),
        startDate: extractDate(race?.race_date_start),
        startTime: extractTime(race?.race_date_start),
        duration: convertMinutesToDuration(race?.race_duration_minutes),
        endDate: extractDate(race?.race_date_end),
        endTime: extractTime(race?.race_date_end),
        minParticipants: race?.runner_params?.pac_nb_min || '1',
        maxParticipants: race?.runner_params?.pac_nb_max || '10',
        minPerTeam: race?.team_params?.pae_team_count_min || '1',
        maxPerTeam: race?.team_params?.pae_team_count_max || '1',
        minTeams: race?.team_params?.pae_nb_min || '1',
        maxTeams: race?.team_params?.pae_nb_max || '1',
        priceMajor: race?.price_major || '0',
        priceMinor: race?.price_minor || '0',
        priceAdherent: race?.price_adherent || '',
        difficulty: race?.race_difficulty || '',
        type: race?.typ_id ? parseInt(race.typ_id) : (types.length > 0 ? parseInt(types[0].id) : ''),
        mealPrice: race?.race_meal_price || '',
        image: null,
        raid_id: race?.raid_id || raid_id || '',
        selectedAgeCategories: race?.categorieAges?.map(pc => parseInt(pc.ageCategory?.id)) || [],
        // Leisure age rules (A <= B <= C)
        leisureAgeMin: race?.leisure_age_min || '12',
        leisureAgeIntermediate: race?.leisure_age_intermediate || '16',
        leisureAgeSupervisor: race?.leisure_age_supervisor || '18',
    });

    // Date validation state
    const [dateErrors, setDateErrors] = useState({});
    
    // Toggle age category selection
    const toggleAgeCategory = (categoryId) => {
        const parsedId = parseInt(categoryId);
        setData('selectedAgeCategories', 
            data.selectedAgeCategories.includes(parsedId)
                ? data.selectedAgeCategories.filter(id => id !== parsedId)
                : [...data.selectedAgeCategories, parsedId]
        );
    };
    // Delete confirmation modal state (only used in edit mode)
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    /**
     * Handle race deletion
     */
    const handleDelete = () => {
        router.delete(route('races.destroy', race.race_id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        });
    };

    /**
     * Close delete confirmation modal
     */
    const closeDeleteModal = () => {
        setShowDeleteModal(false);
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

        // Check start date is not in the past
        if (startDate) {
            const start = new Date(startDate);
            if (start < today) {
                newErrors.startDate = 'La date de début ne peut pas être dans le passé';
            } else {
                delete newErrors.startDate;
            }
        }

        // Check raid date boundaries if raid exists
        if (raid && raid.raid_date_start && raid.raid_date_end) {
            const raidStart = new Date(raid.raid_date_start);
            const raidEnd = new Date(raid.raid_date_end);
            raidStart.setHours(0, 0, 0, 0);
            raidEnd.setHours(23, 59, 59, 999);

            if (startDate) {
                const start = new Date(startDate);
                if (start < raidStart || start > raidEnd) {
                    newErrors.startDate = `La date de début doit être entre le ${raidStart.toLocaleDateString('fr-FR')} et le ${raidEnd.toLocaleDateString('fr-FR')}`;
                } else {
                    delete newErrors.startDate;
                }
            }

            if (endDate) {
                const end = new Date(endDate);
                if (end < raidStart || end > raidEnd) {
                    newErrors.endDate = `La date de fin doit être entre le ${raidStart.toLocaleDateString('fr-FR')} et le ${raidEnd.toLocaleDateString('fr-FR')}`;
                } else {
                    delete newErrors.endDate;
                }
            }
        }

        // Check end date is after or equal to start date
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            if (end < start) {
                newErrors.endDate = 'La date de fin doit être égale ou postérieure à la date de début';
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

    const isCompetitive = types.find(t => t.id === parseInt(data.type))?.name.toLowerCase() === 'compétitif' ||
        types.find(t => t.id === parseInt(data.type))?.name.toLowerCase() === 'competitif';

    // Check if an age category is available for competitive races
    const isAgeCategoryAvailable = (category) => {
        if (!isCompetitive) return true;
        return category.age_min >= 18;
    };

    // Remove unavailable categories when switching to competitive
    const handleTypeChange = (typeId) => {
        setData('type', parseInt(typeId));
        const newIsCompetitive = types.find(t => t.id === parseInt(typeId))?.name.toLowerCase() === 'compétitif' ||
            types.find(t => t.id === parseInt(typeId))?.name.toLowerCase() === 'competitif';
        
        if (newIsCompetitive) {
            // Filter out categories with age_min < 18
            const filteredCategories = data.selectedAgeCategories.filter(catId => {
                const category = ageCategories.find(c => parseInt(c.id) === parseInt(catId));
                return category && category.age_min >= 18;
            });
            setData('selectedAgeCategories', filteredCategories);
            // Clear price for minors in competitive races
            setData('priceMinor', '');
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setData(name, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Validation for competitive races: Check if at least one age category is selected
        if (isCompetitive && data.selectedAgeCategories.length === 0) {
            alert('Veuillez sélectionner au moins une catégorie d\'âge pour cette course compétitive.');
            return;
        }

        // Validation for leisure races: Check A <= B <= C
        if (!isCompetitive) {
            const a = parseInt(data.leisureAgeMin);
            const b = parseInt(data.leisureAgeIntermediate);
            const c = parseInt(data.leisureAgeSupervisor);
            
            if (isNaN(a) || isNaN(b) || isNaN(c)) {
                alert('Veuillez renseigner les trois valeurs d\'âge (A, B, C) pour cette course loisir.');
                return;
            }
            
            if (a > b || b > c) {
                alert('Les valeurs d\'âge doivent respecter la règle : A ≤ B ≤ C');
                return;
            }
        }
        
        // Prepare submission data
        const submissionData = { ...data };
        
        // Clear priceMinor if competitive race
        if (isCompetitive) {
            submissionData.priceMinor = '';
            // Clear leisure age rules for competitive races
            submissionData.leisureAgeMin = null;
            submissionData.leisureAgeIntermediate = null;
            submissionData.leisureAgeSupervisor = null;
        } else {
            // Clear age categories for leisure races
            submissionData.selectedAgeCategories = [];
        }
        
        // Debug: Log all form data
        console.log('Form data before submission:', submissionData);
        console.log('Processing state:', processing);
        console.log('Selected age categories:', submissionData.selectedAgeCategories);
        console.log('Is competitive:', isCompetitive);
        console.log('Image file:', submissionData.image);
        console.log('Image is File?:', submissionData.image instanceof File);
        
        if (isEditMode) {
            // Use router.post with _method: PUT for file uploads to work correctly
            router.post(route('races.update', race.race_id), {
                _method: 'PUT',
                ...submissionData,
            }, {
                forceFormData: true,
            });
        } else {
            // Update data with cleaned values for create mode
            Object.keys(submissionData).forEach(key => {
                setData(key, submissionData[key]);
            });
            
            // Send form data with forceFormData to handle file upload
            post(route('races.store'), {
                forceFormData: true,
            });
        }
    };

    // Page title and button text based on mode
    const pageTitle = isEditMode ? 'Modifier la Course' : 'Créer une Nouvelle Course';
    const submitButtonText = isEditMode 
        ? (processing ? 'Modification en cours...' : 'Modifier la course')
        : (processing ? 'Création en cours...' : 'Créer la course');

    return (
        <AuthenticatedLayout
            user={auth.user}
        >
            <Head title={pageTitle} />

            {/* Header / Hero Section - Blue Bar Style */}
            <div className="bg-blue-900 py-8 relative overflow-hidden border-b-4 border-emerald-500">
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Trophy className="w-96 h-96 -rotate-12" />
                </div>

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="flex items-center justify-between">
                        {/* Back Button */}
                        {isEditMode ? (
                            <Link 
                                href={route('races.show', race.race_id)} 
                                className="inline-flex items-center gap-2 text-xs font-bold text-emerald-400 hover:text-white transition-colors uppercase tracking-widest"
                            >
                                <ChevronRight className="w-4 h-4 rotate-180" />
                                Retour à la course
                            </Link>
                        ) : raid && raid.raid_id ? (
                            <Link 
                                href={route('raids.show', raid.raid_id)} 
                                className="inline-flex items-center gap-2 text-xs font-bold text-emerald-400 hover:text-white transition-colors uppercase tracking-widest"
                            >
                                <ChevronRight className="w-4 h-4 rotate-180" />
                                Retour au raid
                            </Link>
                        ) : (
                            <div />
                        )}
                        
                        <h1 className="text-2xl font-bold text-white text-center flex-1">
                            {pageTitle}
                        </h1>
                        
                        <div className="w-20" />
                    </div>
                </div>
            </div>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={handleSubmit} className="p-8">
                            {/* Affichage des erreurs */}
                            {Object.keys(errors).length > 0 && (
                                <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <h3 className="text-sm font-semibold text-red-800 mb-2">Erreurs de validation</h3>
                                    <ul className="text-sm text-red-700 space-y-1">
                                        {Object.entries(errors).map(([field, message]) => (
                                            <li key={field}>• {message}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Titre Section - Colonne pleine */}
                                <div className="col-span-1 lg:col-span-2">
                                    <div className="flex items-center justify-between mb-6">
                                        <h3 className="text-lg font-semibold text-gray-900">Informations de la course</h3>
                                        {raid && (
                                            <div className="bg-indigo-50 border border-indigo-200 px-4 py-2 rounded-lg flex items-center gap-2">
                                                <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span className="text-sm font-medium text-indigo-700">Raid : {raid.raid_name}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Nom et Responsable - Ligne 1 */}
                                <div>
                                    <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                                        Nom de la course *
                                    </label>
                                    <input
                                        type="text"
                                        id="title"
                                        name="title"
                                        value={data.title}
                                        onChange={handleInputChange}
                                        placeholder="Nom de la course"
                                        maxLength={100}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        required
                                    />
                                    <p className="mt-1 text-xs text-gray-500">{data.title.length}/100 caractères</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Responsable *
                                    </label>
                                    <UserSelect
                                        users={users}
                                        selectedId={data.responsableId}
                                        onSelect={(user) => setData('responsableId', user.id)}
                                        label="Responsable"
                                    />
                                    {errors.responsableId && <p className="mt-1 text-sm text-red-600">{errors.responsableId}</p>}
                                </div>

                                {/* Description - Colonne pleine */}
                                <div className="col-span-1 lg:col-span-2">
                                    <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                        Description de la course
                                    </label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        value={data.description}
                                        onChange={handleInputChange}
                                        placeholder="Décrivez la course (parcours, règles, etc.)"
                                        rows="4"
                                        maxLength={2000}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                    <p className="mt-1 text-xs text-gray-500">{data.description.length}/2000 caractères</p>
                                    {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                                </div>

                                {/* Dates de Départ - Ligne 2 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Date de départ *
                                        {raid && raid.raid_date_start && raid.raid_date_end && (
                                            <span className="text-xs font-normal text-gray-500 ml-2 block">
                                                (Raid: {new Date(raid.raid_date_start).toLocaleDateString('fr-FR')} au {new Date(raid.raid_date_end).toLocaleDateString('fr-FR')})
                                            </span>
                                        )}
                                    </label>
                                    <input
                                        type="date"
                                        name="startDate"
                                        value={data.startDate}
                                        onChange={handleDateChange}
                                        min={(() => {
                                            const today = new Date().toISOString().split('T')[0];
                                            if (!raid?.raid_date_start) return today;
                                            const raidStart = raid.raid_date_start.split('T')[0];
                                            return today > raidStart ? today : raidStart;
                                        })()}
                                        {...(raid?.raid_date_end && { max: raid.raid_date_end.split('T')[0] })}
                                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent ${dateErrors.startDate ? 'border-red-500' : 'border-gray-300'}`}
                                        required
                                    />
                                    {dateErrors.startDate && <p className="mt-1 text-sm text-red-600">{dateErrors.startDate}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Heure de départ *
                                    </label>
                                    <input
                                        type="time"
                                        name="startTime"
                                        value={data.startTime}
                                        onChange={handleDateChange}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                {/* Durée et Difficulté - Ligne 3 */}
                                <div>
                                    <label htmlFor="duration" className="block text-sm font-medium text-gray-700 mb-2">
                                        Durée (h:mm) *
                                    </label>
                                    <input
                                        type="text"
                                        id="duration"
                                        name="duration"
                                        value={data.duration}
                                        onChange={handleDateChange}
                                        placeholder="2:30"
                                        pattern="\d+:\d{2}"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                    <p className="mt-1 text-xs text-gray-500">Format: heures:minutes (ex: 2:30)</p>
                                </div>

                                <div>
                                    <label htmlFor="difficulty" className="block text-sm font-medium text-gray-700 mb-2">
                                        Difficulté *
                                    </label>
                                    <input
                                        type="text"
                                        id="difficulty"
                                        name="difficulty"
                                        value={data.difficulty}
                                        onChange={handleInputChange}
                                        placeholder="Ex: Facile, Expert, Technique..."
                                        maxLength={255}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        required
                                    />
                                    {errors.difficulty && <p className="mt-1 text-sm text-red-600">{errors.difficulty}</p>}
                                </div>

                                {/* Dates de Fin - Ligne 4 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Date de fin
                                    </label>
                                    <input
                                        type="date"
                                        name="endDate"
                                        value={data.endDate}
                                        onChange={handleDateChange}
                                        min={data.startDate || new Date().toISOString().split('T')[0]}
                                        {...(raid?.raid_date_end && { max: raid.raid_date_end.split('T')[0] })}
                                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent ${dateErrors.endDate ? 'border-red-500' : 'border-gray-300'}`}
                                    />
                                    {dateErrors.endDate && <p className="mt-1 text-sm text-red-600">{dateErrors.endDate}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Heure de fin
                                    </label>
                                    <input
                                        type="time"
                                        name="endTime"
                                        value={data.endTime}
                                        onChange={handleDateChange}
                                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent ${dateErrors.endTime ? 'border-red-500' : 'border-gray-300'}`}
                                    />
                                    {dateErrors.endTime && <p className="mt-1 text-sm text-red-600">{dateErrors.endTime}</p>}
                                </div>

                                {/* Participants - Ligne 5 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Min participants *
                                    </label>
                                    <input
                                        type="number"
                                        name="minParticipants"
                                        value={data.minParticipants}
                                        onChange={handleInputChange}
                                        placeholder="0"
                                        required
                                        min="1"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Max participants *
                                    </label>
                                    <input
                                        type="number"
                                        name="maxParticipants"
                                        value={data.maxParticipants}
                                        onChange={handleInputChange}
                                        placeholder="0"
                                        required
                                        min="1"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>

                                {/* Équipes - Ligne 6 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Min équipes *
                                    </label>
                                    <input
                                        type="number"
                                        name="minTeams"
                                        value={data.minTeams}
                                        onChange={handleInputChange}
                                        placeholder="0"
                                        required
                                        min="1"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Max équipes *
                                    </label>
                                    <input
                                        type="number"
                                        name="maxTeams"
                                        value={data.maxTeams}
                                        onChange={handleInputChange}
                                        placeholder="0"
                                        required
                                        min="1"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>

                                {/* Max par équipe - Ligne 7 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Min par équipe *
                                    </label>
                                    <input
                                        type="number"
                                        name="minPerTeam"
                                        value={data.minPerTeam}
                                        onChange={handleInputChange}
                                        placeholder="1"
                                        required
                                        min="1"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Max par équipe *
                                    </label>
                                    <input
                                        type="number"
                                        name="maxPerTeam"
                                        value={data.maxPerTeam}
                                        onChange={handleInputChange}
                                        placeholder="1"
                                        required
                                        min="1"
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Type
                                    </label>
                                    <div className="space-y-2">
                                        {types.length > 0 ? (
                                            types.map((type) => (
                                                <label key={type.id} className="flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="type"
                                                        value={type.id}
                                                        checked={data.type === type.id}
                                                        onChange={(e) => handleTypeChange(parseInt(e.target.value))}
                                                        className="w-4 h-4 text-indigo-600"
                                                    />
                                                    <span className="ml-2 text-gray-700 capitalize text-sm">{type.name}</span>
                                                </label>
                                            ))
                                        ) : (
                                            <p className="text-sm text-gray-500 italic">Aucun type</p>
                                        )}
                                    </div>
                                </div>

                                {/* Tarifs - Ligne 8 - Colonne pleine */}
                                <div className="col-span-1 lg:col-span-2">
                                    <h4 className="text-sm font-semibold text-gray-900 mb-4">Tarifs d'inscription</h4>
                                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        {/* Prix Majeurs */}
                                        <div>
                                            <label className="block text-xs font-medium text-gray-600 mb-2">Majeurs (18 ans +) *</label>
                                            <div className="flex items-center">
                                                <input
                                                    type="number"
                                                    name="priceMajor"
                                                    value={data.priceMajor}
                                                    onChange={handleInputChange}
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    min="0"
                                                    required
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                />
                                                <span className="ml-2 text-gray-500 text-sm">€</span>
                                            </div>
                                            {errors.priceMajor && <p className="mt-1 text-xs text-red-600">{errors.priceMajor}</p>}
                                        </div>

                                        {/* Prix Mineurs */}
                                        <div>
                                            <label className={`block text-xs font-medium mb-2 ${isCompetitive ? 'text-gray-400' : 'text-gray-600'}`}>
                                                Mineurs (- 18 ans)
                                            </label>
                                            <div className="flex items-center">
                                                <input
                                                    type="number"
                                                    name="priceMinor"
                                                    value={data.priceMinor}
                                                    onChange={handleInputChange}
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    min="0"
                                                    disabled={isCompetitive}
                                                    className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm ${
                                                        isCompetitive 
                                                            ? 'bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed' 
                                                            : 'border-gray-300'
                                                    }`}
                                                />
                                                <span className={`ml-2 text-sm ${isCompetitive ? 'text-gray-400' : 'text-gray-500'}`}>€</span>
                                            </div>
                                            {errors.priceMinor && <p className="mt-1 text-xs text-red-600">{errors.priceMinor}</p>}
                                        </div>

                                        {/* Prix Adhérents */}
                                        <div>
                                            <label className="block text-xs font-medium text-gray-600 mb-2">Adhérents (licenciés)</label>
                                            <div className="flex items-center">
                                                <input
                                                    type="number"
                                                    name="priceAdherent"
                                                    value={data.priceAdherent}
                                                    onChange={handleInputChange}
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    min="0"
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                />
                                                <span className="ml-2 text-gray-500 text-sm">€</span>
                                            </div>
                                            {errors.priceAdherent && <p className="mt-1 text-xs text-red-600">{errors.priceAdherent}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* Avertissement compétitif */}
                                {isCompetitive && (
                                    <div className="col-span-1 lg:col-span-2">
                                        <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-2">
                                            <svg className="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p className="text-xs text-blue-700">
                                                <strong>Mode Compétitif activé :</strong> Les courses compétitives requièrent que tous les membres d'une équipe soient dans la même catégorie d'âge parmi celles sélectionnées ci-dessous.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* Avertissement loisir */}
                                {!isCompetitive && (
                                    <div className="col-span-1 lg:col-span-2">
                                        <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-start gap-2">
                                            <svg className="w-5 h-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p className="text-xs text-emerald-700">
                                                <strong>Mode Loisir activé :</strong> Les règles d'âge sont plus souples. Configurez les valeurs A, B, C ci-dessous pour définir les règles de participation.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* Règles d'âge Loisir (A, B, C) - Seulement pour type loisir */}
                                {!isCompetitive && (
                                    <div className="col-span-1 lg:col-span-2">
                                        <h4 className="text-sm font-semibold text-gray-900 mb-4">Règles d'âge pour courses loisir</h4>
                                        <div className="p-4 bg-gray-50 border border-gray-200 rounded-lg mb-4">
                                            <p className="text-sm text-gray-600 mb-3">
                                                <strong>Règles :</strong> Tous les participants doivent avoir au moins <strong>A ans</strong>. 
                                                Si un participant a moins de <strong>B ans</strong>, l'équipe doit inclure un participant d'au moins <strong>C ans</strong> (accompagnateur). 
                                                Sinon, tous doivent avoir au moins <strong>B ans</strong>.
                                            </p>
                                            <p className="text-xs text-gray-500 italic">
                                                Exemple : A=12, B=16, C=18 → Tous ont au moins 12 ans, et les équipes avec un - de 16 ans doivent avoir un majeur (18+).
                                            </p>
                                        </div>
                                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                            {/* Âge minimum (A) */}
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 mb-2">
                                                    Âge minimum (A) *
                                                </label>
                                                <input
                                                    type="number"
                                                    name="leisureAgeMin"
                                                    value={data.leisureAgeMin}
                                                    onChange={handleInputChange}
                                                    placeholder="12"
                                                    min="0"
                                                    max={data.leisureAgeIntermediate || 100}
                                                    required={!isCompetitive}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                                                />
                                                <p className="mt-1 text-xs text-gray-500">Âge min. pour tous les participants</p>
                                                {errors.leisureAgeMin && <p className="mt-1 text-xs text-red-600">{errors.leisureAgeMin}</p>}
                                            </div>

                                            {/* Âge intermédiaire (B) */}
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 mb-2">
                                                    Âge intermédiaire (B) *
                                                </label>
                                                <input
                                                    type="number"
                                                    name="leisureAgeIntermediate"
                                                    value={data.leisureAgeIntermediate}
                                                    onChange={handleInputChange}
                                                    placeholder="16"
                                                    min={data.leisureAgeMin || 0}
                                                    max={data.leisureAgeSupervisor || 100}
                                                    required={!isCompetitive}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                                                />
                                                <p className="mt-1 text-xs text-gray-500">Seuil sans accompagnateur</p>
                                                {errors.leisureAgeIntermediate && <p className="mt-1 text-xs text-red-600">{errors.leisureAgeIntermediate}</p>}
                                            </div>

                                            {/* Âge accompagnateur (C) */}
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 mb-2">
                                                    Âge accompagnateur (C) *
                                                </label>
                                                <input
                                                    type="number"
                                                    name="leisureAgeSupervisor"
                                                    value={data.leisureAgeSupervisor}
                                                    onChange={handleInputChange}
                                                    placeholder="18"
                                                    min={data.leisureAgeIntermediate || 0}
                                                    required={!isCompetitive}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                                                />
                                                <p className="mt-1 text-xs text-gray-500">Âge requis de l'accompagnateur</p>
                                                {errors.leisureAgeSupervisor && <p className="mt-1 text-xs text-red-600">{errors.leisureAgeSupervisor}</p>}
                                            </div>
                                        </div>

                                        {/* Validation A <= B <= C */}
                                        {(parseInt(data.leisureAgeMin) > parseInt(data.leisureAgeIntermediate) || 
                                          parseInt(data.leisureAgeIntermediate) > parseInt(data.leisureAgeSupervisor)) && (
                                            <p className="mt-3 text-sm text-red-600 flex items-center gap-2">
                                                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                Les valeurs doivent respecter : A ≤ B ≤ C
                                            </p>
                                        )}
                                    </div>
                                )}

                                {/* Catégories d'âges - Seulement pour type compétitif */}
                                {isCompetitive && (
                                    <div className="col-span-1 lg:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 mb-3">
                                            Catégories d'âges acceptées
                                            <span className="text-red-500 ml-1">*</span>
                                            <span className="text-xs text-gray-500 ml-2">({data.selectedAgeCategories.length} sélectionnée{data.selectedAgeCategories.length !== 1 ? 's' : ''})</span>
                                        </label>
                                        {data.selectedAgeCategories.length === 0 && (
                                            <p className="text-sm text-amber-600 mb-3 flex items-center gap-2">
                                                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                Veuillez sélectionner au moins une catégorie d'âge
                                            </p>
                                        )}
                                        <p className="text-sm text-gray-600 mb-3">
                                            Tous les membres d'une équipe doivent appartenir à la <strong>même catégorie d'âge</strong> parmi celles sélectionnées.
                                        </p>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            {ageCategories.length > 0 ? (
                                                ageCategories.map((category) => {
                                                    const isAvailable = isAgeCategoryAvailable(category);
                                                    const isSelected = data.selectedAgeCategories.includes(parseInt(category.id));
                                                    
                                                    return (
                                                        <label 
                                                            key={category.id} 
                                                            className={`flex items-center p-3 border rounded-lg transition-colors text-sm ${
                                                                isAvailable 
                                                                    ? 'border-gray-200 hover:bg-blue-50 cursor-pointer' 
                                                                    : 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60'
                                                            }`}
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={isSelected}
                                                                onChange={() => isAvailable && toggleAgeCategory(category.id)}
                                                                disabled={!isAvailable}
                                                                className="w-4 h-4 text-emerald-600 rounded disabled:opacity-50"
                                                            />
                                                            <span className="ml-3">
                                                                <span className="font-medium text-gray-900">{category.nom}</span>
                                                                <span className="text-gray-500 text-xs ml-2">({category.age_min}-{category.age_max || '∞'})</span>
                                                                {!isAvailable && (
                                                                    <span className="text-red-600 text-xs ml-2 font-medium block">Non disponible en compétitif</span>
                                                                )}
                                                            </span>
                                                        </label>
                                                    );
                                                })
                                            ) : (
                                                <p className="text-sm text-gray-500 italic">Aucune catégorie</p>
                                            )}
                                        </div>
                                        {errors.selectedAgeCategories && <p className="mt-2 text-sm text-red-600">{errors.selectedAgeCategories}</p>}
                                    </div>
                                )}

                                {/* Prix du repas et Image - Ligne 9 */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Prix du repas (optionnel)
                                    </label>
                                    <div className="flex items-center">
                                        <input
                                            type="number"
                                            name="mealPrice"
                                            value={data.mealPrice}
                                            onChange={handleInputChange}
                                            placeholder="0.00"
                                            step="0.01"
                                            min="0"
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                        />
                                        <span className="ml-2 text-gray-500">€</span>
                                    </div>
                                </div>

                                <ImageUpload
                                    label="Image de la course"
                                    name="image"
                                    onChange={(file) => setData('image', file)}
                                    error={errors.image}
                                    currentImage={race?.race_image ? `/storage/${race.race_image}` : null}
                                    maxSize={5}
                                    helperText="Image qui sera affichée sur la page de la course (optionnel)"
                                />
                            </div>

                            {/* Bouton Submit */}
                            <div className="mt-8 flex justify-center col-span-1 lg:col-span-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={`${processing ? 'bg-gray-400 cursor-not-allowed' : 'bg-gray-800 hover:bg-gray-900'
                                        } text-white font-semibold py-3 px-12 rounded-lg transition`}
                                >
                                    {submitButtonText}
                                </button>
                            </div>

                            {/* Danger Zone - Only in edit mode */}
                            {isEditMode && (
                                <div className="col-span-1 lg:col-span-2 mt-8 bg-white rounded-lg shadow-md p-6 border-2 border-red-200">
                                    <h2 className="text-lg font-semibold text-red-600 mb-4">
                                        Zone de danger
                                    </h2>
                                    <p className="text-sm text-gray-600 mb-4">
                                        La suppression de la course est irréversible. Toutes les inscriptions associées seront également supprimées.
                                    </p>
                                    <DangerButton type="button" onClick={() => setShowDeleteModal(true)}>
                                        Supprimer la course
                                    </DangerButton>
                                </div>
                            )}
                        </form>
                    </div>
                </div>
            </div >

            {/* Delete Confirmation Modal */}
            <Modal show={showDeleteModal} onClose={closeDeleteModal}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        Êtes-vous sûr de vouloir supprimer cette course ?
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        La suppression de la course est irréversible. Toutes les inscriptions associées seront également supprimées.
                    </p>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton type="button" onClick={closeDeleteModal}>
                            Annuler
                        </SecondaryButton>

                        <DangerButton type="button" className="ms-3" onClick={handleDelete}>
                            Oui, supprimer
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout >
    );
}