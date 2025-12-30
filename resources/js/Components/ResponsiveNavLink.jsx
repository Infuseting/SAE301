import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                active
                    ? 'border-[#9333ea] bg-[#f5f3ff] text-[#4c1d95] focus:border-[#7a2ce6] focus:bg-[#f3e8ff] focus:text-[#5b21b6] dark:bg-[#1f1233] dark:text-[#e9d7ff]'
                    : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 dark:text-gray-300'
            } text-base font-medium transition duration-150 ease-in-out focus:outline-none ${className}`}
        >
            {children}
        </Link>
    );
}
