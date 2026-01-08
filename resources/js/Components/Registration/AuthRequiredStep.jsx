import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { LogIn, UserPlus, Mail, Lock, Eye, EyeOff } from 'lucide-react';

/**
 * Authentication step for unauthenticated users.
 * Displays login/register forms inline within the modal.
 * 
 * @param {object} translations - Translation strings
 * @param {function} onNext - Handler for proceeding to next step
 */
export default function AuthRequiredStep({ translations, onNext }) {
    const [mode, setMode] = useState('login'); // 'login' or 'register'
    const [showPassword, setShowPassword] = useState(false);
    const [formData, setFormData] = useState({
        email: '',
        password: '',
        first_name: '',
        last_name: '',
        password_confirmation: '',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        const endpoint = mode === 'login' ? '/login' : '/register';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(formData),
            });

            if (response.ok) {
                // Reload page to get authenticated state
                window.location.reload();
            } else {
                const data = await response.json();
                setError(data.message || data.errors?.email?.[0] || 'Une erreur est survenue');
            }
        } catch (err) {
            setError('Erreur de connexion');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-6">
            {/* Toggle buttons */}
            <div className="flex bg-slate-100 rounded-2xl p-1">
                <button
                    type="button"
                    onClick={() => setMode('login')}
                    className={`flex-1 py-3 px-4 rounded-xl font-bold text-sm uppercase tracking-wide transition-all ${mode === 'login'
                            ? 'bg-white text-blue-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                        }`}
                >
                    <LogIn className="inline h-4 w-4 mr-2" />
                    {translations.login || 'Connexion'}
                </button>
                <button
                    type="button"
                    onClick={() => setMode('register')}
                    className={`flex-1 py-3 px-4 rounded-xl font-bold text-sm uppercase tracking-wide transition-all ${mode === 'register'
                            ? 'bg-white text-blue-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                        }`}
                >
                    <UserPlus className="inline h-4 w-4 mr-2" />
                    {translations.register || 'Inscription'}
                </button>
            </div>

            {/* Error message */}
            {error && (
                <div className="p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-700">
                    {error}
                </div>
            )}

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-4">
                {mode === 'register' && (
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                Prénom
                            </label>
                            <input
                                type="text"
                                value={formData.first_name}
                                onChange={(e) => setFormData(prev => ({ ...prev, first_name: e.target.value }))}
                                className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all"
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                Nom
                            </label>
                            <input
                                type="text"
                                value={formData.last_name}
                                onChange={(e) => setFormData(prev => ({ ...prev, last_name: e.target.value }))}
                                className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all"
                                required
                            />
                        </div>
                    </div>
                )}

                <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        <Mail className="inline h-3 w-3 mr-1" />
                        {translations.email || 'Email'}
                    </label>
                    <input
                        type="email"
                        value={formData.email}
                        onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
                        className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all"
                        required
                    />
                </div>

                <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        <Lock className="inline h-3 w-3 mr-1" />
                        {translations.password || 'Mot de passe'}
                    </label>
                    <div className="relative">
                        <input
                            type={showPassword ? 'text' : 'password'}
                            value={formData.password}
                            onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all pr-12"
                            required
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                        </button>
                    </div>
                </div>

                {mode === 'register' && (
                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            <Lock className="inline h-3 w-3 mr-1" />
                            {translations.confirm_password || 'Confirmer le mot de passe'}
                        </label>
                        <input
                            type="password"
                            value={formData.password_confirmation}
                            onChange={(e) => setFormData(prev => ({ ...prev, password_confirmation: e.target.value }))}
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all"
                            required
                        />
                    </div>
                )}

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-blue-200 transition-all disabled:opacity-50"
                >
                    {loading ? (
                        <span className="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent" />
                    ) : mode === 'login' ? (
                        translations.login || 'Se connecter'
                    ) : (
                        translations.register || "S'inscrire"
                    )}
                </button>
            </form>

            {mode === 'login' && (
                <p className="text-center text-sm text-slate-500">
                    <a href="/forgot-password" className="text-blue-600 hover:underline">
                        {translations.forgot_password || 'Mot de passe oublié ?'}
                    </a>
                </p>
            )}
        </div>
    );
}
