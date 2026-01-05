import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

// Icons
const GoogleIcon = () => (
    <svg className="h-5 w-5" viewBox="0 0 24 24">
        <path fill="currentColor" d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z" />
    </svg>
);

const DiscordIcon = () => (
    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1892.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.1023.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.419-2.1568 2.419zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.419-2.1568 2.419z" />
    </svg>
);

const GithubIcon = () => (
    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
    </svg>
);


export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const messages = usePage().props.translations?.messages || {};

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={messages.login_title || 'Sign in'} />

            <div className="mb-6">
                <h2 className="mt-6 text-3xl font-extrabold text-gray-900 ">
                    {messages.welcome_back || 'Welcome back'}
                </h2>
                <p className="mt-2 text-sm text-gray-600 ">
                    {messages.continue_with_email || 'Or continue with email'}{' '}
                    <Link
                        href={route('register')}
                        className="font-medium text-important hover:text-unimportant transition-colors"
                    >
                        {messages.create_account || 'create a new account'}
                    </Link>
                </p>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="mt-6">
                {/* Social Login Grid */}
                <div className="grid grid-cols-2 gap-3 mb-3">
                    <a href={route('socialite.redirect', 'google')} className="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-xl shadow-sm bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-200 transition-all duration-200">
                        <span className="sr-only">{messages.connect_google || 'Connect Google'}</span>
                        <div className="flex items-center gap-2">
                            <GoogleIcon /> <span>{messages.social_login_google || 'Google'}</span>
                        </div>
                    </a>

                    <a href={route('socialite.redirect', 'discord')} className="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-xl shadow-sm bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-200 transition-all duration-200">
                        <span className="sr-only">{messages.connect_discord || 'Connect Discord'}</span>
                        <div className="flex items-center gap-2">
                            <DiscordIcon /> <span>{messages.social_login_discord || 'Discord'}</span>
                        </div>
                    </a>
                </div>
                <div>
                    <a href={route('socialite.redirect', 'github')} className="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-xl shadow-sm bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-200 transition-all duration-200">
                        <span className="sr-only">{messages.connect_github || 'Connect GitHub'}</span>
                        <div className="flex items-center gap-2">
                            <GithubIcon /> <span>{messages.social_login_github || 'GitHub'}</span>
                        </div>
                    </a>
                </div>

                <div className="relative mt-8">
                    <div className="absolute inset-0 flex items-center" aria-hidden="true">
                        <div className="w-full border-t border-gray-200" />
                    </div>
                    <div className="relative flex justify-center text-sm">
                        <span className="bg-white px-4 text-gray-500 font-medium">{messages.continue_with_email || 'Ou avec votre email'}</span>
                    </div>
                </div>

                <div className="mt-8">
                    <form onSubmit={submit} className="space-y-5">
                        <div className="space-y-1">
                            <InputLabel htmlFor="email" value={messages.email_address || 'Adresse Email'} className="text-gray-700 font-semibold" />
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                autoComplete="username"
                                isFocused={true}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div className="space-y-1">
                            <InputLabel htmlFor="password" value={messages.password || 'Mot de passe'} className="text-gray-700 font-semibold" />
                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                            />
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-between">
                            <label className="flex items-center cursor-pointer">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
                                />
                                <span className="ms-2 text-sm text-gray-600">{messages.remember_me || 'Se souvenir de moi'}</span>
                            </label>

                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-sm font-medium text-emerald-600 hover:text-emerald-500 transition-colors"
                                >
                                    {messages.forgot_password || 'Mot de passe oublié ?'}
                                </Link>
                            )}
                        </div>

                        <div>
                            <PrimaryButton className="w-full flex justify-center py-3 bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 transition-all duration-200 text-base font-bold shadow-lg shadow-emerald-900/10 rounded-xl" disabled={processing}>
                                {messages.login_button || 'Se connecter'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </GuestLayout>
    );
}

