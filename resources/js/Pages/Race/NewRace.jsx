import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function NewRace({ auth, user }) {
    const [formData, setFormData] = useState({
        title: '',
        organizer: '',
        startDate: '',
        startTime: '',
        duration: '',
        endDate: '',
        endTime: '',
        minParticipants: '',
        maxParticipants: '',
        maxPerTeam: '',
        difficulty: 'easy',
        type: 'competitive',
        categories: [{ minAge: '', maxAge: '', price: '' }],
        minTeams: '',
        maxTeams: '',
        licenseDiscount: '',
        meals: '',
        price: '',
        image: null,
    });

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleCategoryChange = (index, field, value) => {
        const newCategories = [...formData.categories];
        newCategories[index][field] = value;
        setFormData(prev => ({ ...prev, categories: newCategories }));
    };

    const addCategory = () => {
        setFormData(prev => ({
            ...prev,
            categories: [...prev.categories, { minAge: '', maxAge: '', price: '' }]
        }));
    };

    const handleImageChange = (e) => {
        if (e.target.files[0]) {
            setFormData(prev => ({ ...prev, image: e.target.files[0] }));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('Form Data:', formData);
        // Ajouté le formulaire au backend ici
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
                            <div className="grid grid-cols-3 gap-8">
                                {/* Colonne Gauche - Éléments Obligatoires */}
                                <div className="col-span-2">
                                    {/* Titre Section */}
                                    <h3 className="text-lg font-semibold text-gray-900 mb-6">Éléments Obligatoires</h3>

                                    {/* Nom de la course */}
                                    <div className="mb-6">
                                        <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                                            Nom de la course
                                        </label>
                                        <input
                                            type="text"
                                            id="title"
                                            name="title"
                                            value={formData.title}
                                            onChange={handleInputChange}
                                            placeholder="Nom de la course"
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            required
                                        />
                                    </div>

                                    {/* Sélection du responsable */}
                                    <div className="mb-6">
                                        <button
                                            type="button"
                                            className="w-full bg-amber-900 hover:bg-amber-800 text-white font-semibold py-3 px-4 rounded-lg transition"
                                        >
                                            Sélection du responsable
                                        </button>
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
                                                value={formData.startDate}
                                                onChange={handleInputChange}
                                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                required
                                            />
                                            <input
                                                type="time"
                                                name="startTime"
                                                value={formData.startTime}
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
                                            value={formData.duration}
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
                                                value={formData.endDate}
                                                onChange={handleInputChange}
                                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                            <input
                                                type="time"
                                                name="endTime"
                                                value={formData.endTime}
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
                                                value={formData.minParticipants}
                                                onChange={handleInputChange}
                                                placeholder="Min"
                                                className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                            <input
                                                type="number"
                                                name="maxParticipants"
                                                value={formData.maxParticipants}
                                                onChange={handleInputChange}
                                                placeholder="Max"
                                                className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>
                                        <input
                                            type="number"
                                            name="maxPerTeam"
                                            value={formData.maxPerTeam}
                                            onChange={handleInputChange}
                                            placeholder="Max par équipe"
                                            className="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>

                                    {/* Difficulté et Type */}
                                    <div className="grid grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-3">Difficulté</label>
                                            <div className="space-y-2">
                                                <label className="flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="difficulty"
                                                        value="easy"
                                                        checked={formData.difficulty === 'easy'}
                                                        onChange={handleInputChange}
                                                        className="w-4 h-4 text-indigo-600"
                                                    />
                                                    <span className="ml-2 text-gray-700">Facile</span>
                                                </label>
                                                <label className="flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="difficulty"
                                                        value="medium"
                                                        checked={formData.difficulty === 'medium'}
                                                        onChange={handleInputChange}
                                                        className="w-4 h-4 text-indigo-600"
                                                    />
                                                    <span className="ml-2 text-gray-700">Moyen</span>
                                                </label>
                                                <label className="flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="difficulty"
                                                        value="hard"
                                                        checked={formData.difficulty === 'hard'}
                                                        onChange={handleInputChange}
                                                        className="w-4 h-4 text-indigo-600"
                                                    />
                                                    <span className="ml-2 text-gray-700">Difficile</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-3">Type</label>
                                            <div className="space-y-2">
                                                <label className="flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="type"
                                                        value="competitive"
                                                        checked={formData.type === 'competitive'}
                                                        onChange={handleInputChange}
                                                        className="w-4 h-4 text-indigo-600"
                                                    />
                                                    <span className="ml-2 text-gray-700">Compétitif</span>
                                                </label>
                                                <label className="flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="type"
                                                        value="leisure"
                                                        checked={formData.type === 'leisure'}
                                                        onChange={handleInputChange}
                                                        className="w-4 h-4 text-indigo-600"
                                                    />
                                                    <span className="ml-2 text-gray-700">Rando / Loisir</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Colonne Droite */}
                                <div>
                                    {/* Catégories */}
                                    <div className="mb-8">
                                        <h4 className="text-sm font-semibold text-gray-900 mb-4">Catégories :</h4>
                                        <div className="space-y-3">
                                            {formData.categories.map((cat, index) => (
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
                                                value={formData.minTeams}
                                                onChange={handleInputChange}
                                                placeholder="Min"
                                                className="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                            <input
                                                type="number"
                                                name="maxTeams"
                                                value={formData.maxTeams}
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
                                            value={formData.licenseDiscount}
                                            onChange={handleInputChange}
                                            placeholder="Réduction pour les licenciés"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                        />
                                    </div>

                                    <div className="flex gap-2 mb-4">
                                        <input
                                            type="text"
                                            name="meals"
                                            value={formData.meals}
                                            onChange={handleInputChange}
                                            placeholder="Repas"
                                            className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                        />
                                        <input
                                            type="text"
                                            name="price"
                                            value={formData.price}
                                            onChange={handleInputChange}
                                            placeholder="Prix"
                                            className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                        />
                                    </div>

                                    {/* Image */}
                                    <div className="mb-6">
                                        <div className="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center mb-3">
                                            {formData.image ? (
                                                <img 
                                                    src={URL.createObjectURL(formData.image)} 
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
                            </div>

                            {/* Bouton Submit */}
                            <div className="mt-8 flex justify-center">
                                <button
                                    type="submit"
                                    className="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-3 px-12 rounded-lg transition"
                                >
                                    Créer la course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}