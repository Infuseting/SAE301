import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import SelectResponsableModal from '@/Components/SelectResponsableModal';

export default function NewRace({ auth, users = [], difficulties = [], types = [], raid_id = null, raid = null }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedResponsable, setSelectedResponsable] = useState(null);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
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
        licenseDiscount: '',
        meals: '',
        price: '',
        image: null,
        raid_id: raid_id || '',
        categories: [],
    });

    const isCompetitive = types.find(t => t.id === data.type)?.name.toLowerCase() === 'compétitif' ||
        types.find(t => t.id === data.type)?.name.toLowerCase() === 'competitif';

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setData(name, value);
    };

    const handleCategoryChange = (index, field, value) => {
        // Obsolete, replaced by specific fields
    };

    const [imagePreview, setImagePreview] = useState(null);

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('image', file);
            setImagePreview(URL.createObjectURL(file));
        }
    };

    const addCategory = () => {
        setData('categories', [...data.categories, { minAge: '', maxAge: '', price: '' }]);
    };


    /**
     * Handle responsable selection from modal
     * @param {object} user - The selected user object
     */
    const handleSelectResponsable = (user) => {
        setSelectedResponsable(user);
        setData('responsableId', user.id);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('races.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Créer une Nouvelle Course</h2>}
        >
            <Head title="Créer une Course" />

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

                            <div className="grid grid-cols-3 gap-8">
                                {/* Colonne Gauche - Éléments Obligatoires */}
                                <div className="col-span-2">
                                    {/* Titre Section */}
                                    <div className="flex items-center justify-between mb-6">
                                        <h3 className="text-lg font-semibold text-gray-900">Éléments Obligatoires</h3>
                                        {raid && (
                                            <div className="bg-indigo-50 border border-indigo-200 px-4 py-2 rounded-lg flex items-center gap-2">
                                                <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span className="text-sm font-medium text-indigo-700">Raid : {raid.raid_name}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Nom de la course */}
                                    <div className="mb-6">
                                        <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                                            Nom de la course
                                        </label>
                                        <input
                                            type="text"
                                            id="title"
                                            name="title"
                                            value={data.title}
                                            onChange={handleInputChange}
                                            placeholder="Nom de la course"
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            required
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

                                    {/* Date et heure de départ */}
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Date et heure de départ
                                        </label>
                                        <div className="flex gap-2">
                                            <input
                                                type="date"
                                                name="startDate"
                                                value={data.startDate}
                                                onChange={handleInputChange}
                                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                required
                                            />
                                            <input
                                                type="time"
                                                name="startTime"
                                                value={data.startTime}
                                                onChange={handleInputChange}
                                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                required
                                            />
                                        </div>
                                    </div>

                                    {/* Durée */}
                                    <div className="mb-6">
                                        <label htmlFor="duration" className="block text-sm font-medium text-gray-700 mb-2">
                                            Durée en h:mm
                                        </label>
                                        <input
                                            type="text"
                                            id="duration"
                                            name="duration"
                                            value={data.duration}
                                            onChange={handleInputChange}
                                            placeholder="0:30"
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>

                                    {/* Date et heure de fin */}
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Date et heure de fin
                                        </label>
                                        <div className="flex gap-2">
                                            <input
                                                type="date"
                                                name="endDate"
                                                value={data.endDate}
                                                onChange={handleInputChange}
                                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                            <input
                                                type="time"
                                                name="endTime"
                                                value={data.endTime}
                                                onChange={handleInputChange}
                                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>
                                    </div>

                                    {/* Nombre de participants */}
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Nombre de participants
                                        </label>
                                        <div className="flex gap-2 mb-2">
                                            <input
                                                type="number"
                                                name="minParticipants"
                                                value={data.minParticipants}
                                                onChange={handleInputChange}
                                                placeholder="Min"
                                                className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                            <input
                                                type="number"
                                                name="maxParticipants"
                                                value={data.maxParticipants}
                                                onChange={handleInputChange}
                                                placeholder="Max"
                                                className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>
                                        <input
                                            type="number"
                                            name="maxPerTeam"
                                            value={data.maxPerTeam}
                                            onChange={handleInputChange}
                                            placeholder="Max par équipe"
                                            className="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Difficulté de la course
                                        </label>
                                        <input
                                            type="text"
                                            name="difficulty"
                                            value={data.difficulty}
                                            onChange={handleInputChange}
                                            placeholder="Ex: Facile, Expert, Technique..."
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            required
                                        />
                                        {errors.difficulty && <p className="mt-1 text-sm text-red-600">{errors.difficulty}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-3">Type</label>
                                        <div className="space-y-2">
                                            {types.length > 0 ? (
                                                types.map((type) => (
                                                    <label key={type.id} className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="type"
                                                            value={type.id}
                                                            checked={data.type === type.id}
                                                            onChange={(e) => setData('type', parseInt(e.target.value))}
                                                            className="w-4 h-4 text-indigo-600"
                                                        />
                                                        <span className="ml-2 text-gray-700 capitalize">{type.name}</span>
                                                    </label>
                                                ))
                                            ) : (
                                                <p className="text-sm text-gray-500 italic">Aucun type disponible</p>
                                            )}
                                        </div>
                                        {isCompetitive && (
                                            <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-2">
                                                <svg className="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p className="text-xs text-blue-700">
                                                    <strong>Mode Compétitif actitvé :</strong> Les courses compétitives sont réservées aux adultes (18 ans et plus). Les âges minimums seront ajustés automatiquement.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Colonne Droite */}
                            <div>
                                {/* Catégories */}
                                <div className="mb-8">
                                    <h4 className="text-sm font-semibold text-gray-900 mb-4">Catégories :</h4>
                                    <div className="space-y-3">
                                        {data.categories.map((cat, index) => (
                                            <div key={index} className="flex gap-2">
                                                <input
                                                    type="number"
                                                    placeholder="Age min"
                                                    value={cat.minAge}
                                                    onChange={(e) => handleCategoryChange(index, 'minAge', e.target.value)}
                                                    className="w-20 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                />
                                                <input
                                                    type="number"
                                                    placeholder="Age Max"
                                                    value={cat.maxAge}
                                                    onChange={(e) => handleCategoryChange(index, 'maxAge', e.target.value)}
                                                    className="w-20 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                />
                                                <input
                                                    type="number"
                                                    placeholder="Prix"
                                                    value={cat.price}
                                                    onChange={(e) => handleCategoryChange(index, 'price', e.target.value)}
                                                    className="w-20 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                />
                                            </div>
                                        ))}
                                    </div>
                                    <button
                                        type="button"
                                        onClick={addCategory}
                                        className="mt-2 text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                                    >
                                        + Ajouter
                                    </button>
                                </div>

                                {/* Nombre d'équipes */}
                                <div className="mb-8">
                                    <h4 className="text-sm font-semibold text-gray-900 mb-3">Nombre d'équipes</h4>
                                    <div className="flex gap-2">
                                        <input
                                            type="number"
                                            name="minTeams"
                                            value={data.minTeams}
                                            onChange={handleInputChange}
                                            placeholder="Min"
                                            className="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                        <input
                                            type="number"
                                            name="maxTeams"
                                            value={data.maxTeams}
                                            onChange={handleInputChange}
                                            placeholder="Max"
                                            className="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>

                                {/* Éléments Facultatifs */}
                                <h4 className="text-sm font-semibold text-gray-900 mb-4">Éléments facultatifs :</h4>

                                <div className="mb-3">
                                    <input
                                        type="text"
                                        name="licenseDiscount"
                                        value={data.licenseDiscount}
                                        onChange={handleInputChange}
                                        placeholder="Réduction pour les licenciés"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                    />
                                </div>

                                <div className="flex gap-2 mb-4">
                                    <input
                                        type="text"
                                        name="meals"
                                        value={data.meals}
                                        onChange={handleInputChange}
                                        placeholder="Repas"
                                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                    />
                                    <input
                                        type="text"
                                        name="price"
                                        value={data.price}
                                        onChange={handleInputChange}
                                        placeholder="Prix"
                                        className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                    />
                                </div>

                                {/* Image */}
                                <div className="mb-6">
                                    <div className="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center mb-3">
                                        {data.image ? (
                                            <img
                                                src={URL.createObjectURL(data.image)}
                                                alt="Preview"
                                                className="w-full h-full object-cover rounded-lg"
                                            />
                                        ) : (
                                            <span className="text-gray-400">Aperçu image</span>
                                        )}
                                    </div>
                                    <label className="text-indigo-600 hover:text-indigo-700 text-sm font-medium cursor-pointer">
                                        ajouter une image
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


                            {/* Bouton Submit */}
                            <div className="mt-8 flex justify-center">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={`${processing ? 'bg-gray-400 cursor-not-allowed' : 'bg-gray-800 hover:bg-gray-900'
                                        } text-white font-semibold py-3 px-12 rounded-lg transition`}
                                >
                                    {processing ? 'Création en cours...' : 'Créer la course'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div >

            {/* Modal de sélection du responsable */}
            < SelectResponsableModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)
                }
                onSelect={handleSelectResponsable}
                users={users}
            />
        </AuthenticatedLayout >
    );
}