import Dropdown from '@/Components/Dropdown';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { usePage } from '@inertiajs/react';

export default function LanguageSwitcher({ mobile = false }) {
    const { locale } = usePage().props;

    const flags = {
        en: '🇺🇸',
        es: '🇪🇸',
        fr: '🇫🇷',
    };

    const currentFlag = flags[locale] || flags['en'];

    if (mobile) {
        return (
            <>
                <ResponsiveNavLink href={route('lang.switch', 'en')}>{flags.en}</ResponsiveNavLink>
                <ResponsiveNavLink href={route('lang.switch', 'es')}>{flags.es}</ResponsiveNavLink>
                <ResponsiveNavLink href={route('lang.switch', 'fr')}>{flags.fr}</ResponsiveNavLink>
            </>
        );
    }

    return (
        <div className="relative">
            <Dropdown>
                <Dropdown.Trigger>
                    <span className="inline-flex rounded-md">
                        <button
                            type="button"
                            className="inline-flex items-center rounded-md border border-transparent bg-white  px-3 py-2 text-xl leading-4 text-gray-500  transition duration-150 ease-in-out hover:text-gray-700  hover:bg-gray-100  focus:outline-none"
                        >
                            {currentFlag}

                            <svg
                                className="-me-0.5 ms-2 h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </button>
                    </span>
                </Dropdown.Trigger>

                <Dropdown.Content width="w-min">
                    <Dropdown.Link href={route('lang.switch', 'en')}>
                        {flags.en}
                    </Dropdown.Link>
                    <Dropdown.Link href={route('lang.switch', 'es')}>
                        {flags.es}
                    </Dropdown.Link>
                    <Dropdown.Link href={route('lang.switch', 'fr')}>
                        {flags.fr}
                    </Dropdown.Link>
                </Dropdown.Content>
            </Dropdown>
        </div>
    );
}
