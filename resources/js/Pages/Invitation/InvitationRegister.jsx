import { Head, useForm } from '@inertiajs/react';
import { User, Mail, Calendar, MapPin, Lock, Phone } from 'lucide-react';

export default function InvitationRegister({ invitation }) {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        birth_date: '',
        address: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('invitation.store-registration', invitation.token));
    };

    return (
        <>
            <Head title="Créer un compte" />

            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-6">
                <div className="max-w-2xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white">
                        <h1 className="text-3xl font-bold mb-2">Créer votre compte</h1>
                        <p className="text-blue-100">Pour rejoindre l'équipe de {invitation.inviter_name}</p>
                    </div>

                    {/* Content */}
                    <div className="p-8">
                        <div className="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <p className="text-sm text-gray-600 mb-1">Course</p>
                            <p className="font-bold text-gray-900">{invitation.race_name}</p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* First Name */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Prénom *
                                </label>
                                <div className="relative">
                                    <User className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                {errors.first_name && <p className="mt-1 text-sm text-red-600">{errors.first_name}</p>}
                            </div>

                            {/* Last Name */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Nom *
                                </label>
                                <div className="relative">
                                    <User className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        value={data.last_name}
                                        onChange={(e) => setData('last_name', e.target.value)}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                {errors.last_name && <p className="mt-1 text-sm text-red-600">{errors.last_name}</p>}
                            </div>

                            {/* Email (read-only) */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="email"
                                        value={invitation.email}
                                        disabled
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
                                    />
                                </div>
                            </div>

                            {/* Birth Date */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Date de naissance *
                                </label>
                                <div className="relative">
                                    <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="date"
                                        value={data.birth_date}
                                        onChange={(e) => setData('birth_date', e.target.value)}
                                        max={new Date().toISOString().split('T')[0]}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                {errors.birth_date && <p className="mt-1 text-sm text-red-600">{errors.birth_date}</p>}
                            </div>

                            {/* Address */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Adresse *
                                </label>
                                <div className="relative">
                                    <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                {errors.address && <p className="mt-1 text-sm text-red-600">{errors.address}</p>}
                            </div>

                            {/* Phone */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Téléphone *
                                </label>
                                <div className="relative">
                                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                {errors.phone && <p className="mt-1 text-sm text-red-600">{errors.phone}</p>}
                            </div>

                            {/* Password */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Mot de passe *
                                </label>
                                <div className="relative">
                                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                        minLength={8}
                                    />
                                </div>
                                {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                            </div>

                            {/* Password Confirmation */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Confirmer le mot de passe *
                                </label>
                                <div className="relative">
                                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                        minLength={8}
                                    />
                                </div>
                                {errors.password_confirmation && <p className="mt-1 text-sm text-red-600">{errors.password_confirmation}</p>}
                            </div>

                            {/* Submit */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed mt-6"
                            >
                                {processing ? 'Création...' : 'Créer mon compte et rejoindre l\'équipe'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
