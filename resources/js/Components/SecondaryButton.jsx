export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center rounded-md border border-gray-300 bg-white dark:bg-[#18181b] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 dark:text-gray-200 shadow-sm dark:shadow-[0px_8px_24px_rgba(147,51,234,0.06)] transition duration-150 ease-in-out hover:bg-gray-50 dark:hover:bg-[#272729] focus:outline-none focus:ring-2 focus:ring-[#9333ea] focus:ring-offset-2 disabled:opacity-25 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
