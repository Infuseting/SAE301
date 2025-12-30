export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center rounded-md border border-transparent bg-[#9333ea] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-[#7a2ce6] focus:bg-[#7a2ce6] focus:outline-none focus:ring-2 focus:ring-[#9333ea] focus:ring-offset-2 active:bg-[#6a22cc] shadow-sm hover:shadow-md ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
